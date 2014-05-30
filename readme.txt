=== PressForward ===
Contributors: PressForward, AramZS, boonebgorges, joanftroyano, clioweb
Donate link:
Tags: aggregate, aggregation, aggregator, atom, attribution, circulate, collect, community, content curation, curate, curation, curation tool, discuss, distribute, editorial, feed, network, news, opml, OPML, read, reader, reblog, reblogging, republish, review, RSS, rss, share, syndicate, syndication, workflow
Requires at least: 3.0.1
Tested up to: 3.9.1
Stable tag: 3.1.3
License: GPL2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

PressForward provides an integrated workflow for curating content aggregated from the web.


== Description == 
PressForward is a free plugin that provides an editorial workflow for content aggregation and curation within the WordPress dashboard. It is designed for bloggers and editorial teams who wish to collect, discuss, and share content from a variety of sources on the open web.

PressForward includes: 

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

Sample instructions for editorial groups are available in the [Editors’ Corner](http://digitalhumanitiesnow.org/editors-corner/instructions/) on [Digital Humanities Now](http://digitalhumanitiesnow.org), the largest publication using the PressForward plugin.

[See how we compare to other WordPress plugins](https://github.com/PressForward/pressforward/wiki/FAQ#how-does-pressforward-compare-to-other-plugins).
 
The PressForward Plugin is developed and maintained by the [Roy Rosenzweig Center for History and New Media](http://chnm.gmu.edu) at [George Mason University](http://gmu.edu) as part of the [PressForward Project](http://pressforward.org), generously funded by the Alfred P. Sloan Foundation. Our developers include [Aram Zucker-Scharff](http://aramzs.me), [Boone B. Gorges](http://boone.gorg.es), and [Jeremy Boggs](http://clioweb.org). PressForward is free to use and modify under a [GNU GPL2 license](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

== Installation ==

* Search the [WordPress Plugin Directory](http://wordpress.org/plugins/) and download the PressForward plugin; or
* Go to the [latest version on GitHub](https://github.com/PressForward/pressforward/tags).
1. Download the zip or tar.gz file of the most recent version
1. Rename zipped folder 'pressforward'
1. Place the ‘pressforward’ folder in your ‘/wp-content/plugins’ directory using your FTP client.
* Activate plugin via the "Installed Plugins" menu in the Wordpress Dashboard
* The PressForward menu should now appear in your Wordpress Dashboard. Clicking on the PressForward icon will display tabs.

== Quick Start Guide ==

A complete [User Manual](https://github.com/PressForward/pressforward/wiki/User-Manual) is available on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

Our [Five-Step Quick Start Guide](https://github.com/PressForward/pressforward/wiki/Quick-Start-Guide) also can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

**Step 1: Install and Activate Plugin**
1. Install the plugin.
* Search the WordPress directory and download the PressForward plugin; or 
* Download the zip or tar.gz of the [latest version ](https://github.com/PressForward/pressforward/tags), place it into your ‘wp-content/plugins’ directory, unzip, and rename the folder ‘pressforward’.
2. Activate PressForward from your WordPress plugin menu.

**Step 2: Add RSS/ATOM Feeds and OPML Files**
1. Choose the Add Feeds tab in the PressForward menu.
1. Add a feed URL or OPML file under Subscribe to Feeds.
1. Click Save Options.

**Step 3: Read, Discuss, and Nominate Content**
Posts from added feeds will populate the All Content panel. Use the PressForward toolbar to see additional information, star, add a comment, and nominate items to share on your site. To read and nominate posts:
1. Select the All Content tab from the PressFoward menu to review items.
1. Click on a title to read the full text in the Reader View or follow the embedded link to view the original item.
1. Navigate between items with the forward and backward arrows.
1. Click Add Comment to respond to an item.
1. Click the Single Arrow in the toolbar to Nominate an item, sending it to Under Review.

**Step 4: Select Content and Send to Draft**
1. Open the Under Review panel and find your item. 
1. Click the title to open an item in Reader View.
1. Click the Double Arrow in the PressForward toolbar to send a selected item to Draft. 

**Step 5: Format and Publish a Post**
1. Find drafts in the WordPress Posts panel. 
1. Edit, format, and publish content as you would any other post in WordPress. 
1. A link to the original site of publication will be inserted at the bottom of each post discovered through PressForward. Consider using this link to attribute work to its original site of publication.

== FAQ ==

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


== Developers ==

We welcome volunteers to help develop and extend PressForward functionality and modules. Our GPL2 license also allows you to fork and modify the code to suit your needs.

[Detailed information about our code](https://github.com/PressForward/pressforward/wiki/Devbook) can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).
