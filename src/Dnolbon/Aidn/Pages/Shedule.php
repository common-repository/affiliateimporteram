<?php
namespace Dnolbon\Aidn\Pages;

use Dnolbon\Aidn\Tables\SheduleTable;
use Dnolbon\Aidn\Wordpress\WpListTable;

class Shedule
{
    /**
     * @var WpListTable $table
     */
    private $table;

    public function render()
    {
        $activePage = 'schedule';
        include AIDN_ROOT_PATH . '/layout/toolbar.php';

        $this->getTable()->prepareItems();
        include AIDN_ROOT_PATH . '/layout/shedule.php';
    }

    /**
     * @return WpListTable
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new SheduleTable();
        }
        return $this->table;
    }
}
