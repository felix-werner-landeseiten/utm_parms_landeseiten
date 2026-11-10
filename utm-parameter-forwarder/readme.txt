=== UTM Parameter Forwarder ===
Contributors: landeseiten
Tags: utm, tracking, campaign, gclid, fbclid
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Carries UTM and ad click-ID parameters from the landing URL to every internal link.

== Description ==

When a visitor lands on the site with campaign parameters such as
`?utm_source=linkedin&utm_campaign=spring`, the plugin appends the same
parameters to every internal link on the page. The next page therefore
receives them in its URL, and forms (Elementor, Gravity Forms, Contact Form 7,
HubSpot, etc.) can read them into hidden fields.

Forwarded by default:

* UTM: utm_source, utm_medium, utm_campaign, utm_term, utm_content, utm_id
* Google Ads: gclid, gbraid, wbraid, gad_source
* Meta: fbclid
* LinkedIn: li_fat_id

The list can be changed under Settings > UTM Forwarder.

Behaviour:

* Only links to the same site are changed; external links, mailto:, tel:,
  anchors (#) and download links are left alone.
* WordPress system URLs (wp-admin, wp-login.php, wp-json, xmlrpc.php) are skipped.
* Parameters already present on a link are not overwritten.
* Links added later (menus, sliders, popups) are handled too.
* Add the CSS class `utmpf-ignore` to a link to exclude it.
* Works with full-page caching, because all processing happens in the browser.

Security and privacy:

* The server never reads, stores or outputs the incoming parameter values.
* No database tables, no AJAX or REST endpoints, no cookies.
* Only whitelisted parameter names are forwarded; values are length-limited,
  stripped of control characters and of < > " ' ` \, and URL-encoded by the
  browser's URL API.
* The optional "remember during visit" mode uses sessionStorage, which stays in
  the visitor's tab and is never sent to the server.

== Installation ==

1. Download `utm-parameter-forwarder.zip` from the latest GitHub release
   (https://github.com/felix-werner-landeseiten/utm_parms_landeseiten/releases).
2. Plugins > Add New > Upload Plugin, choose the ZIP.
3. Activate. No configuration is required.

== Updates ==

New versions are published as GitHub releases and appear in Dashboard > Updates
like any other plugin (the repository must be public for this). A new ZIP can
also be uploaded manually; WordPress then offers "Replace current with uploaded".

== Changelog ==

= 1.0.0 =
* First release.
