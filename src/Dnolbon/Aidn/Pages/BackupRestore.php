<?php
namespace Dnolbon\Aidn\Pages;

class BackupRestore
{
    public function render()
    {
        $this->loadFile();

        $activePage = 'backup';

        include AIDN_ROOT_PATH . '/layout/toolbar.php';

        include AIDN_ROOT_PATH . '/layout/backup_restore.php';
    }

    public function loadFile()
    {
        foreach ($_FILES as $file) {
            $csv = array_map('str_getcsv', file($file['tmp_name']));
            foreach ($csv as $line) {
                update_option($line[0], $line[1]);
            }
        }
    }
}
