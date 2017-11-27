/**
 * Give Frontend Recurring JS
 *
 * @description: Scripts function in frontend form
 *
 */
var Give_Recurring_Vars;

jQuery(document).ready(function ($) {

	var doc = $(document);

	var Give_Recurring = {

		/**
		 * Initialize
		 */
		init: function () {

			Give_Recurring.confirm_subscription_cancellation();
			Give_Recurring.conditional_account_creation();

		},

		/**
		 * Toggle Set Recurring Fields.
		 */
		conditional_account_creation: function () {

			//Only w/o Email Access Enabled
			if (Give_Recurring_Vars.email_access == 'on') {
				return false;
			}

			//If user is already logged in, bail.
			if ($('body').hasClass('logged-in')) {
				return false;
			}

			//On Page Load: When page loads loop through each form and show hide fields.
			$('form[id^=give-form].give-recurring-form').each(function () {
				Give_Recurring.toggle_register_login_fields_onload($(this));
			});

			//On Gateway Load too...
			doc.on('give_gateway_loaded', function (ev, response, form_id) {
				Give_Recurring.toggle_register_login_fields_onload($('.give-recurring-form#' + form_id));
			});

			//When a level is clicked then toggle account creation based on whether it's recurring or not.
			$('.give-donation-level-btn, .give-radio-input-level, .give-select-level > option').on('click touchend', function () {
				Give_Recurring.toggle_admin_choice_register_fields($(this));
			});

			//Donor's choice checkbox toggle required fields.
			$('.give-recurring-donors-choice > input').on('click touchend', function () {
				Give_Recurring.toggle_donor_choice_register_fields($(this));
			});


		},

		/**
		 * Toggle the register and login fields on load.
		 *
		 * @param form
		 */
		toggle_register_login_fields_onload: function (form) {

			var selected_level = form.find('.give-donation-levels-wrap .give-default-level');
			var admin_choice = form.find('.give-recurring-admin-choice');

			//No action needed if checkbox.
			if (admin_choice.length > 0) {
				return false;
			}

			//Is Form Recurring? If not, bail.
			if (!form.hasClass('give-recurring-form')) {
				return false;
			}

			//Check for select option
			if (selected_level.length == 0) {
				selected_level = form.find('.give-select-level > .give-default-level');
			}

			var donors_choice_checkbox = form.find('.give-recurring-donors-choice input');

			//If recurring show register/login fields
			if (selected_level.hasClass('give-recurring-level') || donors_choice_checkbox.prop('checked')) {
				Give_Recurring.show_fields(form);
			} else {
				Give_Recurring.hide_fields(form);
			}

		},


		/**
		 * Toggle the register and login fieldsets for multi-level forms.
		 *
		 * @param level
		 */
		toggle_admin_choice_register_fields: function (level) {

			var form = level.parents('form[id^=give-form]');
			var is_admin_choice = form.parents('div.give-form-wrap').hasClass('give-recurring-form-admin');

			//Only applicable to admin choice.
			if (!is_admin_choice) {
				return;
			}

			//Is this a recurring level.
			if (level.hasClass('give-recurring-level')) {
				Give_Recurring.show_fields(form);
			} else {
				Give_Recurring.hide_fields(form);
			}
		},

		/**
		 * Toggle the register and login fieldsets for multi-level forms.
		 *
		 * @param checkbox The donor's choice checkbox.
		 */
		toggle_donor_choice_register_fields: function (checkbox) {
			var form = checkbox.parents('form[id^=give-form]');

			if (checkbox.prop('checked')) {
				Give_Recurring.show_fields(form);
			} else {
				Give_Recurring.hide_fields(form);
			}
		},

		/**
		 * Show Fields
		 *
		 * @since 1.2.3
		 * @param form
		 */
		show_fields: function (form) {
			var login_fieldset = form.find('.give-login-account-wrap');
			var register_fieldset = form.find('[id^=give-register-account-fields]');
			var register_fields = register_fieldset.find('input');
			var hidden_register = form.find('[name=give-purchase-var]');

			//Add required attribute.
			$(register_fields).attr('required', 'required');

			//Show fields.
			login_fieldset.show();
			register_fieldset.show();
			hidden_register.val('needs-to-register');
		},

		/**
		 * Hide fields.
		 *
		 * @since 1.2.3
		 * @param form
		 */
		hide_fields: function (form) {
			var login_fieldset = form.find('.give-login-account-wrap');
			var register_fieldset = form.find('[id^=give-register-account-fields]');
			var register_fields = register_fieldset.find('input');
			var hidden_register = form.find('[name=give-purchase-var]');

			//Remove required attribute.
			$(register_fields).removeAttr('required');

			//Hide fields.
			login_fieldset.hide();
			register_fieldset.hide();
			hidden_register.val('');
		},

		/**
		 * Confirm Cancellation
		 *
		 * @description:
		 */
		confirm_subscription_cancellation: function () {

			$('.give-cancel-subscription').on('click touchend', function (e) {
				var response = confirm(Give_Recurring_Vars.messages.confirm_cancel);
				//Cancel form submit if user rejects confirmation
				if (response !== true) {
					return false;
				}
			});

		}


	};

	Give_Recurring.init();


});