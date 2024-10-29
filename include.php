<?php
//date_default_timezone_set('GMT');

if (!defined('AIDN_NAME')) {
    define('AIDN_NAME', 'Affiliate Am');
}

if (!defined('AIDN_TABLE_LOG')) {
    define('AIDN_TABLE_LOG', 'aidn_log');
}

if (!defined('AIDN_TABLE_BLACKLIST')) {
    define('AIDN_TABLE_BLACKLIST', 'aidn_blacklist');
}

if (!defined('AIDN_TABLE_STATS')) {
    define('AIDN_TABLE_STATS', 'aidn_stats');
}

if (!defined('AIDN_TABLE_GOODS')) {
    define('AIDN_TABLE_GOODS', 'aidn_goods');
}

if (!defined('AIDN_TABLE_GOODS_ARCHIVE')) {
    define('AIDN_TABLE_GOODS_ARCHIVE', 'aidn_goods_archive');
}

if (!defined('AIDN_TABLE_ACCOUNT')) {
    define('AIDN_TABLE_ACCOUNT', 'aidn_account');
}

if (!defined('AIDN_TABLE_PRICE_FORMULA')) {
    define('AIDN_TABLE_PRICE_FORMULA', 'aidn_price_formula');
}

if (!defined('AIDN_NO_IMAGE_URL')) {
    define('AIDN_NO_IMAGE_URL', plugins_url('assets/img', 'iconPlaceholder_96x96.gif'));
}

if (!defined('AIDN_DEL_COOKIES_FILE_AFTER')) {
    define('AIDN_DEL_COOKIES_FILE_AFTER', 86400);
}

$classPath = __DIR__ . '/src/AIDN';

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

include_once $classPath . '/Log/Log.php';

include_once $classPath . '/Goods/Goods.php';
include_once $classPath . '/Abstract/Account.php';
include_once $classPath . '/Abstract/Loader.php';
include_once $classPath . '/Abstract/Configurator.php';
include_once $classPath . '/WooCommerce/WooCommerce.php';
include_once $classPath . '/Prices/PriceFormula.php';

include_once $classPath . '/Utils/Utils.php';
include_once $classPath . '/Pages/DashboardPage.php';
include_once $classPath . '/Pages/SettingsPage.php';
include_once $classPath . '/WooCommerce/ProductList.php';
include_once $classPath . '/WooCommerce/OrderList.php';

include_once $classPath . '/Utils/Ajax.php';

$AIDN_GLOBAL_API_LIST = array();

if (!function_exists('aidn_add_api')) {

    /**
     * @param AIDN_AbstractConfigurator $api_configurator
     */
    function aidn_add_api($api_configurator)
    {
        global $AIDN_GLOBAL_API_LIST;
        if (!is_array($AIDN_GLOBAL_API_LIST)) {
            $AIDN_GLOBAL_API_LIST = array();
        }
        if ($api_configurator instanceof AIDN_AbstractConfigurator) {
            $find = false;
            foreach ($AIDN_GLOBAL_API_LIST as $tmp_api) {
                if ($tmp_api->get_type() === $api_configurator->getType()) {
                    $find = true;
                    break;
                }
            }
            if (!$find) {
                $AIDN_GLOBAL_API_LIST[$api_configurator->getType()] = $api_configurator;
            }
        }
    }

}

/* include api modules */
foreach (glob(AIDN_ROOT_PATH . 'src/AIDN/Modules/*', GLOB_ONLYDIR) as $dir) {
    $file_list = scandir($dir . '/');
    $include_array = array();
    foreach ($file_list as $f) {
        if (is_file($dir . '/' . $f)) {
            $file_info = pathinfo($f);
            if ($file_info['extension'] === 'php') {
                $file_data = get_file_data($dir . '/' . $f, array('position' => '@position'));
                $include_array[$dir . '/' . $f] = (int)$file_data['position'];
            }
        }
    }
    asort($include_array);
    foreach ($include_array as $file => $p) {
        include_once $file;
    }
}
/* include api modules */

/* include addons */
$dirs = glob(AIDN_ROOT_PATH . 'addons/*', GLOB_ONLYDIR);
if ($dirs && is_array($dirs)) {
    foreach (glob(AIDN_ROOT_PATH . 'addons/*', GLOB_ONLYDIR) as $dir) {
        $file_list = scandir($dir . '/');
        foreach ($file_list as $f) {
            if (is_file($dir . '/' . $f)) {
                $file_info = pathinfo($f);
                if ($file_info['extension'] === 'php') {
                    include_once $dir . '/' . $f;
                }
            }
        }
    }
}
/* include addons */

