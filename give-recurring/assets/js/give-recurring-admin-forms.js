/**
 * Give Admin Recurring JS
 *
 * @description: Scripts function in admin form creation (single give_forms post) screen
 *
 */
var Give_Recurring_Vars;

jQuery(document).ready(function ($) {


    var Give_Admin_Recurring = {

        /**
         * Initialize
         */
        init: function () {

            //Object vars
            Give_Admin_Recurring.recurring_option = $('select#_give_recurring');
            Give_Admin_Recurring.row_recurring_period = $('.give-recurring-period');
            Give_Admin_Recurring.row_recurring_times = $('.give-recurring-times');

            Give_Admin_Recurring.toggle_set_recurring_fields();
            Give_Admin_Recurring.toggle_multi_recurring_fields();
            Give_Admin_Recurring.recurring_repeatable_select();
            Give_Admin_Recurring.validate_times();
            Give_Admin_Recurring.validate_period();
            Give_Admin_Recurring.detect_email_access();

            //Set or Multi Toggle Options Reveal
            $('input[name="_give_price_option"]').on('change', function () {

                var set_or_multi = $('input[name="_give_price_option"]:checked').val();

                if (set_or_multi == 'set') {
                    Give_Admin_Recurring.toggle_set_recurring_fields();
                } else {
                    Give_Admin_Recurring.toggle_multi_recurring_fields();
                }


            });

            //Single-level Recurring Options Reveal
            $('select#_give_recurring').on('change', function () {

                var set_or_multi = $('input[name="_give_price_option"]:checked').val();

                if (set_or_multi == 'set') {
                    //Single level
                    Give_Admin_Recurring.toggle_set_recurring_fields();
                } else {
                    //Multi-level
                    Give_Admin_Recurring.toggle_multi_recurring_fields();
                }

            });


        },

        /**
         * Toggle Set Recurring Fields
         *
         * @description: Hides and shows Recurring fields for the "Set" donation option
         */
        toggle_set_recurring_fields: function () {

            var recurring_option = Give_Admin_Recurring.recurring_option.val();
            var set_or_multi = $('input[name="_give_price_option"]:checked').val();

            //Sanity check: ensure this is set
            if (set_or_multi !== 'set') {
                return false;
            }
            
            if (recurring_option == 'yes_admin') {
                //Show fields
                $('.give-recurring-row.give-hidden').show();
                //Hide checkbox opt-in option field
                $('.give-recurring-checkbox-option').hide();

            } else if (recurring_option == 'yes_donor') {
                //Show fields
                $('.give-recurring-row.give-hidden').show();
                //Show checkbox opt-in option field
                $('.give-recurring-checkbox-option').show();
            } else {
                $('.give-recurring-row.give-hidden').hide();
            }


        },

        /**
         * Toggle Multi-Level Recurring Fields
         *
         * @description: Hides and shows Recurring fields for the "Multi-level" donation option
         */
        toggle_multi_recurring_fields: function () {

            var set_or_multi = $('input[name="_give_price_option"]:checked').val();
            var recurring_option = Give_Admin_Recurring.recurring_option.val();

            //Sanity check: ensure this is multi
            if (set_or_multi !== 'multi') {
                return false;
            }

            if (recurring_option == 'yes_admin') {
                //Hide donor-recurring settings fields
                $('.give-recurring-row.give-hidden').hide();
                //Show admin-recurring settings fields
                $('.give-recurring-multi-el').show();
                //Toggle repeatable fields
                $('select[name$="[_give_recurring]"]').change();
                //Add classes for styling
                $('.cmb-type-levels-repeater-header, #_give_donation_levels_repeat').addClass('give-recurring-admin-choice');

            } else if (recurring_option == 'yes_donor') {

                $('.give-recurring-row.give-hidden').show();
                $('.give-recurring-multi-el').hide();
            }
            //Off: hide all
            else if (recurring_option == 'no') {

                $('.give-recurring-row.give-hidden').hide();
                $('.give-recurring-multi-el').hide();
                $('.cmb-type-levels-repeater-header, #_give_donation_levels_repeat').removeClass('give-recurring-admin-choice');

            }

        },

        /**
         * Update Recurring Fields
         * @description: Activates and deactivates recurring fields based on the user's selections
         * @param $this
         */
        update_recurring_fields: function ($this) {
            var val = $('option:selected', $this).val(),
                fields = $this.parents('.cmb-repeatable-grouping').find('select[name$="[_give_period]"], input[name$="[_give_times]"]');

            if (val == 'no') {
                fields.attr('disabled', true);
            } else {
                fields.attr('disabled', false);
            }

            $this.attr('disabled', false);

        },

        /**
         * Recurring Select
         * @description:
         */
        recurring_repeatable_select: function () {

            $('body').on('change', 'select[name$="[_give_recurring]"]', function () {
                Give_Admin_Recurring.update_recurring_fields($(this));
            });
            $('select[name$="[_give_recurring]"]').change();

            $('body').on('cmb2_add_row', function () {
                $('select[name$="[_give_recurring]"]').each(function (index, value) {
                    Give_Admin_Recurring.update_recurring_fields($(this));
                });

            });

        },

        /**
         * Validate Times
         *
         * @description: Used for client side validation of times set for various recurring gateways
         */
        validate_times: function () {

            var recurring_times = $('.give-time-field');

            //Validate times on times input blur (client side then server side)
            recurring_times.on('blur', function () {

                var time_val = $(this).val();
                var recurring_option = $('#_give_recurring').val();

                //Verify this is a recurring download first
                //Sanity check: only validate if recurring is set to Yes
                if (recurring_option == 'no') {
                    return false;
                }

                //Check if PayPal Standard is set & Validate times are over 1
                if (typeof Give_Recurring_Vars.enabled_gateways.paypal !== 'undefined' && time_val == 1) {

                    //Alert user of issue
                    alert(Give_Recurring_Vars.invalid_time.paypal);
                    //Refocus on the faulty input
                    $(this).focus();

                }

            });

        },

        /**
         * Validate Period
         * @description: Used for client side validation of period set for various recurring gateways
         */
        validate_period: function () {

            var recurring_period = $('.give-period-field');

            //Validate times on times input blur (client side then server side)
            recurring_period.on('blur', function () {


                var period_val = $(this).val();
                var recurring_option = $('#_give_recurring').val();

                //Verify this is a recurring download first
                //Sanity check: only validate if recurring is set to Yes
                if (recurring_option == 'no') {
                    return false;
                }

                //Check if WePay Standard is set & Validate times are over 1
                if (typeof Give_Recurring_Vars.enabled_gateways.wepay !== 'undefined' && period_val == 'day') {

                    //Alert user of issue
                    alert(Give_Recurring_Vars.invalid_period.wepay);
                    //Change to a valid value
                    $(this).val('week');
                    //Refocus on the faulty input
                    $(this).focus();

                }


            });

        },

        /**
         * Detect Email Access
         *
         * @description: Is Email Best Access on? If not, display message and hide register/login fields
         *
         * @since: v1.1
         */
        detect_email_access: function () {

            //Email Access Not Enabled & Recurring Enabled
            if (Give_Recurring_Vars.email_access !== 'on' && Give_Recurring_Vars.recurring_option !== 'no') {
                Give_Admin_Recurring.toggle_login_message('on');
            }

            $('select#_give_recurring').on('change', function () {

                var this_val = $(this).val();

                if (this_val !== 'no') {
                    Give_Admin_Recurring.toggle_login_message('on');
                } else {
                    Give_Admin_Recurring.toggle_login_message('off');
                }

            });

        },

        /**
         *
         * @param toggle_state
         */
        toggle_login_message: function (toggle_state) {
            if (toggle_state == 'on' && $('.login-required-td').length == 0) {
                //Hide appropriate fields
                $('.cmb2-id--give-logged-in-only, .cmb2-id--give-show-register-form .cmb-td').hide();
                //Add class for styles
                $('.cmb2-id--give-show-register-form').addClass('recurring-email-access-message');
                //Prepend message
                $('.cmb2-id--give-show-register-form .cmb-td').before(Give_Recurring_Vars.messages.login_required);
                //Uncheck login required
                $('#_give_logged_in_only').prop('checked', false);
            } else if (toggle_state == 'off') {
                //Hide appropriate fields
                $('.cmb2-id--give-logged-in-only, .cmb2-id--give-show-register-form .cmb-td').show();
                //Add class for styles
                $('.cmb2-id--give-show-register-form').removeClass('recurring-email-access-message');
                //Prepend message
                $('.login-required-td').remove();
            }

        }

    };


    Give_Admin_Recurring.init();


});