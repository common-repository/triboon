<?php

    function twp_get_reject_modal(){
        ?>
    <div id="triboon_reject_modal" class="triboon-modal">
        <div class="triboon-modal-content">
            <div class="triboon-modal-header">
                <span class="triboon-close" id="triboon_closebtn">&times;</span>
                <h2>دلیل رد رپورتاژ</h2>
            </div>
            <div class="triboon-modal-body">
                <!--                <input class="input" name="دلیل رد" id="txt"/>-->
                <label>دلیل رد خود را وارد کنید:</label>
                <select class="triboon-input" id="triboon_txt">
                    <option value="">---</option>
                    <?php
                    $array = twp_get_reject_reasons();
                    foreach ($array as $key):
                        echo '<option value="' . esc_html($key) . '">' . esc_html($key) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="triboon-modal-footer">
                <button class="triboon_button" id="triboon_submit_reject">ثبت</button>
            </div>
        </div>

    </div>
    <?php
}

?>