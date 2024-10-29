<?php
/**
 *
 */
if (!class_exists('AIDN_AbstractConfigurator')) {
    /**
     * Class AIDN_AbstractConfigurator
     */
    abstract class AIDN_AbstractConfigurator
    {

        private $filter_config = array();

        public function __construct()
        {
            aidn_add_api($this);
            $this->checkApiConfigure();

            add_action('aidn_print_api_setting_page', [$this, 'printApiAccountSettingPage'], 10, 1);

            add_action('aidn_print_api_setting_page', [$this, 'printApiSettingPage'], 11, 1);
        }

        public final function init()
        {
            add_filter('aidn_get_dashboard_columns', [$this, 'modifyColumns'], 10, 2);
            add_filter('aidn_get_dashboard_sortable_columns', [$this, 'modifySortableColumns'], 10, 1);
            add_filter('aidn_dashboard_column_default', [$this, 'modifyColumnData'], 10, 3);

            add_action('aidn_befor_dashboard_render', [$this, 'printPageHeader'], 10, 1);


            add_action('aidn_dashboard_render', [$this, 'printPage'], 10, 1);

            add_action('aidn_after_dashboard_render', [$this, 'printPageFooter'], 10, 1);

            add_action('aidn_print_api_setting_page', [$this, 'printApiSettingPage'], 10, 1);

            $this->initModule();

            $this->initFilters();

            $this->configureFilters();

            do_action('aidn_init_custom_filter', $this);
        }

        // should return config array!!!
        abstract public function getConfig();

        /**
         * @return string
         */
        public final function getType()
        {
            return $this->getConfigValues('type');
        }

        /**
         * @param $key
         * @return string
         */
        public final function getConfigValues($key)
        {
            $config = $this->getConfig();
            return isset($config[$key]) ? $config[$key] : '';
        }

        public function isInstaled()
        {
            $config = $this->getConfig();
            return (is_array($config) && count($config) && isset($config['instaled']) && $config['instaled']);
        }

        public function printPage($dashboard)
        {
            $dashboard_view = aidn_get_api_path($this) . 'layout/dashboard.php';
            if (file_exists($dashboard_view)) {
                include_once $dashboard_view;
            } else {
                include_once AIDN_ROOT_PATH . '/layout/dashboard.php';
            }
        }

        /**
         * @param AIDN_AbstractConfigurator $api
         */
        public function printApiAccountSettingPage($api)
        {
            if ($api->getType() === $this->getType()) {
                $api_account = aidn_get_account($api->getType());
                if ($api_account) {
                    $api_account->printForm();
                }
            }
        }

        /**
         * @param AIDN_AbstractConfigurator $api
         */
        public function printApiSettingPage($api)
        {
            if ($api->getType() === $this->getType()) {
                $setting_view = aidn_get_api_path($this) . 'layout/settings.php';
                if (file_exists($setting_view)) {
                    include_once $setting_view;
                }
            }
        }

        public function printPageHeader()
        {

        }

        public function printPageFooter()
        {
//            echo '<div class="aidn_tr_module_version">Module version: ' . $this->getConfigValues('version') . '</div>';
        }

        public function install()
        {

        }

        public function uninstall()
        {

        }

        // configure common filters
        private final function initFilters()
        {
            $this->addFilter('aidn_productId', 'aidn_productId', 10, [
                'type' => 'edit',
                'label' => 'ProductId',
                'dop_row' => 'OR configure search filter',
                'placeholder' => 'Please enter your productId'
            ]);
            $this->addFilter('aidn_query', 'aidn_query', 20, [
                'type' => 'edit',
                'label' => 'Keywords',
                'placeholder' => 'Please enter your Keywords'
            ]);
            $this->addFilter('price', ['aidn_min_price', 'aidn_max_price'], 30, [
                'type' => 'edit',
                'label' => 'Price',
                'aidn_min_price' => ['label' => "from $", 'default' => '0.00'],
                'aidn_max_price' => ['label' => " to $", 'default' => '0.00']
            ]);
        }

        // configure custom api filters
        protected function configureFilters()
        {

        }

        public final function addFilter($id, $name, $order = 1000, $config = array())
        {
            $this->filter_config[$id] = array('id' => $id, 'name' => $name, 'config' => $config, 'order' => $order);
        }

        public final function removeFilter($id)
        {
            unset($this->filter_config[$id]);
        }

        public final function getFilters()
        {
            $result = array();
            foreach ($this->filter_config as $id => $filter) {
                $result[$id] = $filter;
                if (isset($filter['config']['data_source']) && $filter['config']['data_source']) {
                    if (is_array($filter['config']['data_source'])) {
                        $result[$id]['config']['data_source'] = $filter['config']['data_source'][0]->{$filter['config']['data_source'][1]}();
                    } else {
                        $result[$id]['config']['data_source'] = ${$filter['config']['data_source']}();
                    }
                }
            }
            if (!function_exists('AIDN_AbstractConfigurator_cmp')) {

                function AIDN_AbstractConfigurator_cmp($a, $b)
                {
                    if ($a['order'] === $b['order']) {
                        return 0;
                    }
                    return ($a['order'] < $b['order']) ? -1 : 1;
                }

            }
            uasort($result, 'AIDN_AbstractConfigurator_cmp');

            return $result;
        }

        // configure custom api initialization
        protected function initModule()
        {

        }

        public function modifyColumns($columns)
        {
            return $columns;
        }

        public function modifySortableColumns($columns)
        {
            $sortable_columns = $columns;
            if (is_array($this->getConfigValues('sort_columns'))) {
                foreach ($this->getConfigValues('sort_columns') as $sc) {
                    $sortable_columns[$sc] = array($sc, false);
                }
            }
            return $sortable_columns;
        }

        abstract public function modifyColumnData($data, $item, $column_name);

        abstract public function saveSetting($data);

        private final function checkApiConfigure()
        {
            $config = $this->getConfig();
            if (!is_array($config)) {
                throw new Exception('AIDN Error: ' . get_class($this) . ' uncorect API configure! get_config() must return array');
            } else if (!isset($config['type']) || !$config['type']) {
                throw new Exception('AIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have not empty "type"');
            } else if ($this->isInstaled()) {
                if (!isset($config['account_class'])) {
                    throw new Exception('AIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have correct "account_class"');
                } else if (!isset($config['loader_class'])) {
                    throw new Exception('AIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have correct "loader_class"');
                }
            }
        }

    }
}
