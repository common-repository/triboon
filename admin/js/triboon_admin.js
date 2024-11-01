(function ($, window, document) {
    'use strict';
    // execute when the DOM is ready
    $(document).ready(function () {
        var acceptBtn = $('[name=triboon_accept_btn]')
        var rejectBtn = $('[name=triboon_reject_btn]')
        var rejectModal = $('#triboon_reject_modal')
        var closeModalBtn = $('#triboon_closebtn')
        var submitRejectBtn = $('#triboon_submit_reject');
        var query = new URLSearchParams(window.location.search);

        acceptBtn.on('click', function (e) {
            e.preventDefault();
            if (acceptBtn.hasClass('disabled')) {
                return
            }
            $('#triboon_spinner').css('visibility', 'visible');
            acceptBtn.addClass("disabled");
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'triboon_accept_reportage',
                    reportage_id: acceptBtn.attr('id'),
                },
                success: function (data) {
                    console.log(data);
                    data = JSON.parse(data);
                    $('#triboon_spinner').css('visibility', 'hidden');
                    if (data.status === 200) {
                        query.append("triboon_success", "رپورتاژ با موفقیت تایید شد.");
                        window.location.href = "?" + query.toString();

                    } else if (data.status === 400) {
                        var error = data.parsed_json.error;
                        query.append("triboon_error", error);
                        window.location.href = "?" + query.toString();

                    } else {
                        query.append("triboon_error", "خطا در برقراری ارتباط ، لطفا مجددا تلاش بفرمایید.");
                        window.location.href = "?" + query.toString();
                    }
                },
                error: function (data) {
                    query.append("triboon_error", "خطا در برقراری ارتباط ، لطفا مجددا تلاش بفرمایید.");
                    window.location.href = "?" + query.toString();
                }
            });
        });
        rejectBtn.on('click', function (e) {
            e.preventDefault();
            rejectModal.css('display', 'block');
            window.rejectReportageID = rejectBtn.attr('id');
        });

        rejectModal.on('click', function (e) {
            if (e.target === e.currentTarget)
                rejectModal.css.display = "none";
        });

        closeModalBtn.on('click', function (e) {
            rejectModal.css('display', 'none')
        });

        submitRejectBtn.on('click', function (e) {
            e.preventDefault();
            if (submitRejectBtn.hasClass('disabled')) {
                return
            }
            submitRejectBtn.addClass("disabled");
            // jQuery post method, a shorthand for $.ajax with POST
            $.ajax({
                url: ajax_object.ajaxurl, // this is the object instantiated in wp_localize_script function
                type: 'POST',
                data: {
                    action: 'triboon_reject_reportage', // this is the function in your functions.php that will be triggered
                    reportage_id: window.rejectReportageID,
                    reason: $('#triboon_txt').val()
                },
                success: function (data) {
                    console.log(data);
                    data = JSON.parse(data);
                    if (data.status === 200) {
                        query.append("triboon_success", "رپورتاژ با موفقیت رد شد.");
                        // window.location.href = "?" + query.toString();

                    } else if (data.status === 400) {
                        var error = data.parsed_json.error;
                        query.append("triboon_error", error);
                        // window.location.href = "?" + query.toString();

                    } else {
                        query.append("triboon_error", "خطا در برقراری ارتباط ، لطفا مجددا تلاش بفرمایید.");
                        // window.location.href = "?" + query.toString();
                    }
                },
                error: function (data) {
                    query.append("triboon_error", "خطا در برقراری ارتباط ، لطفا مجددا تلاش بفرمایید.");
                    // window.location.href = "?" + query.toString();
                }
            });

        });
        var reject_modal = document.getElementById("triboon_reject_modal");
        window.onclick = function (event) {
            if (event.target == reject_modal) {
                reject_modal.style.display = "none";
            }
        }

        var page_query = new URLSearchParams(window.location.search);
        var triboon_message = page_query.get("triboon_error");
        if (triboon_message) {
            console.log(triboon_message.toString());
            $('<div class="notice notice-error is-dismissible" id="message"><p>' + triboon_message + '</p></div>').insertBefore('#titlediv')
        }

    });
}(jQuery, window, document));