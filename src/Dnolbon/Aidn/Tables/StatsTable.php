<?php
namespace Dnolbon\Aidn\Tables;

use Dnolbon\Aidn\Wordpress\WordpressDb;
use Dnolbon\Aidn\Wordpress\WpListTable;

class StatsTable extends WpListTable
{

    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @since 3.1.0
     * @access public
     *
     * @return array
     */
    public function getColumns()
    {
        $columns = [
            'image' => 'Thumb',
            'external_id' => 'Amazon SKU',
            'title' => 'Title',
            'hits' => 'Hits',
            'orders' => 'Redirected',
            'post_date' => 'Date added'
        ];
        return $columns;
    }

    /**
     * Prepares the list of items for displaying.
     * @uses WP_List_Table::set_pagination_args()
     *
     * @since 3.1.0
     * @access public
     */
    public function prepareItems()
    {
        $current_page = $this->getPagenum();

        $db = WordpressDb::getInstance()->getDb();

        $sql = 'SELECT count(*) FROM ' . $db->prefix . AIDN_TABLE_GOODS_ARCHIVE . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("amazon#", ' . $db->prefix . AIDN_TABLE_GOODS_ARCHIVE . '.external_id) 
                    where ' . $db->postmeta . '.meta_id is not null ';
        $total = $db->get_var($sql);

        $sql = 'SELECT 
                    ' . $db->prefix . AIDN_TABLE_GOODS_ARCHIVE . '.*, 
                    (select count(*) from ' . $db->prefix . AIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AIDN_TABLE_STATS . '.product_id
                    and quantity = 0) as hits,
                    ifnull((select sum(quantity) from ' . $db->prefix . AIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AIDN_TABLE_STATS . '.product_id), 0) as orders,
                    ' . $db->posts . '.post_date
                FROM ' . $db->prefix . AIDN_TABLE_GOODS_ARCHIVE . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("amazon#", ' . $db->prefix . AIDN_TABLE_GOODS_ARCHIVE . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                
                order by ' . (isset($_GET['orderby']) ? $_GET['orderby'] . ' ' . $_GET['order'] : 'post_date desc') . '
                    
                limit ' . (($current_page - 1) * 20) . ',20';
        $this->items = $db->get_results($sql);

        $this->setPagination(['total_items' => $total, 'per_page' => 20]);

        $this->initTable();
    }

    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     *
     * @since 3.1.0
     * @access protected
     *
     * @return array
     */
    protected function getSortableColumns()
    {
        return [
            'external_id' => ['external_id', false],
            'title' => ['title', false],
            'hits' => ['hits', false],
            'orders' => ['orders', false],
            'post_date' => ['post_date', false]
        ];
    }

    protected function columnImage($item)
    {
        return '<img src="' . $item->image . '">';
    }

    public function getId($item)
    {
        return 'amazon#' . $item->external_id;
    }
}
