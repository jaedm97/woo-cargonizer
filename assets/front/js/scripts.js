/**
 * Front Script
 */

(function ($, window, document, pluginObject) {
    "use strict";

    $(document).on('update_checkout', function () {

        let fieldWrap = $('.woocngr_pickup_address_wrap'),
            zipCodeField = $('#billing_postcode'),
            countryField = $('#billing_country'),
            zipCode = zipCodeField.val(),
            countryCode = countryField.find(":selected").val();

        $.ajax({
            type: 'POST',
            context: this,
            url: pluginObject.ajaxURL,
            data: {
                'action': 'woocngr_update_pickup',
                'zipcode': zipCode,
                'country': countryCode,
            },
            success: function (response) {
                if (response.success) {
                    fieldWrap.html(response.data);
                }
            }
        });
    });

})(jQuery, window, document, woocngr_object);







