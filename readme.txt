=== Indexhibit2 Importer ===
Contributors: leemon
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=oscarciutat%40gmail%2ecom
Tags: importer, indexhibit
Requires at least: 4.0
Tested up to: 4.9.10
Requires PHP: 5.6
Stable tag: trunk
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
If needed, you can convert the imported pages to posts or other post types with a plugin such as Post Type Switcher (https://wordpress.org/plugins/post-type-switcher/)

== Copyright ==
Indexhibit 2 Importer is distributed under the terms of the GNU GPL

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

== Changelog ==
= 0.1 =
* Initial release
