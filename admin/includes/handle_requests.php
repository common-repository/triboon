<?php
$GLOBALS['pre_link'] = 'https://api.triboon.net';

function twp_get_publisher_reportage_list($url)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    global $error_shown;
    $token = get_option('triboon_wp_token_option');
    $header_token = 'api-key ' . trim($token);
    $args = array('headers' => array('Authorization' => $header_token));
    $response = wp_remote_get($url, $args);
    if (wp_remote_retrieve_response_code($response) != 200) {
        if ($error_shown == false) {
            echo '<div class="notice notice-error is-dismissible" id="message"><p>خطا در برقراری ارتباط با سرور</p></div>';
            $error_shown = true;
        }
        return;
    }
    $parsed_json = json_decode(wp_remote_retrieve_body($response), true);
    $reportages = $parsed_json['results'];
    foreach ($reportages as $reportage) {
        triboon_add_data($reportage);
    }
    update_option('notif_check', count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s OR state=%s", "publisher_pending", "publisher_accepted"))));

}

function twp_set_reportage($reportage_id){
    global $wpdb;
    $table_name = $wpdb->prefix . 'triboon';
    global $error_shown;
    $token = get_option('triboon_wp_token_option');
    $url = $GLOBALS['pre_link'] . '/external/wp/reportages/'.$reportage_id.'/';
    $header_token = 'api-key ' . trim($token);
    $args = array('headers' => array('Authorization' => $header_token));
    $response = wp_remote_get($url, $args);
    if (wp_remote_retrieve_response_code($response) != 200) {
        if ($error_shown == false) {
            echo '<div class="notice notice-error is-dismissible" id="message"><p>خطا در برقراری ارتباط با سرور</p></div>';
            $error_shown = true;
        }
        return;
    }
    $report = json_decode(wp_remote_retrieve_body($response), true);
    triboon_add_data($report);
    update_option('notif_check', count($wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state=%s OR state=%s", "publisher_pending", "publisher_accepted"))));

}

function update_all_reportages()
{
    global $error_shown;
    $error_shown = false;
    $url = $GLOBALS['pre_link'] . '/external/wp/reportages/?limit=1000';
    twp_get_publisher_reportage_list($url);

}

function publish_triboon_post_request_handle($post_id, $reportage_id)
{
    $url = $GLOBALS['pre_link'] .'/external/wp/reportages/' . $reportage_id . '/publish';
    $token = get_option('triboon_wp_token_option');
    $link = get_post_permalink($post_id);;
    $header_token = 'api-key ' . trim($token);
    $args = array(
        'headers' => array('Authorization' => $header_token,
        'Content-Type' => 'application/json'),
        'body' => json_encode(array('url' => $link)),
        'method'      => 'POST',
        'data_format' => 'body');
    $response = wp_remote_post($url, $args);
    $parsed_json = json_decode(wp_remote_retrieve_body($response), true);
    error_log(wp_remote_retrieve_response_code($response));

    if (wp_remote_retrieve_response_code($response) == 200) {
        error_log(242423);
        echo '<div class="notice notice-success is-dismissible" id="message"><p>رپورتاژبا موفقیت منتشر شد.</p></div>';
    } else {
        foreach ($parsed_json as $error) {
            wp_delete_post($post_id,true);
            if (is_array($error))
                echo '<div class="notice notice-error is-dismissible" id="message"><p>' . esc_attr($error[0]) . '</p></div>';
            else
                echo '<div class="notice notice-error is-dismissible" id="message"><p>' . esc_attr($error) . '</p></div>';

        }
    }
    twp_set_reportage($reportage_id);
}


function triboon_accept_reportage($reportage_id) {
    $url = $GLOBALS['pre_link'] .'/external/wp/reportages/' . $reportage_id . '/confirm';
    $token = get_option('triboon_wp_token_option');
    $header_token = 'api-key ' . trim($token);
    $args = array('headers' => array('Authorization' => $header_token));
    $response = wp_remote_post($url, $args);
    $parsed_json = json_decode(wp_remote_retrieve_body($response), true);
    twp_set_reportage($reportage_id);
    return $parsed_json;
}

function triboon_reject_reportage($reportage_id, $reason) {
    $url = $GLOBALS['pre_link'] .'/external/wp/reportages/' . $reportage_id . '/reject';
    $token = get_option('triboon_wp_token_option');
    $header_token = 'api-key ' . trim($token);
    $args = array(
        'headers' => array('Authorization' => $header_token, 'Content-Type' => 'application/json'),
        'body' => json_encode(array('rejection_description' => $reason)),
        'method'      => 'POST',
        'data_format' => 'body',
    );
    $response = wp_remote_post($url, $args);
    $parsed_json = json_decode(wp_remote_retrieve_body($response), true);
    twp_set_reportage($reportage_id);
    return $parsed_json;
}



function twp_get_reject_reasons()
{
    $reject_reasons = $GLOBALS['pre_link'] .'/external/wp/reject-reasons';
    $token = get_option('triboon_wp_token_option');
    $header_token = 'api-key ' . trim($token);
    $args = array('headers' => array('Authorization' => $header_token));
    $response = wp_remote_get($reject_reasons, $args);
    $rej = wp_remote_retrieve_body($response);
    $rej = substr($rej, 1, strlen($rej) - 2);
    $arr = explode(",", $rej);
    $arr2 = array();
    foreach ($arr as $txt) {
        $txt = substr($txt, 1, strlen($txt) - 2);
        array_push($arr2, $txt);
    }
    return $arr2;
}
?>