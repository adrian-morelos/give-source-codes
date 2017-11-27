=== Give - Recurring Donations ===
Contributors: wordimpress, dlocc, ramiy
Tags: donations, donation, ecommerce, e-commerce, fundraising, fundraiser, paymill, gateway
Requires at least: 4.2
Tested up to: 4.7
Stable tag: 1.2.3
License: GPLv3
License URI: https://opensource.org/licenses/GPL-3.0

Create powerful subscription based donations with the Give Recurring Donation Add-on.

== Description ==

This plugin requires the Give plugin activated to function properly. When activated, it adds the ability to accept recurring (subscription) donations to various payment gateways such as PayPal Standard, Stripe, PayPal Pro, and more.

== Installation ==

= Minimum Requirements =

* WordPress 4.2 or greater
* PHP version 5.3 or greater
* MySQL version 5.0 or greater
* Some payment gateways require fsockopen support (for IPN access)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of Give, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Give" and click Search Plugins. Once you have found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading our donation plugin and uploading it to your server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.2.3 =
* New: Added functionality to require that Give core be active in order to use the plugin - https://github.com/WordImpress/Give-Recurring-Donations/issues/301
* Fix: Logs filling with "Error Processing IPN Transaction" incorrectly for non-recurring payments - https://github.com/WordImpress/Give-Recurring-Donations/issues/293
* Fix: Refactored JS for how the recurring checkbox toggle displays required fields. - https://github.com/WordImpress/Give-Recurring-Donations/pull/303
* Fix: Authorize.net Subscription names need to be limited to 50 characters. - https://github.com/WordImpress/Give-Recurring-Donations/issues/298

= 1.2.2 =
* New: Pass the billing address information to Authorize.net about the donor when creating the subscription if present - https://github.com/WordImpress/Give-Recurring-Donations/issues/271
* New: Better error reporting for Authorize.net - https://github.com/WordImpress/Give-Recurring-Donations/issues/271
* New: Method to confirm whether the Payflow recurring feature is enabled at the gateway to better ensure the subscription is created successfully - https://github.com/WordImpress/Give-Recurring-Donations/issues/288
* New: Subscription status indicator icon added to Donations > Subscriptions > Subscription Details page - https://github.com/WordImpress/Give-Recurring-Donations/issues/132
* Fix: Intermittent issue with the donor's subscription cancel option displaying properly due to an issue with the PayPal Payflow gateway cancel logic - https://github.com/WordImpress/Give-Recurring-Donations/issues/260
* Fix: Resolved translation strings without textdomains or incorrect text domains for better translations - https://github.com/WordImpress/Give-Recurring-Donations/issues/275

= 1.2.1 =
* Fix: License activation notice would improperly display even though the license was activated when the admin viewed the Recurring Donations settings tab - https://github.com/WordImpress/Give-Recurring-Donations/issues/265

