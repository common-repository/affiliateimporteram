<?php
spl_autoload_register('aidn_plugin_autoload');


function aidn_plugin_autoload($class)
{
    $prefix = 'Dnolbon\\';
    $baseDir = __DIR__ . '/src/';

    if (strpos($class, $prefix) === false) {
        return;
    }

    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
}
