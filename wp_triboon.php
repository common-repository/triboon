<?php
/*
Plugin Name: Triboon
Description: This plugin can be used to pre-check your incoming reportages from triboon
Author: Triboon.net
Version: 1.0.6
*/
global $triboon_db_version;
$GLOBALS['pre_link'] = 'https://api.triboon.net';
require_once(__DIR__ . '/admin//includes/handle_requests.php');
require_once(__DIR__ . '/admin//includes/handle_table.php');
require_once(__DIR__ . '/admin//includes/handle_media.php');
require_once(__DIR__ . '/admin//includes/triboon_metabox.php');
require_once(__DIR__ . '/admin//includes/triboon_ajax_handler.php');
require_once(__DIR__ . '/admin//includes/triboon_reportage_table.php');
require_once(__DIR__ . '/admin/triboon_setting_page.php');
$GLOBALS['triboon_icon'] = "https://api.triboon.net/media/wp/triboon-3.svg";



register_activation_hook(__FILE__, 'twp_install_plugin');
register_activation_hook(__FILE__, 'create_triboon_table');
register_deactivation_hook(__FILE__, 'remove_triboon_table');
add_action('wp_ajax_my_file_upload', 'wp_triboon_images_handle');
add_action('wp_ajax_nopriv_my_file_upload', 'wp_triboon_images_handle');
add_action("init", 'twp_create_post_type');
add_action('admin_menu', 'twp_admin_menu');
add_action('plugins_loaded', 'triboon_update_db_check');
add_filter('wp_insert_post_data', 'twp_dont_publish', '99', 2);
add_filter('publish_triboon', 'twp_schedule_post', '99', 2);
add_filter('get_sample_permalink_html', 'twp_hide_permalink');
add_action('add_meta_boxes', 'triboon_add_custom_metabox');
add_action('wp_ajax_triboon_accept_reportage', 'triboon_accept_reportage_ajax' );
add_action('wp_ajax_triboon_reject_reportage', 'triboon_reject_reportage_ajax' );
add_action('admin_enqueue_scripts', 'twp_add_scripts' );
add_action('init', 'twp_custom_capability', 11);
add_action('register_uninstall_hook','twp_drop_table');
add_action('register_activation_hook', 'twp_set_notif_count');

function twp_set_notif_count(){
    update_option('check_new_reportage', 0);
}

function twp_custom_capability()
{
    $role = get_role('administrator');
    $role->add_cap('manage_triboon', true);
}

function twp_drop_table(){
    global $wpdb;
    global $triboon_db_version;
    $table_name = $wpdb->prefix . 'triboon';
    $drop_sql = "DROP TABLE IF EXISTS " . $table_name . " ;";
    $wpdb->query($drop_sql);
}



function twp_add_scripts(){
    wp_register_script( 
        'ajaxHandle', 
        plugins_url('admin/js/triboon_admin.js', __FILE__), 
        array(), 
        false, 
        true 
    );
    wp_enqueue_script( 'ajaxHandle' );
    wp_localize_script( 
        'ajaxHandle', 
        'ajax_object', 
        array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) 
    );
    wp_enqueue_style( 
      'triboon', 
     plugins_url('admin/css/style.css', __FILE__)
    );
}

function twp_install_plugin()
{
    twp_create_post_type();
    flush_rewrite_rules();
}



function twp_datetime_with_timezone($utc_datetime_str)
{
    $dt = new DateTime($utc_datetime_str, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
    return $dt->format('Y-m-d H:i:s');

}

function twp_create_post_type()
{
    $labels = array(
        'name' => 'Triboon',
        'all_items' => 'reportages',
        'singular_name' => 'reportage',
    );


    $args = array(
        'labels' => $labels,
        'public' => true,
        'query_var' => true,
        'publicly_queryable' => true,
        'show_in_menu' => false,
        'capability_type' => 'post',
        'menu_icon' => 'dashicons-text-page',
        'capabilities' => array(
            'create_posts' => 'do_not_allow',
            'preview_posts' => 'do_not_allow',
            'delete_posts' => 'do_not_allow',
        ),
        'taxonomies' => array(
            'category',
            'post_tag',
        ),
        'map_meta_cap' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt')
    );
    register_post_type('Triboon', $args);
}


function twp_schedule_post($post_id, $post)
{
        if ($post && $post->post_type == 'triboon') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'triboon';
            $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE post_id=" . $post_id);
            if ($thereportage != null && $thereportage->state==="publisher_accepted") {
                $post->post_content = wp_triboon_images_handle($post->post_content)['html_file'];
                $id = wp_triboon_images_handle(twp_get_content_file($thereportage))['featured_img_id'];
                if ($id != null) {
                    set_post_thumbnail($post_id, $id);
                  }
                $post->post_type = 'post';
                publish_triboon_post_request_handle($thereportage->post_id, $thereportage->reportage_id);
            }
            else{
                $post->post_status = 'draft';
                $post->guid= "";
//                add_filter('redirect_post_location', 'triboon_post_publish_redirection', 99);

            }
        wp_update_post( $post );
        }
    }



