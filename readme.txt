=== Indexhibit 2 Importer ===
Contributors: leemon
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=oscarciutat%40gmail%2ecom
Tags: importer, indexhibit
Requires at least: 4.0
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import exhibits and media files from an Indexhibit 2 site.

== Description ==
Import exhibits and media files from an Indexhibit 2 site.

== Installation ==
1. Upload the `indexhibit2-importer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Tools -> Import screen, and click on the 'Run Importer' link under Indexhibit 2

== Frequently Asked Questions ==
= Does this plugin import all the contents from an Indexhibit 2 site? =
No, it imports exhibits and media files, but ignores links, sections, subsections and exhibit formats.

= How are exhibits and media files imported into WordPress? =
Exhibits are imported as pages and media files are imported as attachments which are attached to their corresponding pages.
If needed, you can convert the imported pages to posts or other post types with a plugin such as [Post Type Switcher](https://wordpress.org/plugins/post-type-switcher/)

= Where can I find my Indexhibit 2 site settings file? =
The `config.php` file can be found in the `/ndxzsite/config/` directory

= I like the default theme in Indexhibit 2. Is there anything like it in WordPress? =
There are similar themes in WordPress. Here are some examples:
* [Blask]https://wordpress.org/themes/blask/
* [Cele]https://wordpress.org/themes/cele/
* [Fukasawa]https://wordpress.org/themes/fukasawa/
* [Koji]https://wordpress.org/themes/koji/
* [McLuhan]https://wordpress.org/themes/mcluhan/
* [Rams]https://wordpress.org/themes/rams/
* [Soho Lite]https://wordpress.org/themes/soho-lite/

== Screenshots ==
1. Import exhibits
2. Import media files

== Changelog ==
= 1.0.3 =
* Fix typos in database settings form

= 1.0.2 =
* Consolidate plugin options into one array

= 1.0.1 =
* Fix small typos

= 1.0 =
* Initial release
