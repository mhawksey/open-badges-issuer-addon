=== Open Badges Issuer Add-on ===
Contributors: mhawksey
Tags: badge, badges, openbadges, credly, OBI, mozilla, open badges, achievement, badgeOS
Requires at least: 3.5
Tested up to: 4.3.0
Stable tag: 1.1.2
License: GNU AGPLv3
License URI: http://www.gnu.org/licenses/agpl-3.0.html

Issue Mozilla Open Badges directly from your site with this add-on for BadgeOS

== Description ==

This add-on allows you to directly issue Open Badges from your WordPress site. The add-on works with the BadgeOS plugin exposing achievements earned as [Open Badges Assertions](https://github.com/mozilla/openbadges-specification/blob/master/Assertion/latest.md). The add-on integrates with the [Mozilla Issuer API](https://github.com/mozilla/openbadges/blob/development/docs/apis/issuer_api.md) to allow award badges to be pushed to their Mozilla Backpack. 

[Get the BadgeOS plugin](http://wordpress.org/extend/plugins/badgeos/ "BadgeOS").

= Resources / Helpful Links =

This plugin was developed using the [BadgeOS Boilerplate Add-On](https://github.com/opencredit/BadgeOS-Boilerplate-Add-on) by @mhawksey at the [Association for Learning Technology](http://alt.ac.uk) for the [Open Course in Technology Enhanced Learning (ocTEL)](http://octel.alt.ac.uk). For more information about BadgeOS see the resource below:

* [BadgeOS.org](http://badgeos.org/ "BadgeOS web site") - Contact Us, Video Tutorials, Examples, News
* [Credly.com](https://credly.com/ "Credly web site") - Manage lifelong credentials that matter
* [BadgeOS on GitHub](https://github.com/opencredit/badgeos "BadgeOS on GitHub") - Report issues, contribute code

== Installation ==


1. First get and activate the free [BadgeOS plugin](http://wordpress.org/extend/plugins/badgeos/ "BadgeOS").
1. Get and activate the free [JSON API plugin](http://wordpress.org/extend/plugins/json-api/ "JSON API").
1. Then upload the 'badgeos-open-badges-issuer' plugin via the 'Plugins' menu in WordPress (or upload to the '/wp-content/plugins/' directory).
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==


== Screenshots ==

1. Settings screen. Options include specifying a different user data field for the Open Badges recipient and setting IssuerOrganisation

2. Screenshot of issued Open Badges Log. Results are tagged for success and failed issues. Failed issuing includes returned error message from Mozilla Backpack

3. Issuing of badges is done using modal Mozilla Issuer API 


== Changelog ==

= 1.1.2 =
* fixed issue with sending badges to Backpage from http sites
* stronger salt to obscure recipient email


= 1.1.1 =
* option to not include the plugin css

= 1.1.0 =
* Integrated 'send to backpack' in all badge listings where user has achieved award
* Improved [badgeos_backpack_push] shortcode functionality including search option

= 1.0.1 =
* Fixed missing from menu

= 1.0.0 =
* Intial release


== Upgrade Notice ==

= 1.0.0 =
* Intial release