if (!function_exists('aidn_get_api_list')) {

    /**
     * @param bool $installed_only
     * @return AIDN_AbstractConfigurator[]
     */
    function aidn_get_api_list($installed_only = false)
    {
        global $AIDN_GLOBAL_API_LIST;
        $api_list = array();

        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach ($AIDN_GLOBAL_API_LIST as $api) {
            if ($api instanceof AIDN_AbstractConfigurator && (!$installed_only || $api->isInstaled())) {
                $api_list[$api->getType()] = $api;
            }
        }
        return $api_list;
    }

}

if (!function_exists('aidn_get_api')) {

    /**
     * @param $type
     * @return AIDN_AbstractConfigurator
     */
    function aidn_get_api($type)
    {
        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach (aidn_get_api_list() as $api) {
            if ($api->getType() === $type) {
                return $api;
            }
        }
        return null;
    }

}

if (!function_exists('aidn_get_default_api')) {

    function aidn_get_default_api()
    {
        $api_list = aidn_get_api_list();

        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach ($api_list as $api) {
            if ($api->isInstaled()) {
                return $api;
            }
        }
        return false;
    }

}

if (!function_exists('aidn_get_root_menu_id')) {

    function aidn_get_root_menu_id()
    {
        $default_api = aidn_get_default_api();
        return AIDN_ROOT_MENU_ID . ($default_api ? ('-' . $default_api->getType()) : '');
    }

}

if (!function_exists('aidn_get_loader')) {

    /**
     * @param $type
     * @return AIDN_AbstractLoader
     */
    function aidn_get_loader($type)
    {
        $api_list = aidn_get_api_list();
        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach ($api_list as $api) {
            if ($api->getType() === $type && class_exists($api->getConfigValues('loader_class'))) {
                $class_name = $api->getConfigValues('loader_class');
                return apply_filters('aidn_get_loader', new $class_name($api));
            }
        }
        return null;
    }

}

if (!function_exists('aidn_get_account')) {

    function aidn_get_account($type)
    {
        $api_list = aidn_get_api_list();
        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach ($api_list as $api) {
            if ($api->getType() === $type && class_exists($api->getConfigValues('account_class'))) {
                $class_name = $api->getConfigValues('account_class');
                return apply_filters('aidn_get_account', new $class_name($api));
            }
        }
        return false;
    }

}

if (!function_exists('aidn_get_api_path')) {

    function aidn_get_api_path($api)
    {
        if ($api instanceof AIDN_AbstractConfigurator) {
            return AIDN_ROOT_PATH . 'src/AIDN/Modules/' . $api->getType() . '/';
        }
        return '';
    }

}

if (!function_exists('aidn_get_api_url')) {

    function aidn_get_api_url($api)
    {
        if ($api instanceof AIDN_AbstractConfigurator) {
            return AIDN_ROOT_URL . 'src/AIDN/Modules/' . $api->getType() . '/';
        }
        return false;
    }

}

if (!function_exists('aidn_api_enqueue_style')) {

    /**
     * @param AIDN_AbstractConfigurator $api
     */
    function aidn_api_enqueue_style($api)
    {
        $dirs = glob(aidn_get_api_path($api) . 'styles/', GLOB_ONLYDIR);
        if ($dirs && is_array($dirs)) {
            foreach (glob(aidn_get_api_path($api) . 'styles/', GLOB_ONLYDIR) as $dir) {
                $file_list = scandir($dir . '/');
                foreach ($file_list as $f) {
                    if (is_file($dir . '/' . $f)) {
                        $file_info = pathinfo($f);
                        if ($file_info['extension'] === 'css') {
                            wp_enqueue_style('aidn-' . $api->getType() . '-' . $file_info['filename'], aidn_get_api_url($api) . 'styles/' . $file_info['basename'], array(), $api->getConfigValues('version'));
                        }
                    }
                }
            }
        }
    }

}

if (!function_exists('aidn_error_handler')) {

    function aidn_error_handler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        switch ($errno) {
            case E_USER_ERROR:
                $mess = "<b>ERROR</b> [$errno] $errstr<br />\n Fatal error on line $errline in file $errfile, PHP " . PHP_VERSION . ' (' . PHP_OS . ")<br />\n";
                throw new Exception($mess);
            case E_USER_WARNING:
                $mess = "<b>My WARNING</b> [$errno] $errstr<br />\n";
                throw new Exception($mess);

            case E_USER_NOTICE:
                $mess = "<b>My NOTICE</b> [$errno] $errstr<br />\n";
                throw new Exception($mess);

            default:
                $mess = "Unknown error[$errno] on line $errline in file $errfile: $errstr<br />\n";
                throw new Exception($mess);
        }
    }

}

if (!function_exists('aidn_log')) {

    function aidn_log($message)
    {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }
}

