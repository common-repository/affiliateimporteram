<?php
namespace Dnolbon\Aidn\Pages;

use Dnolbon\Aidn\Tables\StatsTable;
use Dnolbon\Aidn\Wordpress\WpListTable;

class Stats
{
    /**
     * @var WpListTable $table
     */
    private $table;

    public function render()
    {
        $activePage = 'stats';
        include AIDN_ROOT_PATH . '/layout/toolbar.php';

        $this->getTable()->prepareItems();
        include AIDN_ROOT_PATH . '/layout/stats.php';
    }

    /**
     * @return WpListTable
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new StatsTable();
        }
        return $this->table;
    }
}
