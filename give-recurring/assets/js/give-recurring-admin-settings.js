/**
 * Give Admin Recurring Settings JS
 *
 * @description: Scripts functions in the admin Recurring Donations tab
 *
 */
var Give_Recurring_Vars;

jQuery(document).ready(function ($) {


    var Give_Admin_Recurring_Settings = {

        /**
         * Initialize
         */
        init: function () {

            Give_Admin_Recurring_Settings.toggle_fields();

            //@TODO: Janky code; needs clean up to be dynamic if we add other toggleable fields
            $('#recurring_send_renewal_reminders, #enable_subscription_cancelled_email, #recurring_send_expiration_reminders, #enable_subscription_receipt_email, #give_authorize_md5_hash_value_option').on('change', function () {
                Give_Admin_Recurring_Settings.toggle_fields();
            });


        },

        /**
         * Toggle Set Recurring Fields
         *
         * @description:
         */
        toggle_fields: function () {

            //@TODO: Make this more dynamic
            var subscription_receipt = $('#enable_subscription_receipt_email').prop('checked');

            if (subscription_receipt == true) {
                $('.cmb2-id-subscription-notification-subject, .cmb2-id-subscription-receipt-message').css('display', 'table-row');
            } else {
                $('.cmb2-id-subscription-notification-subject, .cmb2-id-subscription-receipt-message').hide();
            }

            //Cancellation Event Emails
            var email_cancelled = $('#enable_subscription_cancelled_email').prop('checked');
            if (email_cancelled == true) {
                $('.cmb2-id-subscription-cancelled-subject, .cmb2-id-subscription-cancelled-message').css('display', 'table-row');
            } else {
                $('.cmb2-id-subscription-cancelled-subject, .cmb2-id-subscription-cancelled-message').hide();
            }


            //Renewal Reminders
            var renewal_reminders = $('#recurring_send_renewal_reminders').prop('checked');

            if (renewal_reminders == true) {
                $('.cmb2-id-recurring-renewal-reminders').css('display', 'table-row');
            } else {
                $('.cmb2-id-recurring-renewal-reminders').hide();
            }

            //Expirations
            var expiration_reminders = $('#recurring_send_expiration_reminders').prop('checked');

            if (expiration_reminders == true) {
                $('.cmb2-id-recurring-expiration-reminders').css('display', 'table-row');
            } else {
                $('.cmb2-id-recurring-expiration-reminders').hide();
            }

            //Authorize.net MD5 Hash
            var authorize_hash_option = $('#give_authorize_md5_hash_value_option').prop('checked');
            if (authorize_hash_option == true) {
                $('.cmb2-id-give-authorize-md5-hash-value').css('display', 'table-row');
            } else {
                $('.cmb2-id-give-authorize-md5-hash-value').hide();
            }

        }

    };

    Give_Admin_Recurring_Settings.init();


});