function twp_dont_publish($data, $postarr)
{
        if ($data['post_type'] == 'triboon' && $data['post_status'] == 'publish') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'triboon';
            $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE post_id=" . $postarr['ID']);
            if ($thereportage != null && $thereportage->state==="publisher_accepted") {
                $data['post_content'] = wp_triboon_images_handle($data['post_content'])['html_file'];
                $id = wp_triboon_images_handle(twp_get_content_file($thereportage))['featured_img_id'];
                if ($id != null) {
                    set_post_thumbnail($postarr['ID'], $id);
                  }
                $data['post_type'] = 'post';
                publish_triboon_post_request_handle($thereportage->post_id, $thereportage->reportage_id);
            }
            else{
                $data['post_status'] = 'draft';
                $data['guid']= "";
//                add_filter('redirect_post_location', 'triboon_post_publish_redirection', 99);
            }
        }
    return $data;
}


function twp_hide_permalink($link)
{
    return str_replace('/triboon', '', $link);
}



function twp_admin_menu()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    update_option('notif_check', count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s", "publisher_pending"))));
    add_menu_page(__('Reportages', 'triboon'),
        get_option('notif_check') ? sprintf('تریبون <span class="awaiting-mod">%d</span>', get_option('notif_check')) : 'تریبون',
        'manage_triboon', 'triboon', 'triboon_handler', $GLOBALS['triboon_icon'] , 5);
    add_submenu_page('triboon', 'triboon', 'رپورتاژها', 'manage_triboon', 'triboon', 'triboon_handler');
    add_submenu_page('triboon', 'Options', 'تنظیمات', 'manage_triboon', 'triboon_options', 'option_handler');
}


function triboon_handler()
{
    update_all_reportages();
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    $table = new Triboon_List_Table();
    $table->prepare_items();
    if ('show' === $table->current_action()) {
        $reportage_id = sanitize_text_field($_REQUEST['reportage_id']);
        $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE reportage_id=" . $reportage_id);
        $post = get_post($thereportage->post_id);
        if ($post == null) {
            echo '<div class="notice notice-error is-dismissible" id="message"><p>این رپورتاژ از طریق پلاگین منتشر نشده است.</p></div>';
            return;
        }
        $link = get_post_permalink($post);
        echo("<script>location.href = '" . esc_url($link) . "'</script>");

    } else if ('preview' === $table->current_action() || 'edit' === $table->current_action()) {
        $reportage_id = sanitize_text_field($_REQUEST['reportage_id']);
        $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE reportage_id=" . $reportage_id);
        $post = get_post($thereportage->post_id);
        if ($thereportage->post_id != null && $post != null) {
            $post = get_post($thereportage->post_id);
        } else {
            $html_file = wp_triboon_images_handle(twp_get_content_file($thereportage))['html_file'];
            $args = array(
                'post_type' => 'triboon',
                'post_title' => $thereportage->title,
                'post_status' => 'future',
                'post_date' => twp_datetime_with_timezone($thereportage->preferred_publish_date),
                'post_content' => $html_file,
                'tags_input' => explode("/", $thereportage->tags),
                'post_category' => array(get_option('default-category')),
            );
            $post = wp_insert_post($args);
            $id = wp_triboon_images_handle(twp_get_content_file($thereportage))['featured_img_id'];
            if ($id != null) {
                set_post_thumbnail($post, $id);
            }
            $wpdb->update($table_name, array(
                'post_id' => $post,
            ), array('reportage_id' => $thereportage->reportage_id));
        }
        if ('edit' === $table->current_action()) {
            $link = get_edit_post_link($post);
        } else
            $link = get_preview_post_link($post);
        $link = str_replace('&amp;', '&', $link);
        if ($link == "") {
            echo '<div class="notice notice-error is-dismissible" id="message"><p> لطفا دوباره امتحان کنید </p></div>';
            return;
        }
        echo "<script>location.href = '" . str_replace('&#038;','&', esc_url($link)) . "'</script>";
    }

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <?php $table->views(); ?>
        <form id="triboon" method="GET">
            <input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page'])  ?>"/>
            <?php $table->display() ?>
        </form>
    </div>
    <?php
}

?>