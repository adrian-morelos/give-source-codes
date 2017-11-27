=== Give - PayPal Pro ===
Contributors: wordimpress
Tags: donations, donation, ecommerce, e-commerce, fundraising, fundraiser, paypal, paypal pro, gateway
Requires at least: 4.2
Tested up to: 4.7
Stable tag: 1.1.3
License: GPLv3
License URI: https://opensource.org/licenses/GPL-3.0

PayPal Pro Gateway Add-on for Give.

== Description ==

This plugin requires the Give plugin activated to function properly. When activated, it adds a payment gateway for PayPal Website Payments Pro.

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

= 1.1.3 =
* New: The plugin now checks to see if Give is active and up to the minimum version required to run the plugin.

= 1.1.2 =
* Fix: PHP warning because private `load_textdomain()` method should have been public.

= 1.1.1 =
* Fix: Issue outputting CC fields when multiple donations forms are on a page and the default gateways is PayPal Pro the donation form.

= 1.1 =
* New: Support for PayPal Payments Pro
* New: Support for PayPal's Website Payment Pro REST API integration. Now you can accept payments using PayPals' modern API for faster transaction times.
* New: Now you have the ability to disable the "Billing Details" fieldset which contains the address fields. This information is not required to process transactions and disabling the fields could help increase conversion rates.
* New: Additional inline documentation for easier understanding of each gateway offering and links to in-depth docs.
* Update: 'BUTTONSOURCE' PayPal arg

= 1.0.1 =
* Update: Updated 'buttonsource' param for PP API
* Fix: PHP notice about missing variable upon successful transaction

= 1.0 =
* Initial plugin release. Yippee!
