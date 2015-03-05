=== PressForward ===
Contributors: PressForward, AramZS, boonebgorges, joanftroyano, clioweb
Donate link:
Tags: aggregate, aggregation, aggregator, atom, attribution, circulate, collect, community, content curation, curate, curation, curation tool, discuss, distribute, editorial, feed, network, news, opml, OPML, read, reader, reblog, reblogging, republish, review, RSS, rss, share, syndicate, syndication, workflow
Requires at least: 3.0.1
Tested up to: 4.1
Stable tag: 3.5.1
License: GPL2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

PressForward provides an integrated workflow for curating content aggregated from the web.


== Description ==
PressForward is a free plugin that provides an editorial workflow for content aggregation and curation within the WordPress dashboard. It is designed for bloggers and editorial teams who wish to collect, discuss, and share content from a variety of sources on the open web.

**PressForward includes:**

* a feed reader embedded within the WordPress dashboard
* a bookmarklet to capture content from the web
* tools for collaborative, editorial work
* a process for republishing aggregated content

**The feed reader features:**

* Readability integration to standardize the formatting of aggregated content
* Full text, image, video content, and post metadata import for each item
* Starring, allowing individual users to flag items of interest
* Nomination of single items for further review prior to publication
* Capture of any web content through the “Nominate This” bookmarklet
* Batch import of feeds from OPML, including Google OPML
* Temporary storage of aggregated content, enabling users to survey content without overwhelming their WordPress installation
* Responsive design for grid or list viewing options

**Tools for collaboration:**

* Separate “Under Review” interface for editors to discuss and select nominations from all users
* Commenting on each item
* Customizable user privileges

**Publication features:**

* Full content, attribution link, and metadata of posts accessible in WordPress dashboard
* Republish any or all content in any format supported by your theme (text, image, quote, snippet, etc.)
* Optional auto-redirect back to original source
* Produces a single RSS feed of all your aggregated content

Instructions for use are found in our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki).

