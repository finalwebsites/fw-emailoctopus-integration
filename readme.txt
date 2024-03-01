=== FW EmailOctopus Integration ===
Contributors: finalwebsites
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: EmailOctopus, integration, elementor, woocommerce, form actions
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.4.3
Stable tag: 1.0.0

Increase the subscribers for your website by using EmailOctopus and this professional integration plugin for WordPress, Elementor and WooCommerce.

== Description ==

Email marketing is still one of the best ways to drive traffic to your website. You can use this WordPress plugin to add a newsletter subscription form below your blog, right in your articles or on other places using Elementor forms or a shortcode. Use the WooCommerce integration and submit order related information together with each subscription.

*To use this plugin, you need access to a EmailOctopus account. You can get a free account from the [EmailOctopus](https://emailoctopus.com/) website. The free account allows you use 2500 active subscribers and has a few limitations on the account features.*


= These are the features =

* Add the subscription form by using a shortcode
* Integration for Elementor form actions (with support for custom list fields)
* WooCommerce integration (store order related info in EmailOctopus)
* Easy to use, custom list fields will be automatically created if the don't exists
* Efficient spam protection (using JavaScript and cookies)
* The visitor stays on your website while submitting the form data
* Support for mutliple mailing lists
* You can change/translate all plugin text by using a localization tool (Loco Translate is our favorite)
* Support for multilanguage websites (comapatible with [Polylang](https://wordpress.org/plugins/polylang/))
* The form HTML is compatible with the Bootstrap CSS framework (v3)
* Optional: use the CSS style-sheet (Bootstrap v3 compatible) included with the plugin
* Track successfully submitted forms in Google Analytics and Clicky
* The plugin includes JS and CSS files only if the form (shortcode) is present
* Using nonces for simple form value validation


== Installation ==

The quickest method for installing the plugin is:

1. Automatically install using the built-in WordPress Plugin installer or...
1. Upload the entire plugin directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create in EmailOctopus one ore more mailing lists and add also an API key.
1. Enter your EmailOctopus API key, choose the mailing list ID and the other options on the plugin settings page.

=== WooCommerce integration ===

If your want to use the EmailOctopus integration for WooCommerce, you need to follow these steps:

1. Goto WooCommerce > Settings > Integrations > EmailOctopus
1. Choose the Mailing list you prefer for the subscription feature on your checkout page
1. Check the other features to include addtional order information with each subscription
1. Enable the option "Subscribe everyone" if you like to use Emailoctopus for none commercial emails (too).

=== Elementor integration ===

If you use Elementor Pro, it's possible to add the EmailOctopus subscription as form action.

1. Add the Elementor form as usual and choose "EmailOctopus" from "Actions after submit".
1. Now point the diifferent mailing list fields to the form fields, by entering the field ID.

The "Newsletter" option has a special behavior. Use a checkbox in your form and if the checkbox was checked, a tag called "newsletter" will be added to the subscriber in EmailOctopus.


== Frequently Asked Questions ==

= How to add a manual goal in Clicky? =

If you use a Clicky premium plan it's possible to track Goals.

1. In Clicky, visit: Goals > Setup > Create a new goal.
1. Enter a name for the goal
1. Check the "Manual Goal" checkbox and click Submit
1. Copy/paste the ID into the corresponding field on the plugin options page

= How does the "Subscribe everyone" feature work? =

For webshop owners is it possible to use the WooCommerce integration and EmailOctopus, to send emails related to an order. For example usage instructions or a request for a review. To make this work, you need to sync all email addresses and not only the addresses from people subscibed to the newsletter. Use the Automation feature in EmailOctopus for this kind of after-sale campaigns.

== Screenshots ==


== Changelog ==

= 1.0 =
* Initial release
