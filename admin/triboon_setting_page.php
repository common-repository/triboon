<?php
function option_handler()
{
    if (isset($_POST['submit'])) {
         update_option('triboon_wp_token_option', sanitize_text_field($_POST["token"]));
         if($_POST['default-category']){
             update_option('default-category', sanitize_text_field($_POST["default-category"]));
         }
         else{
             update_option('default-category', '-1');
         }

        echo '<div class="notice notice-success is-dismissible" id="message"><p>اطلاعات با موفقیت ثبت شد.</p></div>';
    }
    $token = get_option('triboon_wp_token_option');
    $def_category = get_option('default-category');
    ?>

    <div class="triboon-container">
        <form class="triboon-settings" method='post'>
            <td>
                <div id='token'>
                    <span>کلید دسترسی:</span>

                    <input type='text' size='50' name='token' value="<?php echo $token ?>"/>

                </div>
                <div id='token'>
                <span>دسته پیش فرض:</span>
                <select class="triboon-input" name="default-category">
                    <?php
                    $args = array(
                        'hide_empty' => false,
                    );
                    $array = get_categories($args);
                    if ($def_category != '-1'){
                        $cat = get_category((int)$def_category);
                        echo '<option value="' . esc_html($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                    }
                    echo '<option value="">---بدون پیش فرض---</option>';
                    foreach ($array as $key):
                        echo '<option value="' . esc_html($key->term_id) . '">' . esc_html($key->name) . '</option>';
                    endforeach;
                    ?>
                </select>
                </div>
            </td>
            <td>
                <input type='submit' class="button" name='submit' value='ثبت اطلاعات'/>
            </td>
        </form>
    <div>

    <?php

}
?>