/**
 * Give Admin Recurring JS
 *
 * @description: Scripts function in admin form creation (single give_forms post) screen
 *
 */
var Give_Recurring_Vars;

jQuery(document).ready(function ($) {


    var Give_Admin_Recurring_Subscription = {

        /**
         * Initialize
         */
        init: function () {

            this.edit_expiration();
            this.edit_profile_id();
            this.confirm_cancel();
            this.confirm_delete();
            this.toggle_renewal_form();
            this.handle_status_change();

        },

        /**
         * Edit Subscription Text Input
         *
         * @since 1.2
         *
         * @description: Handles actions when a user clicks the edit or cancel buttons in sub details
         *
         * @param link object The edit/cancelled element the user clicked
         * @param input the editable field
         */
        edit_subscription_input: function (link, input) {

            //User clicks edit
            if (link.text() === Give_Recurring_Vars.action_edit) {
                //Preserve current value
                link.data('current-value', input.val());
                //Update text to 'cancel'
                link.text(Give_Recurring_Vars.action_cancel);
            } else {
                //User clicked cancel, return previous value
                input.val(link.data('current-value'));
                //Update link text back to 'edit'
                link.text(Give_Recurring_Vars.action_edit);
            }

        },

        /**
         * Edit Expiration
         *
         * @since 1.2
         */
        edit_expiration: function () {

            $('.give-edit-sub-expiration').on('click', function (e) {
                e.preventDefault();

                var link = $(this);
                var exp_input = $('input.give-sub-expiration');
                Give_Admin_Recurring_Subscription.edit_subscription_input(link, exp_input);

                //Toggle elements
                $('.give-sub-expiration').toggle();
                $('#give-sub-expiration-update-notice').slideToggle();
            });

        },

        /**
         * Edit Profile ID
         *
         * @since 1.2
         */
        edit_profile_id: function () {

            $('.give-edit-sub-profile-id').on('click', function (e) {
                e.preventDefault();

                var link = $(this);
                var profile_input = $('input.give-sub-profile-id');
                Give_Admin_Recurring_Subscription.edit_subscription_input(link, profile_input);

                //Toggle elements
                $('.give-sub-profile-id').toggle();
                $('#give-sub-profile-id-update-notice').slideToggle();
            });

        },


        /**
         * Toggle Set Recurring Fields
         */
        confirm_cancel: function () {

            $('input[name="give_cancel_subscription"]').on('click', function () {
                var response = confirm(Give_Recurring_Vars.confirm_cancel);
                //Cancel form submit if user rejects confirmation
                if (response !== true) {
                    return false;
                }
            });


        },

        /**
         * Confirm Sub Delete
         */
        confirm_delete: function () {

            $('.give-delete-subscription').on('click', function (e) {

                if (confirm(Give_Recurring_Vars.delete_subscription)) {
                    return true;
                }

                return false;
            });

        },

        /**
         * Toggle Manual Renewal Form
         */
        toggle_renewal_form: function () {


            $('.give-add-renewal').on('click', function () {

                $('table.renewal-payments tfoot').toggle();

            });

        },

        /**
         * Admin Status Select Field Change
         *
         * @description: Handle status switching
         * @since: 1.0
         */
        handle_status_change: function () {

            //When sta
            $('select#subscription_status').on('change', function () {

                var status = $(this).val();

                $('.give-donation-status').removeClass(function (index, css) {
                    return (css.match(/\bstatus-\S+/g) || []).join(' ');
                }).addClass('status-' + status);


            });

        }

    };

    Give_Admin_Recurring_Subscription.init();


});