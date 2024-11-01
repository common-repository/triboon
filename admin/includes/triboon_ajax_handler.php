<?php
require_once(__DIR__ . '/handle_requests.php');

function triboon_accept_reportage_ajax(){
    $triboon_reportage_id = sanitize_text_field($_POST['reportage_id']);
    $data = triboon_accept_reportage($triboon_reportage_id);
    if ($data["status"] == 200) {
        twp_set_reportage($triboon_reportage_id);
    }
    echo json_encode($data);
    wp_die();
}


function triboon_reject_reportage_ajax(){
    $triboon_reportage_id = sanitize_text_field($_POST['reportage_id']);
    $reason = sanitize_text_field($_POST['reason']);
    $data = triboon_reject_reportage($triboon_reportage_id, $reason);
    if ($data["status"] == 200) {
        twp_set_reportage($triboon_reportage_id);
    }
    echo json_encode($data);
    wp_die();
}

?>