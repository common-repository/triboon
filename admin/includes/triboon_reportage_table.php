<?php


$triboon_db_version = '1.0.0';
require_once(__DIR__ . '/jalali_datetime.php');
require_once(__DIR__ . '/handle_requests.php');


if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

use Borsaco\jalaliDateTime\JDateTime as JDT;


class Triboon_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'Triboon',
            'plural' => 'triboon'
        ));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_title($item)
    {
        if ($item['state'] == 'publisher_rejected') {
            $actions = array(
                'preview' => sprintf('<a  href="?page=%s&action=preview&reportage_id=%s" style="color: white; background-color:#014559">%s</a>', sanitize_text_field($_REQUEST['page']), $item['reportage_id'], __('پیش نمایش', 'triboon')),
            );
        } elseif ($item['state'] == 'publisher_published') {
            $actions = array(
                'show' => sprintf('<a  href="?page=%s&action=show&reportage_id=%s" style="color: white; background-color:#014559" >%s</a>',sanitize_text_field($_REQUEST['page']), $item['reportage_id'], __('نمایش', 'triboon')),
            );
        } else {
            $actions = array(
                'edit' => sprintf('<a  href="?page=%s&action=edit&reportage_id=%s" style="color: white; background-color:#014559" >%s</a>', sanitize_text_field($_REQUEST['page']), $item['reportage_id'], __('بررسی رپورتاژ', 'triboon')),
            );
        }
        return sprintf('%s %s',
            $item['title'],
            $this->row_actions($actions)
        );
    }

    function column_preferred_publish_date($item)
    {
        $datetime = $item['preferred_publish_date'];
        $jdt = JDT::convertFormatToFormat('Y-m-d H:i:s', 'Y-m-d H:i:s', twp_datetime_with_timezone($datetime));
        return $jdt;
    }

    function column_primary_image($item)
    {
        $url = $item['primary_image'];
        if ($url == null)
            return '<img class="triboon-primary-image-col" src=' . 'https://panel.triboon.net/images/logo2.svg' . ' />';
        return '<img class="triboon-primary-image-col" src=' . $url . ' />';
    }

    function column_attachments($item){
        $attachments_url = $item['attachments'];
        if ($attachments_url != NULL) {
            return '<a class="triboon-download-btn" href='.$attachments_url.'> دانلود فایل </a>';
        }
        return "-";
    }

    function get_columns()
    {
        $columns = array(
            'title' => __('عنوان', 'triboon'),
            'preferred_publish_date' => __('تاریخ مورد نظر انتشار', 'triboon'),
            'subject' => __('موضوع', 'triboon'),
            'lead_content' => __('مقدمه رپورتاژ', 'triboon'),
            'description' => __('توضیحات', 'triboon'),
            'tags' => __('تگ ها', 'triboon'),
            'pricing_plan' => __('پلن خرید', 'triboon'),
            'primary_image' => __('تصویر اصلی', 'triboon'),
            'fa_state' => __('وضعیت', 'triboon'),
            'attachments' => __('ضمیمه ها', 'triboon')
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'title' => array('title', true),
            'preferred_publish_date' => array('preferred_publish_date', true),
        );
        return $sortable_columns;
    }

    protected function get_views()
    {
        $current = ( !empty(sanitize_text_field($_REQUEST['state']) ? sanitize_text_field($_REQUEST['state']): 'all'));
        global $wpdb;
        $table_name = $wpdb->prefix . 'triboon';
        $pending_count = count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_pending")));
        $rejected_count = count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_rejected")));
        $accepted_count = count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_accepted")));
        $published_count = count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_published")));
        $total_count = $pending_count + $rejected_count + $accepted_count + $published_count;
        $status_links = array(
            "all" => sprintf(__("<a href='?page=%s' class='triboon-states %s'>همه <span class='awaiting-mod'>%d</span> </a> ", 'triboon'), sanitize_text_field($_REQUEST['page']), ($current === "all" ? "triboon-state-active" : ""), $total_count),
            "publisher_pending" => sprintf(__("<a href='?page=%s&state=publisher_pending' class='triboon-states %s'>بررسی نشده <span class='awaiting-mod'>%d</span></a>" , 'triboon'), sanitize_text_field($_REQUEST['page']), ($current === "publisher_pending" ? "triboon-state-active" : ""),$pending_count),
            "publisher_accepted" => sprintf(__("<a href='?page=%s&state=publisher_accepted' class='triboon-states %s'>تایید شده<span class='awaiting-mod'>%d</span></a>", 'triboon'), sanitize_text_field($_REQUEST['page']), ($current === "publisher_accepted" ? "triboon-state-active" : ""), $accepted_count),
            "publisher_rejected" => sprintf(__("<a  href='?page=%s&state=publisher_rejected' class='triboon-states %s'>رد شده <span class='awaiting-mod'>%d</span></a>", 'triboon'), sanitize_text_field($_REQUEST['page']), ($current === "publisher_rejected" ? "triboon-state-active" : ""), $rejected_count),
            "publisher_published" => sprintf(__("<a  href='?page=%s&state=publisher_published' class='triboon-states %s'>منتشر شده <span class='awaiting-mod'>%d</span></a>", 'triboon'), sanitize_text_field($_REQUEST['page']), ($current === "publisher_published" ? "triboon-state-active" : ""), $published_count)
        );
        return $status_links;
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'triboon';
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $total_items = $wpdb->get_var("SELECT COUNT(reportage_id) FROM $table_name");
        $count = get_option('check_new_reportage');
        $notif_count = count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_pending")));
        update_option('notif_check', $notif_count);
        if ($total_items != $count) {
            echo '<div class="notice notice-success is-dismissible" id="message"><p>رپورتاژ جدید دارید</p></div>';
            update_option('check_new_reportage', $total_items);
        }
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'preferred_publish_date';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        if (isset($_REQUEST['state'])) {
            $state = sanitize_text_field($_REQUEST['state']);
            $total_items = $wpdb->get_var("SELECT COUNT(reportage_id) FROM $table_name WHERE state=\"" . $state . "\"");
            $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=\"" . $state . "\" ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        } else {
            $total_items = $wpdb->get_var("SELECT COUNT(reportage_id) FROM $table_name");
            $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        }
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}
?>