=== Indexhibit 2 Importer ===
Contributors: leemon
Donate link: 
Tags: importer, indexhibit
Requires at least: 4.0
Tested up to: 4.9.10
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import exhibits and media files from an Indexhibit 2 site.

== Description ==

Import exhibits and media files from an Indexhibit 2 site.

This plugin is experimental and is provided with no support or warranty.

== Installation ==

1. Upload the `indexhibit2-importer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Tools -> Import screen, and click on the 'Run Importer' link under Indexhibit 2

== Frequently Asked Questions ==
= Does this plugin import all the contents from an Indexhibit 2 site? =
No, this imports exhibits and media files, but ignores links, sections, subsections and exhibit formats.

= How are exhibits and media files imported into WordPress? =
Exhibits are imported as pages and media files are imported as attachments attached to their corresponding pages.
If needed, you can convert the imported pages to posts or other post types with a plugin such as Post Type Switcher (https://wordpress.org/plugins/post-type-switcher/)

= I don't see any of the imported media files in the imported pages. Why? =
The plugin just imports the media files to the WordPress media library. You need to add them to the pages inserting them directly or creating galleries.

== Changelog ==

= 0.1 =
* Initial release
