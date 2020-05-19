/**
 * Admin Scripts
 */

(function ($, window, document, pluginObject) {
    "use strict";


    $(document).on('change', '.woocngr_shi_product_selection select', function () {

        let productSelection = $(this), newServiceSelections = [],
            selectedProduct = productSelection.find(":selected").val().split('-'),
            productID = selectedProduct[0],
            servicesData = JSON.parse(productSelection.parent().find('.services_data').html().replace(/'/g, "")),
            targetClass = productSelection.attr('id'),
            fieldName = targetClass.replace('woocngr_shi_product_', 'woocngr_shi_services_'),
            fieldSet = $('tr.' + targetClass).find('fieldset');

        $.each(servicesData, function (product_id, product_services) {
            if (product_id === productID) {
                $.each(product_services, function (key, value) {
                    let uniqueIdenfier = key + '_' + Math.floor(Math.random() * 1000);
                    newServiceSelections.push('<label for="woocngr_shi_services_' + uniqueIdenfier + '">' +
                        '<input type="checkbox" id="woocngr_shi_services_' + uniqueIdenfier + '" name="' + fieldName + '[]" value="' + key + '">' +
                        value + '</label>');
                });
            }
        });

        fieldSet.html(newServiceSelections.join('<br>'));
    });


    $(document).on('click', '.woocngr-btn-send', function () {

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

    $(document).on('click', '.woocngr-btn-send', function () {

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


    $(document).on('click', '.woocngr-popup-send', function () {

        let sendButton = $(this),
            orderID = sendButton.data('order_id'),
            popupBox = sendButton.parent().parent(),
            htmlPrev = sendButton.html();

        sendButton.html(pluginObject.sendingText);

        $.ajax({
            type: 'POST',
            context: this,
            url: pluginObject.ajaxURL,
            data: {
                'action': 'woocngr_override_send',
                'order_id': orderID,
                'transport_product': popupBox.find('#woocngr_so_transport_product').val(),
                'package': popupBox.find('#woocngr_so_package').val(),
                'weight': popupBox.find('#woocngr_so_weight').val(),
                'length': popupBox.find('#woocngr_so_length').val(),
                'width': popupBox.find('#woocngr_so_width').val(),
                'height': popupBox.find('#woocngr_so_height').val(),
            },
            success: function (response) {
                sendButton.html(response.success ? pluginObject.sendingSuccessText : response.data);
                popupBox.find('[id^=woocngr_]').each(function () {
                    $(this).val('');
                });
                sendButton.html(htmlPrev);
            }
        });

        return false;
    });


    $(document).on('click', '.woocngr-popup-cancel', function () {
        $(this).parent().parent().parent().fadeOut();
    });


    $(document).on('click', '.woocngr-popup-opener', function () {
        $('.woocngr-popup-container.' + $(this).data('target')).fadeIn();
    });


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







