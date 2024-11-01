<?php
function twp_upload_image_in_media($image)
{

    include_once(ABSPATH . 'wp-admin/includes/image.php');
    $uniq_name = date('dmY') . '' . (int)microtime(true);
    $filename = $uniq_name;
    $basename = basename($image);
    $filename = $filename . $basename;
    $uploaddir = wp_upload_dir();
    $uploadfile = $uploaddir['path'] . '/' . $filename;
    $contents = file_get_contents($image);
    file_put_contents($uploadfile, $contents);
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    return array('file' => $uploadfile, 'attachment' => $attachment);
}

function wp_triboon_images_handle($html_content)
{
    preg_match_all('/<img[^>]+>/i', $html_content, $result);
    $featured_image_isset = false;
    $featured_image_id = null;
    foreach ($result[0] as $img) {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($img);
        libxml_use_internal_errors($internalErrors);
        $xpath = new DOMXPath($doc);
        $src = $xpath->evaluate("string(//img/@src)");
        $src = str_replace('\\"', '', $src);
        $data = twp_upload_image_in_media($src);
        $attach_id = wp_insert_attachment($data['attachment'], $data['file']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $data['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        if ($featured_image_isset == false) {
            $featured_image_id = $attach_id;
            $featured_image_isset = true;
        }
        $html_content = str_replace($src, wp_get_attachment_url($attach_id), $html_content);
    }
    return array('html_file' => $html_content, 'featured_img_id' => $featured_image_id);
}

function get_triboon_logo()
{
    $id = get_option('triboon_logo');
    if ($id != null && get_attached_file($id))
        return wp_get_attachment_url($id);

    $data = twp_upload_image_in_media(__DIR__ . '/logo/triboon.png');
    $attach_id = wp_insert_attachment($data['attachment'], $data['file']);
    update_option('triboon_logo', $attach_id);
    return wp_get_attachment_url($attach_id);
}

function twp_get_content_file($reportage)
{
    $content_file = $reportage->content_file;
    $response = wp_remote_get($content_file);
    return wp_remote_retrieve_body($response);
}

?>