=== PressForward ===
Contributors: PressForward, AramZS, boonebgorges, joanftroyano, clioweb
Version: 3.0.2
Donate link:
Tags: aggregation, atom, content curation, curation, curation tool, editorial, feed, RSS, workflow
Requires at least: 3.0.1
Tested up to: 3.8
Stable tag: 3.0.2
License: GPL2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

PressForward provides an integrated editorial process to curate aggregated content from the web.


== Description == 
PressForward provides an editorial process for content aggregation and curation within the WordPress dashboard. It is designed for bloggers and editorial teams who wish to review, discuss, and share content from a variety of sources.

The plugin provides 

* an RSS reader within the WordPress dashboard
* a bookmarklet to capture content from the Web
* tools for collaborative editorial work
* a process for republishing aggregated content

**The RSS “All Content” Reader includes:**

* Readability integration to clean and standardize the formatting of imported content
* Importing of full text, images, video content, and post metadata 
* Individual starring, allowing individual users to flag items of interest
* Ability to nominate any item for further review prior to publication
* Ability to incorporate content not accessible by RSS through the “Nominate This” bookmarklet
* Batch import of feeds from OPML, including Google OPML
* Temporary storage of the aggregated content outside of the WordPress database until it is “nominated” for publication. This enables users to survey content without overwhelming their WordPress installation.
* Responsive design supporting grid or list viewing options

**Tools for collaboration include:**

* Separate “Under Review” interface for editors to review and discuss nominations from all users
* Ability to comment on all items
* Ability to set levels of access by user privileges

**Publication features include:**

* Ability to send full content, attribution link, and metadata of nominated posts to regular WordPress dashboard for publishing
* Ability to republish any or all content in any format supported by your theme (text, image, quote, snippet, etc.)
* Ability to auto-redirect published posts to original source
* A single RSS feed of all your aggregated content

Instructions for use are found in our <a href="https://github.com/PressForward/pressforward/wiki">Wiki on GitHub</a>.

Screenshots and sample instructions for editorial groups are available in the <a href="http://digitalhumanitiesnow.org/editors-corner/instructions/">Editors’ Corner</a> on <a href="http://digitalhumanitiesnow.org">Digital Humanities Now</a>, the largest publication using the PressForward plugin.

The PressForward Plugin was developed for the <a href="http://pressforward.org/">PressForward Project</a> from the <a href="http://chnm.gmu.edu">Roy Rosenzweig Center for History and New Media</a> at <a href="http://gmu.edu">George Mason University</a> by <a href="http://aramzs.me">Aram Zucker-Scharff</a>, <a href=" http://boone.gorg.es/">Boone B. Gorges</a>, and <a href="http://clioweb.org">Jeremy Boggs</a>. It is free to use and modify under a <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">GNU GPL2 license</a>.

== Installation ==
1. Go to the <a href="https://github.com/PressForward/pressforward/tags">latest version on GitHub</a> or install from WordPress.org.
1. Download the zip or tar.gz file of the most recent version
1. Rename zipped folder 'pressforward'
1. Place the ‘pressforward’ folder in your ‘/wp-content/plugins’ directory in your FTP client.
1. Activate plugin via the "Installed Plugins" menu in the Wordpress Dashboard
1. The installed plug-in should now appear in your toolbar on your Wordpress Dashboard. Clicking on the item will provide a dropdown menu. (note: a collapsed toolbar will appear differently on your page.) Take a moment to click through each of the PressForward tabs and familiarize yourself with the structure of the plugin.


**How to Add Feeds**
The feeds you add will populate content on the “All Content” page and a list of the feeds to which you have subscribed will appear on the “Subscribed Feeds” page. If this is your first time using feeds, please visit the tutorial available [here](http://www.paulstamatiou.com/how-to-getting-started-with-rss/). There are two ways to add feeds. You can either add feeds one at a time or import them from another reader with an OPML file.

1. Select the Feeder menu from the PressForward tab in the Wordpress Dashboard
1. To add a single feed just past the complete URL `(http:// ...)` or RSS path into the "Add Single Feed" box
1. To add a list of feeds copy the link to the OPML file into the "Add OPML" box
   + No HTTPS allowed
   + Link Needs to be live `http://` link, no local files
   + File does not necessarily have to exist, ie you can use a dropbox-generated url
1. Save Options
1. You will see a confirmation dialog box that confirms the addition of the feed.
1. If you are upgrading from any version lower than 2.2 to any version higher than 2.3, you will need to click the _**Switch feeds to new retrieval setup**_ button.  This only needs to be done once at the time of the upgrade. If you are installing any version higher than 2.3 for the first time, or upgrading from 2.3 to any higher version, you do not need to click this button.
1. The Subscribed Feeds menu will list all of the feed subscriptions. The posts sent via the feeds will appear on the **All Content** page.

A complete User Manual is available on our <a href="https://github.com/PressForward/pressforward/wiki">Wiki on GitHub</a>.