= 1.2 =
* New: Support for PayPal Payments Pro (Payflow) - https://github.com/WordImpress/Give-Recurring-Donations/issues/256
* New: Support for the PayPal REST API - https://github.com/WordImpress/Give-Recurring-Donations/issues/224
* New: The ability to filter subscriptions by status under Donations > Subscriptions
* New: The ability to edit a number of subscription detail fields including profile ID, expiration date, and subscription status
* New: Improved UI for [give_subscriptions] shortcode and also customizable attributes - https://github.com/WordImpress/Give-Recurring-Donations/issues/143
* New: Filter "give_recurring_multilevel_text_separator" to control multi-level separator between level text and recurring duration text - https://github.com/WordImpress/Give-Recurring-Donations/issues/142
* New: Added span wrapped tag to recurring language in multi-level buttons for easier styling - https://github.com/WordImpress/Give-Recurring-Donations/issues/142
* New: Allow the admin to delete or cancel renewal in the subscription details - https://github.com/WordImpress/Give-Recurring-Donations/issues/204
* New: New form for adding manual renewal payments added to individual subscription details admin page - https://github.com/WordImpress/Give-Recurring-Donations/issues/205
* New: Stripe now supports refunding and cancelling subscriptions from the donation details screen - https://github.com/WordImpress/Give-Recurring-Donations/issues/239
* Tweak: Consolidate subscriptions listing column & improved UI in WP-admin under Donations > Subscriptions - https://github.com/WordImpress/Give-Recurring-Donations/issues/251
* Tweak: Subscription donations are now referred to as "Renewals" for better clarity and easier understanding of the status - https://github.com/WordImpress/Give-Recurring-Donations/issues/215
* Fix: When Give is opening the donation form in a modal, the "Make this donation recurring" Donor's choice checkbox appears before the button - https://github.com/WordImpress/Give-Recurring-Donations/issues/253
* Fix: Properly show/hide the "Recurring Opt-in Default" field in the admin when toggling recurring options
* Fix: Incorrect false negative in conditional check for whether a donation form is recurring is_recurring() method
* Fix: Issue with checking if a Transaction payment is a Subscription parent payment which was causing the recurring label to be incorrectly output on the parent payments' transaction - https://github.com/WordImpress/Give-Recurring-Donations/issues/214
* Fix: Reports filter field not displayed on the Recurring reports sections - https://github.com/WordImpress/Give-Recurring-Donations/issues/211
* Fix: Reports tooltips not properly formatted - https://github.com/WordImpress/Give-Recurring-Donations/issues/217
* Fix: Donor details renewal stat incorrect - https://github.com/WordImpress/Give-Recurring-Donations/issues/245
* Fix: Renewal date incorrectly calculating for certain donation form configurations - https://github.com/WordImpress/Give-Recurring-Donations/issues/201
* Fix: Multiple Donors Choice Recurring Forms cause first Checkbox to always be selected - https://github.com/WordImpress/Give-Recurring-Donations/issues/254
* Fix: Require the last name field for Authorize.net - the gateway requires that the last name be passed when creating subscriptions - https://github.com/WordImpress/Give-Recurring-Donations/issues/262

= 1.1.1 =
* Fix: PHP fatal error for some hosting configurations "Can't use function return value in write context" - https://github.com/WordImpress/Give-Recurring-Donations/issues/192
* New: New link to plugin settings page with new base name constant - https://github.com/WordImpress/Give-Recurring-Donations/issues/190

= 1.1 =
* New: Don't require a login or registration for subscription donations when email access is enabled - https://github.com/WordImpress/Give-Recurring-Donations/issues/169
* New: Show a login form for [give_subscriptions] shortcode for non-logged-in users - https://github.com/WordImpress/Give-Recurring-Donations/issues/163
* New: Donation form option for admins to set whether subscription checkbox is checked or unchecked by default - https://github.com/WordImpress/Give-Recurring-Donations/issues/162
* Tweak: Provide Statement Descriptor when Creating Stripe Plans - https://github.com/WordImpress/Give-Recurring-Donations/issues/164
* Tweak: Don't register post status within Recurring; it's already in Core - https://github.com/WordImpress/Give-Recurring-Donations/issues/174
* UX: Added scrolling capability to subscription parent payments' metabox because it was getting too long for ongoing subscriptions - https://github.com/WordImpress/Give-Recurring-Donations/issues/130
* Fix: PHP Fatal error when Stripe event is not returned - https://github.com/WordImpress/Give-Recurring-Donations/issues/176
* Fix: PayPal Pro Gateway message issue "Something has gone wrong, please try again" response  - https://github.com/WordImpress/Give-Recurring-Donations/issues/177
* Fix: Blank notice appears when updating / saving settings in Give - https://github.com/WordImpress/Give-Recurring-Donations/issues/171

= 1.0.1 =
* Fix: Security fix added to prevent non-subscribers from seeing others subscriptions within the [give_subscriptions] shortcode

= 1.0 =
* Initial plugin release. Yippee!
