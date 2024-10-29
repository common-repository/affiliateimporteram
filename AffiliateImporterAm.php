<?php
/*
  Plugin Name: AffiliateImporterAm
  Description: This plugin allows you to import the products directly from Amazon in your Wordpress WooCommerce store and earn a commission!
  Version: 1.0.6
  Author: CR1000Team
  License: GPLv2+
  Author URI: http://cr1000team.com
 */
use Dnolbon\Aidn\Pages\BackupRestore;
use Dnolbon\Aidn\Pages\Dashboard;
use Dnolbon\Aidn\Pages\Shedule;
use Dnolbon\Aidn\Pages\Stats;
use Dnolbon\Aidn\Pages\Status;
use Dnolbon\Aidn\Pages\Support;
use Dnolbon\Aidn\Wordpress\WordpressMenuFactory;
use Dnolbon\Aidn\Wordpress\WordpressStats;
use Dnolbon\Aidn\Wordpress\WordpressTranslates;

if (!defined('AIDN_PLUGIN_NAME')) {
    define('AIDN_PLUGIN_NAME', plugin_basename(__FILE__));
}

if (!defined('AIDN_ROOT_URL')) {
    define('AIDN_ROOT_URL', plugin_dir_url(__FILE__));
}
if (!defined('AIDN_ROOT_PATH')) {
    define('AIDN_ROOT_PATH', plugin_dir_path(__FILE__));
}

if (!defined('AIDN_FILE_FULLNAME')) {
    define('AIDN_FILE_FULLNAME', __FILE__);
}
if (!defined('AIDN_ROOT_MENU_ID')) {
    define('AIDN_ROOT_MENU_ID', 'aidn-dashboard');
}

include_once __DIR__ . '/autoload.php';
include_once __DIR__ . '/include.php';
include_once __DIR__ . '/schedule.php';
include_once __DIR__ . '/install.php';
include_once __DIR__ . '/screenoptions.php';

if (!class_exists('AffiliateImporterAm')) {

    class AffiliateImporterAm
    {
        /**
         * @var WordpressTranslates $wordpressTranslates
         */
        private $wordpressTranslates;

        /**
         * @var WordpressStats $wordpressStats
         */
        private $wordpressStats;

        public function __construct()
        {
            register_activation_hook(__FILE__, [$this, 'install']);
            register_deactivation_hook(__FILE__, [$this, 'uninstall']);

            if (is_plugin_active(AIDN_PLUGIN_NAME)) {
                if (!is_plugin_active('woocommerce/woocommerce.php')) {

                    add_action('admin_notices', [$this, 'woocomerceCheckError']);

                    if (AIDN_DEACTIVATE_IF_WOOCOMERCE_NOT_FOUND) {
                        deactivate_plugins(AIDN_PLUGIN_NAME);
                        if (isset($_GET['activate'])) {
                            unset($_GET['activate']);
                        }
                    }
                }

                add_action('admin_menu', [$this, 'registerMenu']);
                add_action('admin_enqueue_scripts', [$this, 'registerAssets']);

                add_filter('plugin_action_links_' . AIDN_PLUGIN_NAME, [$this, 'registerActionLinks']);

                aidn_check_db_update();

                $this->registerActions();

                $this->wordpressTranslates = new WordpressTranslates();
                $this->wordpressStats = new WordpressStats();


                add_action('admin_init', [$this, 'aidnActivateRedirect']);
            } else {
                register_activation_hook(__FILE__, [$this, 'aidnActivateInstall']);
            }
        }

        public function aidnActivateRedirect()
        {
            if (get_option('aidn_activate_redirect', false)) {
                delete_option('aidn_activate_redirect');
                wp_redirect("admin.php?page=aidn-settings#amazon");
                //wp_redirect() does not exit automatically and should almost always be followed by exit.
                exit;
            }
        }

        public function aidnActivateInstall()
        {
            add_option('aidn_activate_redirect', true);
        }

        public function registerActions()
        {
//            $frontEnd = new Frontend();
//            $frontEnd->init();
        }

        /**
         *
         */
        public function woocomerceCheckError()
        {
            $class = 'notice notice-error';
            $message = __(
                'AffiliateImporterAm notice! Please install the Woocommerce plugin first.',
                'sample-text-domain'
            );
            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        }

        /**
         *
         */
        public function registerAssets()
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugin_data = get_plugin_data(__FILE__);

            wp_enqueue_style('aidn-style', plugins_url('assets/css/dnolbon.css', __FILE__), array(), $plugin_data['Version']);

            wp_enqueue_style('aidn-style', plugins_url('assets/css/style.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aidn-font-style', plugins_url('assets/css/font-awesome.min.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aidn-dtp-style', plugins_url('assets/js/datetimepicker/jquery.datetimepicker.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aidn-lighttabs-style', plugins_url('assets/js/lighttabs/lighttabs.css', __FILE__), array(), $plugin_data['Version']);

            wp_enqueue_script('aidn-script', plugins_url('assets/js/script.js', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_script('aidn-dtp-script', plugins_url('assets/js/datetimepicker/jquery.datetimepicker.js', __FILE__), array('jquery'), $plugin_data['Version']);
            wp_enqueue_script('aidn-lighttabs-script', plugins_url('assets/js/lighttabs/lighttabs.js', __FILE__), array('jquery'), $plugin_data['Version']);

            wp_enqueue_script(
                'aidn-columns-script',
                plugins_url('assets/js/DnolbonColumns.js', __FILE__),
                [],
                $plugin_data['Version']
            );

            wp_localize_script('aidn-script', 'WPURLS', array('siteurl' => site_url()));
        }

        /**
         *
         */
        public function registerMenu()
        {
            new AIDN_Goods();
            $api_list = aidn_get_api_list();

            $menu = WordpressMenuFactory::addMenu(
                AIDN_NAME,
                'manage_options',
                'aidn',
                [
                    'icon' => 'small_logo.png',
                    'function' => [new Dashboard(), 'render']
                ]
            );
            /**
             * @var AIDN_AbstractConfigurator $api
             */
            foreach ($api_list as $api) {
                if ($api->isInstaled()) {
                    if ($api->getConfigValues('menu_title')) {
                        $title = $api->getConfigValues('menu_title');
                    } else {
                        $title = $api->getType();
                    }

                    $menu->addChild(
                        WordpressMenuFactory::addMenu(
                            $title,
                            'manage_options',
                            'add',
                            ['function' => [new AIDN_DashboardPage($api->getType()), 'render']]
                        )
                    );
                }
            }

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Shedule',
                    'manage_options',
                    'schedule',
                    ['function' => [new Shedule(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Statistics',
                    'manage_options',
                    'stats',
                    ['function' => [new Stats(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Settings',
                    'manage_options',
                    'settings',
                    ['function' => [new AIDN_SettingsPage(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Backup / Restore',
                    'manage_options',
                    'backup',
                    ['function' => [new BackupRestore(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Status',
                    'manage_options',
                    'status',
                    ['function' => [new Status(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Support',
                    'manage_options',
                    'support',
                    ['function' => [new Support(), 'render']]
                )
            );

            $menu->show();

            do_action('aidn_admin_menu');
        }

        /**
         * @param $links
         * @return array
         */
        public function registerActionLinks($links)
        {
            return array_merge(array('<a href="' . admin_url('admin.php?page=aidn-settings') . '">' . 'Settings' . '</a>'), $links);
        }

        /**
         *
         */
        public function install()
        {
            aidn_install();
        }

        /**
         *
         */
        public function uninstall()
        {
            aidn_uninstall();
        }
    }

}

new AffiliateImporterAm();
