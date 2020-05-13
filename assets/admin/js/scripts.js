/**
 * Admin Scripts
 */

(function ($, window, document, pluginObject) {
    "use strict";

    $(document).on('change', '.woocngr_transport_agreement input[type="radio"]', function () {

        let agreementID = $('.woocngr_transport_agreement input[type="radio"]:checked').val();

        $('.woocngr_product').css('display', 'none');
        if (agreementID.length !== 0) {
            $('.woocngr_product.agreement_' + agreementID).css('display', 'table-row')
        }
    });


    $(document).on('click', '.woocngr-send-details', function () {

        let sendButton = $(this),
            orderID = sendButton.data('order_id'),
            htmlPrev = sendButton.html();

        sendButton.html(pluginObject.sendingText);

        $.ajax({
            type: 'POST',
            context: this,
            url: pluginObject.ajaxURL,
            data: {
                'action': 'woocngr_send_details',
                'order_id': orderID,
            },
            success: function (response) {

                if (response.success) {
                    sendButton.html(pluginObject.sendingSuccessText);
                    setTimeout(function () {
                        sendButton.html(htmlPrev);
                        location.reload();
                    }, 500);
                }
            }
        });

        return false;
    });


})(jQuery, window, document, woocngr_object);