[See how we compare to other WordPress plugins](https://github.com/PressForward/pressforward/wiki/FAQ#how-does-pressforward-compare-to-other-plugins).

Sample instructions for editorial groups are available in the [Editors’ Corner](http://digitalhumanitiesnow.org/editors-corner/instructions/) on [Digital Humanities Now](http://digitalhumanitiesnow.org), the largest publication using the PressForward plugin.

The PressForward Plugin is developed and maintained by the [Roy Rosenzweig Center for History and New Media](http://chnm.gmu.edu) at [George Mason University](http://gmu.edu) as part of the [PressForward Project](http://pressforward.org), generously funded by the Alfred P. Sloan Foundation. Our developers include [Aram Zucker-Scharff](http://aramzs.me), [Boone B. Gorges](http://boone.gorg.es), and [Jeremy Boggs](http://clioweb.org). PressForward is free to use and modify under a [GNU GPL2 license](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

[Detailed information about our code](https://github.com/PressForward/pressforward/wiki/Devbook) can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

== Installation ==

Our [Five-Step Quick Start Guide](https://github.com/PressForward/pressforward/wiki/Quick-Start-Guide) can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

* Search the [WordPress Plugin Directory](http://wordpress.org/plugins/) and download the PressForward plugin; or
* Go to the [latest version on GitHub](https://github.com/PressForward/pressforward/tags).
1. Download the zip or tar.gz file of the most recent version
1. Rename zipped folder 'pressforward'
1. Place the ‘pressforward’ folder in your ‘/wp-content/plugins’ directory using your FTP client.
* Activate plugin via the "Installed Plugins" menu in the Wordpress Dashboard
* The PressForward menu should now appear in your Wordpress Dashboard. Clicking on the PressForward icon will display tabs.

A complete [User Manual](https://github.com/PressForward/pressforward/wiki/User-Manual) also is available on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).


== Frequently Asked Questions ==

A list of [FAQs](https://github.com/PressForward/pressforward/wiki/FAQ) can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

== Screenshots ==

1. Add RSS/Atom Feeds to automatically aggregate content.
2. Nominate This! Bookmarklet allows you to collect content from the web.
3. All Content Grid View provides title, source, snippet, and additional information for every item.
4. All Content List View provides title, source, snippet, and additional information for every item.
5. Toolbar on every item provides easy access to see additional information, star items, add a comment, or nominate an item, sending it to Under Review.
6. Reader View displays content in a comfortable reading environment, links to original source, and includes comments and the toolbar.
7. Discussion visible in threaded comments.
8. In Under Review the toolbar displays the number of comments and nominations, and provides ability to archive item or Send to Draft.
9. Edit and format items in the regular WordPress Posts panel.

== Changelog ==

= 3.5 =
* Check for known aggregation services to insure proper resolution of URLs.
* Created the Amplify dropdown button in the actions bar. 
* Added button to share to Twitter to the Amplify dropdown. 
* Added button to Amplify to send from All Content directly to draft.
* For clarity, changed Last Retrieved column title to Last Time Item Retrieved.
* Added OG URL tag to forwarded pages.
* Updated Readability script to detect Medium.
* Updated Readability script to retrieve pages using File Get Contents when no other methods work.
* Separate out a number of template functions into their own class.
* Attach the navbar on scroll.
* Moved Refresh button to Tools.
* Better assure that the system retrieves open graph images and sets them as Featured Images.
* Display and sort by Last Time Feed Retrieved.
* Allows feed URLs to be changed via Quick Edit. 
* Set up functions to better detect current page.
* Set up a debug function for displaying current page.
* If WordPress installs are greater than 3.1, all feeds not in folders will display under folders.
* Set default sort for Feeds to alphabetical.
* Set up breadcrumb titling for All Content and Nominated. 
* Moved the source and byline to the head of the current article modal.
* Added help button to menu.
* Allows users to decide if they want the right menu displayed and turn it off by default. 
* Allows users to set Grid or List and have the system remember it as a user preference. 
* Allows users to set infinite scroll or pagination and have the system remember it as a user preference. 
* Adds search to the nav menu on scroll-down.
* New tabbed settings page, with user access to tabs based on user roles. 
* Allows administrators to control tab access with settings. 
* Allows users to always access their settings regardless of level. 
* Moved Nominate This to the Tools menu. 
* Added Nominate This as a source for the Feeds menu.
* Added a Nominate This prompt for first-time users to the All Content menu. 
* Allows administrators to control the display of comments based on user level.
* Open modal now counts as a hashed page state. 
* Pressing the browser back button while viewing an open modal will close the modal, not go to the previous page.
* Compressed filtering options into a dropdown in the navbar. 
* Opening a modal to read an item will now only set the item to Read, it will never unset the Read property. 
* Added a column to the Feeds area to indicate last time the feed was retrieved. 
* Allows feeds to be sorted by the last time they were retrieved. 
* Fullscreen script patched to 1.2
* Flipped infobox to display over the action bar. 
* No hidden items in modal navigation.
* The Folders sidebar now scrolls separately from the main page and is always fully available.
* Clarify what exactly dismissing alerts does. (It sets those feeds to Inactive.)
* Better descriptions of most settings. 
* Fixed occasional content duplication on infinite scroll, when retrieval was active. 
* Fixed a variety of user action responses so actions are clearer.

= 3.4.6 =
* Supports additional file path configurations for Nominate This.

= 3.4.5 =
* Nominate This now works much more optimally.
* Nominate This no longer creating crazy line breaks. 
* OpenGraph image detection now working properly. 
* Alert Box now more backwards compatible.
* Turned off modules no longer show turned on menus. 
* Mark As Read now works properly. 

= 3.4.4 =
* Resolved CSS overlay design conflicts.
* Fixed Nominate to Draft in the Nominated page.

= 3.4 =
* the "Nominate This" bookmarklet has moved to tools
* the "Under Review" panel has been renamed to Nominated
* refinements to sorting and filtering in the "All Content" and "Nominated" views
* the "Subscribed Feeds" panel now displays the time and date of the last update on a feed
* items can now be unnominated by clicking again on the green nominate button in All Content
* Administrators can now set the number of days to retain feed items
* users can choose to turn on pagination rather than infinite scroll
* feed alerts can now be deleted or dismissed
* items older than one week, two weeks, one month, or one year can now be archived in "Nominated"
* introduction of folders for feeds.  Individual feeds can be added to folders in the "Subscribed Feeds" panel.  Items in "All Content" can then be filtered by folder.
For more information on the changes see the GitHub summary for [3.4](https://github.com/PressForward/pressforward/issues?q=milestone%3A3.4.x)

= 3.3 =
* show hidden items on the "All Content" browse page
* upload OPML files in the "Add Feeds" panel
* return an item to the pre-readability state in the full screen mode
* provide option to turn off alerts and to change the retention time for feeds
* enhancements improve the speed of the plugin
* improvements to moving a nominated item's metadata into a draft
* improvements to the appearance of action buttons in list and grid view
* sets a limit to the number of times an indivdual user is able to nominate a single item
For more information see the GitHub summary for [3.2](https://github.com/PressForward/pressforward/issues?q=milestone%3A3.2)
