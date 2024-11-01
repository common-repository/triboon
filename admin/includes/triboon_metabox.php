<?php
require_once(__DIR__ . '/handle_table.php');
require_once(__DIR__ . '/reject_modal.php');



function triboon_add_custom_metabox() {
	add_meta_box(
		'triboon_metabox',
		'تریبون',
		'show_triboon_publish_metabox',
		'triboon',
		'side',
		'high'
	);
}

function show_triboon_publish_metabox( $post, $args = array() ) {
	$reportage = get_triboon_reportage_object($post->ID);
    $preferred_publish_date = twp_datetime_with_timezone($reportage->preferred_publish_date);
    $preferred_publish_date = new DateTime($preferred_publish_date);
    $now = new DateTime();
    $help_text = "";
    $is_str = false;
    if ($reportage->state == "publisher_accepted" && ($now >= $preferred_publish_date)) {
        $is_str = true;
        $help_text = "این رپورتاژ تایید شده است. با استفاده از دکمه انتشار می توانید منتشر کنید.";
    }
    else if ($reportage->state == "publisher_accepted" && ($now < $preferred_publish_date)) {
        $is_str = true;
        $help_text = "این رپورتاژ تایید شده است و در زمان مورد نظر منتشر می شود.";
    }
    else if ($reportage->state == "publisher_rejected") {
        $is_str = true;
        $help_text = "این رپورتاژ رد شده است.";
    }
    else if ($reportage->state == "publisher_published") {
        $is_str = true;
        $help_text = "این رپورتاژ منتشر شده است.<br> <a href=\"" . $reportage->url . "\"> مشاهده </a>";
    }
    else if ($reportage->state == "publisher_pending")
        $link = 'https://panel.triboon.net/publisher/reportages/available?filter=id,'.$reportage->reportage_id.';';
    twp_get_reject_modal();

    ?>
    <div style="justify-content:center; align-items:center;">
    <?php if ($reportage->state == "publisher_pending") {?>
            <div style="display:flex;">
            <span id="triboon_spinner" class="spinner"></span>
            <button name="triboon_accept_btn" style="color: white; background-color:#128f76;" class="button  button-large" id="<?php echo esc_attr($reportage->reportage_id) ?>"> <?php  echo esc_attr(__( 'تایید رپورتاژ' )); ?></button>
            <div style="min-width:1rem;"></div>
            <button name="triboon_reject_btn" style="color: white; background-color:#8F000E" class="button  button-large" id="<?php echo esc_attr($reportage->reportage_id) ?>"> <?php echo esc_attr(__( 'رد رپورتاژ')); ?></button>
            </div>
            
    <?php } ?>
    <div>
        <h4 style="text-align:center;"><?php
            if ($is_str)
                echo esc_attr($help_text);
            else {
                echo "<a target='_blank' href=".esc_url($link)."> مشاهده در پنل تریبون </a>";
                }
            ?></h4>
    </div>
    </div>
    <?php
}
?>