if (!function_exists('aidn_add_js_hook')) {

    function aidn_add_js_hook(&$result, $hook_name, $params)
    {
        if ($result !== null || !$result) {
            $result = array();
        }

        if (!isset($result['js_hook'])) {
            $result['js_hook'] = array();
        } else if (!is_array($result['js_hook'])) {
            $result['js_hook'] = array($result['js_hook']);
        }
        $result['js_hook'][] = array('name' => $hook_name, 'params' => $params);

        return $result;
    }
}


if (!function_exists('aidn_get_goods_by_post_id')) {

    function aidn_get_goods_by_post_id($post_id)
    {
        $goods = false;
        if ($post_id) {
            $external_id = get_post_meta($post_id, 'external_id', true);
            if ($external_id) {
                $goods = new AIDN_Goods($external_id);
                $cats = wp_get_object_terms($post_id, 'product_cat');
                if ($cats && !is_wp_error($cats)) {
                    $goods->link_category_id = $cats[0]->term_id;
                    $goods->additional_meta = array();
                    $goods->additional_meta['detail_url'] = 'www.aliexpress.com/item//' . $goods->external_id . '.html';
                }
            }
        }
        return $goods;
    }
}


if (!function_exists('aidn_get_sorted_products_ids')) {

    function aidn_get_sorted_products_ids($sort_type, $ids_count)
    {

        $result = array();

        $api_type_list = array();
        $api_list = aidn_get_api_list(true);
        /**
         * @var AIDN_AbstractConfigurator $api
         */
        foreach ($api_list as $api) {
            $api_type_list[] = $api->getType();
        }

        $ids0 = get_posts(array(
            'post_type' => 'product',
            'fields' => 'ids',
            'numberposts' => $ids_count,
            'meta_query' => array(
                array(
                    'key' => 'import_type',
                    'value' => $api_type_list,
                    'compare' => 'IN'
                ),
                array(
                    'key' => $sort_type,
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        foreach ($ids0 as $id) {
            $result[] = $id;
        }

        if (($ids_count - count($result)) > 0) {
            $res = get_posts(array(
                'post_type' => 'product',
                'fields' => 'ids',
                'numberposts' => $ids_count - count($result),
                'meta_query' => array(
                    array(
                        'key' => 'import_type',
                        'value' => $api_type_list,
                        'compare' => 'IN'
                    )
                ),
                'order' => 'ASC',
                'orderby' => 'meta_value',
                'meta_key' => $sort_type,
                //allow hooks
                'suppress_filters' => false
            ));

            foreach ($res as $id) {
                $result[] = $id;
            }
        }
        return $result;
    }

}

if (!function_exists('aidn_remote_get')) {

    function aidn_remote_get($url, $args = array())
    {
        add_filter('http_api_transports', 'aidn_custom_curl_transport', 100, 3);

        $def_args = array('headers' => array('Accept-Encoding' => ''), 'timeout' => 30, 'user-agent' => 'Toolkit/1.7.3', 'sslverify' => false);

        if (!is_array($args)) {
            $args = array();
        }

        foreach ($def_args as $key => $val) {
            if (!isset($args[$key])) {
                $args[$key] = $val;
            }
        }

        return wp_remote_get($url, $args);
    }

}

if (!function_exists('aidn_custom_curl_transport')) {
    function aidn_custom_curl_transport($transports)
    {
        array_unshift($transports, 'aidn_curl');
        return $transports;
    }
}

if (!function_exists('aidn_cookies_file_path')) {
    function aidn_cookies_file_path($proxy = '')
    {
        $proxy_path = $proxy ? ('_' . str_replace(array('.', ':'), '_', $proxy)) : '';
        $file_path = WP_CONTENT_DIR . '/aidn_cookie' . $proxy_path . '.txt';

        if (AIDN_DEL_COOKIES_FILE_AFTER && file_exists($file_path)) {
            $time_upd = filemtime($file_path);

            if (abs(time() - $time_upd) > AIDN_DEL_COOKIES_FILE_AFTER) {
                unlink($file_path);
            }
        }

        return $file_path;
    }
}

if (!function_exists('aidn_proxy_get')) {
    function aidn_proxy_get()
    {
        $proxy = '';
        if (get_option('aidn_use_proxy', false)) {
            $proxies_str = str_replace([' ', "\n"], ['', ';'], get_option('aidn_proxies_list', ''));

            $arr_proxies = explode(';', $proxies_str);

            $arr_proxies = apply_filters('aidn_get_proxy_list', $arr_proxies);

            $proxies = array();
            foreach ($arr_proxies as $k => $v) {
                $proxies[$k] = trim($v);
            }

            if ($proxies) {
                $proxy = $proxies[array_rand($proxies)];
            }
        }
        return $proxy;
    }
}