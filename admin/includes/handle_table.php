<?php
function twp_update_data($reportage, $tags_as_text)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    if ($reportage['state'] == 'publisher_canceled'){
        $wpdb->delete($table_name, array(
            'reportage_id' => $reportage['id'],
        ));
        return;
    }
    $id_check = $reportage['id'];
    $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE reportage_id=" . $id_check);
    $previous_state = $thereportage->state;
    if ($reportage['state'] == 'publisher_pending')
        $fa_state = 'بررسی نشده';
    else
        $fa_state = $reportage['fa_state'];
    $wpdb->update($table_name, array(
        'title' => $reportage['title'],
        'state' => $reportage['state'],
        'preferred_publish_date' => $reportage['preferred_publish_date'],
        'pricing_plan' => $reportage['pricing_plan'],
        'description' => $reportage['description'],
        'content_file' => $reportage['content_file'],
        'lead_content' => $reportage['lead_content'],
        'fa_state' => $fa_state,
        'url' => $reportage['url'],
        'tags' => $tags_as_text,
        'primary_image' => $reportage['primary_image'],
        'subject' => $reportage['subject'],
        'attachments' => $reportage['attachments'],
    ), array('reportage_id' => $thereportage->reportage_id));
    if (($thereportage->state == "publisher_pending" && $previous_state == "publisher_accepted") ||
        ($thereportage->state == "publisher_accepted" && $previous_state == "publisher_pending")) {
        $post_id = $thereportage->post_id;
        if ($post_id != null) {
            $args = array(
                'ID' => $post_id,
                'post_type' => 'triboon',
                'post_title' => $thereportage->title,
                'post_status' => 'draft',
                'post_date' => twp_datetime_with_timezone($thereportage->preferred_publish_date),
                'post_content' => twp_get_content_file($thereportage),
                'tags_input' => explode("/", $thereportage->tags)
            );
            wp_update_post($args);
        }
    }

}

function triboon_add_data($reportage)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'triboon';
    $id_check = $reportage['id'];
    $old_reportage = $wpdb->get_var('SELECT reportage_id FROM ' . $table_name . ' WHERE reportage_id=' . $id_check . ' ;');
    $tags_as_text = '';
    foreach ($reportage['tags'] as $tag) {
        $tags_as_text = $tags_as_text . $tag . "/";
    }

    if ($old_reportage != null && $old_reportage == $id_check){
        twp_update_data($reportage, $tags_as_text);
        return;
    }
    if ($reportage['state'] == 'publisher_canceled')
        return;
    if ($reportage['state'] == 'publisher_pending')
        $fa_state = 'بررسی نشده';
    else {
        $fa_state = $reportage['fa_state'];
    }

        $wpdb->insert($table_name, array(
        'reportage_id' => $reportage['id'],
        'title' => $reportage['title'],
        'state' => $reportage['state'],
        'preferred_publish_date' => $reportage['preferred_publish_date'],
        'description' => $reportage['description'],
        'pricing_plan' => $reportage['pricing_plan'],
        'content_file' => $reportage['content_file'],
        'lead_content' => $reportage['lead_content'],
        'fa_state' => $fa_state,
        'url' => $reportage['url'],
        'tags' => $tags_as_text,
        'primary_image' => $reportage['primary_image'],
        'subject' => $reportage['subject'],
        'attachments' => $reportage['attachments'],
    ));

}

function create_triboon_table()
{
    global $wpdb;
    global $triboon_db_version;
    $table_name = $wpdb->prefix . 'triboon';
//    $drop_sql = "DROP TABLE IF EXISTS " . $table_name . " ;";
//    $wpdb->query($drop_sql);
    add_option('check_new_reportage', 0);
    add_option('triboon_logo', null);
    add_option('notif_check', null);

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
      reportage_id INT NOT NULL,
      post_id INT,
      title VARCHAR(250) NOT NULL,
      pricing_plan VARCHAR(250),
      state VARCHAR(30) NOT NULL,
      fa_state VARCHAR(30) NOT NULL,
      preferred_publish_date datetime,
      description text,
      content_file text,
      lead_content text,
      url text NULL,
      attachments text NULL,
      tags text,
      primary_image text,
      subject VARCHAR(50),
      PRIMARY KEY  (reportage_id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    add_option('triboon_db_version', $triboon_db_version);

}

function remove_triboon_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    delete_option('triboon_db_version');
    delete_option('triboon_wp_token_option');   
    unregister_post_type("triboon");

}

function triboon_update_db_check()
{
    global $triboon_db_version;
    if (get_site_option('triboon_update_db_check') != $triboon_db_version) {
        create_triboon_table();
    }
}


function get_triboon_reportage_object($post_id){
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    $thereportage = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE post_id=" . $post_id);
    return $thereportage;
}

?>