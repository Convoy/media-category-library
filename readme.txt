=== Media Category Library ===
Contributors: timmcdaniels, cbryantryback
Donate link: http://WeAreConvoy.com
Tags: media library, attachments, upload date, media taxonomy
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that allows items in the Media Library to be assigned to a category.

== Description ==

Features include:

* Associate a category to each file in the Media Library
* Edit or delete media categories
* View files associated to categories in a separate submenu page of Media called Media Category Library
* Change the upload date of files in the Media Category Library
* In Media Category Library, view a list of Pages that have the file included in the content.
* Display lists of files by categories using a shortcode: [mediacat cats="Documents,Images"]
* Front end search form with customizable rewrite URL -- the default URL is /mediacat-library/ (or ?mediacat_library=1 if you use default permalinks), but this can be changed in the plugin settings
* Display front end search form by shortcode: [mediacatform]

== Installation ==

1. Upload `media-category-library` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enable permalinks (optional but suggested)

== Frequently Asked Questions ==

= How do I access the frontend search form? =

The frontend search form is available as a rewrite URL (requires a permalink structure to be defined); the default URL is http://www.YOUR_DOMAIN_HERE.com/mediacat-library/. You can change the rewrite URL under Settings -> Media Category Library. If you don't use a permalink structure, you can access it here: http://www.YOUR_DOMAIN_HERE.com/?mediacat_library=1

== Screenshots ==
1. This is a screenshot of the Media Category settings page.

2. This is a screenshot of the Edit Media page with a Media Category dropdown.

3. This is a screenshot of the backend Media Category Library page.

4. This is a screenshot of the frontend search form.

== Changelog ==

= 0.2 =
* Fixed issues for WordPress subdirectory installs (thanks to fugu@eraserheads.de).
* Added German language support (translation by fugu@eraserheads.de).
* Changed URLs in plugin so that things work if you use default permalinks.

= 0.1 =
* First release!

== Upgrade Notice ==

= 0.1 =
First release!
