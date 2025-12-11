=== PressForward ===
Contributors: PressForward, AramZS, boonebgorges, lmrhody, asmmap, regan008, joanftroyano, clioweb
Donate link:
Tags: aggregate, aggregation, aggregator, atom, attribution, circulate, collect, community, content curation, curate, curation, curation tool, discuss, distribute, editorial, feed, network, news, opml, OPML, read, reader, reblog, reblogging, republish, review, RSS, rss, share, syndicate, syndication, workflow
Requires at least: 5.7
Tested up to: 6.9
Stable tag: 5.9.4
License: AGPLv3
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

PressForward provides an integrated workflow for curating content aggregated from the web.


== Description ==
PressForward is a free plugin that provides an editorial workflow for content aggregation and curation within the WordPress dashboard. It is designed for bloggers and editorial teams who wish to collect, discuss, and share content from a variety of sources on the open web.

**PressForward helps you:**

* Collect content from the web via a feed reader and a bookmarklet
* Discuss content through a collaborative editing interface
* Share content using best practices for attribution and citation

**Collect**

* Aggregate content using RSS/Atom feeds
* Capture any web content with a bookmarklet
* Import full text, image, video content, and post metadata
* Batch add feeds using OPML files
* Integrate standardized content with [Readability](https://www.readability.com/)

**Discuss**

* Discuss content internally with private commenting
* Count nominations from teams of contributors and editors
* Expose item metadata, including the name of the source and method of nomination

**Share**

* Allow contributors to send items directly to their own Twitter account
* Attach full content, attribution link, canonical URL, and metadata to WordPress Posts as Draft
* Republish any content type supported by your theme (text, image, quote, snippet, etc.)
* Optional auto-redirect back to original source
* Export an RSS feed that combines all content from site's feed list

Instructions for use are found in our [User Manual](https://github.com/PressForward/pressforward/wiki).

[See how we compare to other WordPress plugins](https://github.com/PressForward/pressforward/wiki/FAQ#how-does-pressforward-compare-to-other-plugins).

The PressForward Plugin has been developed and maintained by [Digital Scholar](https://digitalscholar.org) since 2019. [Boone B. Gorges](https://boone.gorg.es) is the lead developer. The plugin was originally developed by the [Roy Rosenzweig Center for History and New Media](https://chnm.gmu.edu) at [George Mason University](https://gmu.edu) as part of the [PressForward Project](https://pressforward.org), generously funded by the Alfred P. Sloan Foundation. Previous developers include [Aram Zucker-Scharff](http://aramzs.me/), and Jeremy Boggs. PressForward is free to use and modify under a [GNU GPL3 license](https://www.gnu.org/licenses/gpl-3.0.en.html).

[Detailed information about our code](https://github.com/PressForward/pressforward/wiki/Devbook) can be found on our [GitHub Wiki](https://github.com/PressForward/pressforward/wiki/).

== Installation ==

Our [Quick Start Guide](https://github.com/PressForward/pressforward/wiki/Quick-Start-Guide) can be found in our [User Manual](https://github.com/PressForward/pressforward/wiki).

* Search the [WordPress Plugin Directory](http://wordpress.org/plugins/) and download the PressForward plugin; or
* Go to the [latest version on GitHub](https://github.com/PressForward/pressforward/tags).
1. Download the zip or tar.gz file of the most recent version
1. Rename zipped folder 'pressforward'
1. Place the ‘pressforward’ folder in your ‘/wp-content/plugins’ directory using your FTP client.
* Activate plugin via the "Installed Plugins" menu in the Wordpress Dashboard
* The PressForward menu should now appear in your Wordpress Dashboard. Clicking on the PressForward icon will display tabs.

A complete [User Manual](https://github.com/PressForward/pressforward/wiki) also is available.

== Frequently Asked Questions ==

A list of FAQs can be found in our [User Manual](https://github.com/PressForward/pressforward/wiki/FAQ)

== Screenshots ==

1. Add RSS/Atom Feeds to automatically aggregate content.
2. Nominate This! Bookmarklet allows you to collect content from the web.
3. All Content Grid View provides title, source, snippet, and additional information for every item.
4. All Content List View provides title, source, snippet, and additional information for every item.
5. Toolbar on every item provides easy access to see additional information, star items, add a comment, or nominate an item, sending it to Nominated.
6. Reader View displays content in a comfortable reading environment, links to original source, and includes comments and the toolbar.
7. Internal discussion visible in threaded comments.
8. In Nominated the toolbar displays the number of comments and nominations, and provides ability to archive item or Send to Draft.
9. Send items to introduce, format, and publish in the WordPress Posts panel.

== Changelog ==

= 5.9.3 =
* Fixed bug that prevented Subscribers from being able to use the entire Nominate This flow.

= 5.9.2 =
* Fixed bug that caused autoload failure in certain cases of nested autoloaders.

= 5.9.1 =
* Fixed bug that prevented Nominate This info box from appearing in Reader for Subscribers.

= 5.9.0 =
* Added new Amplify target services: Mastodon, Bluesky, and Threads.
* Added the ability to subscribe to Google Scholar keyword and author URLs, using the same mechanism as RSS feed subscription.
* Added an 'Import Tags' option to the Nominate This bookmarklet, allowing users to control whether tags are imported from the source item.
* Improved handling of errors in OPML generation related to failed RSS imports.
* Improved filters that allow for incoming feed items to be excerpted and otherwise manipulated.
* Fixed bug that prevented 'Nominate This' section of Reader panel from respecting tho 'nominate_this' permission check.
* Fixed bug that prevented folders from appearing when editing a Feed in the block editor.
* Fixed bug that prevented 'Source statement' formats from being properly saveable.
* Removed dummy "Foo" module.

= 5.8.0 =
* Overhaul of Feed management panel, including a switch to the Block Editor, fields for editing a feed URL, and tools for manually triggering retrieval.
* Overhaul of Add Feed tools, including improved validation for URLs, duplicate detection, and better auto-detection of feed URLs based on site URLs.
* Overhaul of PressForward Dashboard menu structure. Feeds are now handled in a separate top-level menu item, while 'All Content' is renamed to 'Feed Items' and 'Nominated' is renamed to 'Nominated Items'.
* Add support for feed authors, which are pulled from the feed, are editable by the admin, and are used as a fallback value for feed item authors when a given feed item has no specific author data.
* New opt-in feature submits a source URL to the Internet Archive (archive.org) when an item is promoted to draft.
* Performance improvements to the Reader interfaces.
* Standardize behavior of the Nominate/Nominate Count interfaces in Reader.
* New tool allows filtering by date in Reader interfaces.
* Internal improvements for increased compatibility with WP_Query caching.
* Fixed a bug that caused infinite redirect loops in certain configuration of 'Forward to Source' feature.
* Fixed a bug that caused JavaScript console errors when opening the Folders pane in the Reader views.

= 5.7.0 =
* New "Welcome" panel provides an easy-to-use path into PressForward content for both new and existing users.
* Added the ability to add a comment when nominating an item.
* Added a feature that allows admins to customize the format the 'Source' statement appended to incoming items.
* Added configurable email notifications when an item you've nominated or commented on receives a new editorial comment.
* Improved consistency in appearance and markup in various admin panels.
* Introduced a block to display the "credits" for an item, including the author and source publication.
* Improved performance on the debugger panel.
* Improvements to Stats panel, including admin-configurable access control for Stats.
* Fixed a number of bugs in the way that some admin panel subnavigation links are built, allowing for better deep linking within these panels.

= 5.6.2 =
* Fixed bug in the way that dependent scripts are loaded on Reader interface.

= 5.6.1 =
* Fixed bug in the way that new JavaScript introduced in 5.6.0 is loaded.

= 5.6.0 =
* Complete rebuild of the Nominate This bookmarklet tool, leveraging the Block Editor. Users who prefer the legacy version of Nominate This can use the 'Force Classic editor for Nominate This' toggle.
* Refactoring of the RSS subscription internals, so that feeds are retrieved independently of one another. This improves performance and reliability.
* A custom block for displaying an item's Nominators.
* A custom block for displaying the Nominate This bookmarklet code on the front end.
* Dynamic user-facing feedback when clicking the 'Refresh Feed Items' link in list of subscribed feeds.
* Fixed behavior that caused PressForward to improperly filter author display names and URLs in certain cases.
* Improved the internals responsible for enqueuing script and style assets, for improved performance.
* Improved messaging in the OPML import module when the WP installation doesn't support OPML uploads.
* Improvements to integration with WordPress Dashboard styles.
* Improved support for latest versions of PHP.

= 5.5.0 =
* Bumped required PHP version to 7.3.0.
* Updated to latest version of Readability.php library, for server-side parsing of items imported via RSS.
* Visual and usability improvements to Nominate This window.
* Improvements to the 'Bookmarklet' section of the Tools admin panel, including a clipboard tool that makes it easier to set up the Nominate This bookmarklet on mobile devices.
* Reworked the trash deletion system to use WordPress's pseudo-cron system, for improved reliability.
* Improvements to internal library loading, and updates to various PHP libraries.
* Improvements to internationalization, including integration with wordpress.org translation systems.
* Fixed bug that caused tags not to be imported from Nominate This in certain cases.
* Improvements to the way that trash deletion is handled on a per-item basis, including the ability to enter 0 as the "retain time", thereby disabling automatic deletion of old items.
* Fixed bug that prevented users from uploading OPML files for import.
* Refactored the Sort feature on the Reader interface for more reliable performance.
* Internal improvements to allow for better customization of the "draft" post type via filter.
* Eliminated a number of unused libraries.

= 5.4.0 =
* Bumped WordPress requirement to 5.7.
* Overhaul to the way that the Nominate This window fetches remote content, for improved performance, improved parsing of source with content, and better error handling.
* Rolled back dynamic source statement from 5.3.0 in favor of legacy source statement in post body.
* Introduced new, optional email notifications for nominators (on successful nomination, and on nomination promotion).
* Added support for JSON-LD data detection when fetching remote items in Nominate This.
* Fixed bug that prevented categories from being properly saved during the nomination process.
* Fixed bug that prevented Subscribed Feeds from being edited in the Block Editor.
* Fixed bug that caused 'Drafted' filter to show others' drafts to users who don't have the proper permissions.
* Internal improvements to conform with WordPress coding standards and best practices.
* Fixed bug that prevented certain OPML imports from completing.
* Fixed bug that prevented the 'Refresh Feed Items' button from working.
* Fixed bug that triggered JavaScript errors when loading certain items directly in the Reader interface.
* Fixed pagination bugs in Reader mode.
* Improved appearance and functionality of modals after recent Bootstrap library update.
* Improved compatibility with PHP 8.1+.

= 5.3.1 =
* Added Source field to the bookmarklet interface.
* Fixed bug where Source statement was appended incorrectly to certain pieces of content.
* Fixed bug that prevented metas from being transferred during nomination advancement.

= 5.3.0 =
* Source statement is now appended to the post content dynamically, rather than inserted into the database.
* Improved caching of external post fetches.
* Improved caching and performance when fetching OpenGraph items.
* Fixed bug in folder syntax when querying for nominations.

= 5.2.10 =
* Fixed incorrect syntax in bookmarklet code
* Improved PHP 8 compatibility
* Fix bug in comment AJAX that prevented submission in some cases
* Security hardening
* Updates to Twitter Bootstrap library

= 5.2.9 =
* WP 6.0 compatibility
* Improved sanitization

= 5.2.8 =
* Fix admin styling and JavaScript after Bootstrap 5.x updates
* Improve admin load speed with caching techniques

= 5.2.7 =
* Rebuild CSS and JS assets

= 5.2.6 =
* Fixed improper sanitization of content nominated via bookmarklet

= 5.2.5 =
* Fixed incorrect function name introduced in 5.2.4
* Improved compatibility with latest versions of PHP

= 5.2.4 =
* Security fix: Fixed potential XSS vulnerability
* Security hardening against XSS and CSRF

= 5.2.3 =
* Security fix: Removed potential iframe vulnerabilities in library test directories.
* Removed unnecessary files to reduce plugin size.
* Fixed syntax error.

= 5.2.2 =

* Incorporated supporting authentication and API libraries for a future PressForward extension.
* Built out authentication flow to transmit data through the PF API with full data encryption.
* Fixed support for user meta to match standard WordPress use.
* Failed OpenGraph fetch now has a fallback.
* Support feeds without GUIDs.
* Items nominated with the bookmarklet now have their set author trump the author data set from mirrored feed items.
* Assure we have backwards compatibility for the API with PHP 5.3
* Fixing our filtering of Post Meta to respect the $single argument.
* Add expected WordPress pagination headers to the API response.
* A wide variety of small fixes to assure unit testing works from PHP 5.3 through PHP 7.2
* Updated our Text Stats library to match backwards compatibility expectations.
* Allow current version of Nominate This to support the standard WordPress Metabox API.
* Bookmarklet use of Send to Draft/Publish now properly shows as drafted in the nomination panel.
* Allow a feed to have a default author set.

= 5.1.1 =
* Changed when source_url is fetched.
* Optimized the comment query.
* Optimized relationship queries.
* Pre-fetch folders.

= 5.1.0 =
* Users can now receive email notifications when an item is nominated.
* The PressForward API can now be filtered using query arguments.
* PressForward is now compatible with the WPMU SmartCrawl SEO plugin.
* Fixed a bug that prevented items that were nominated and also sent to draft by the same user from appearing in posts.
* Fixed a bug created by emojis.
* Fixed a bug that prevented featured images from being added.
* Fixed a bug that allowed for images to be duplicated in the media library when nominated from both the Nominate This Bookmarklet and All Content.
* Fixed a bug that prevented PressForward from collecting content from certain feeds.
* Fixed a bug that created compatibility issues with older versions of PHP.
* Changed the language on the ‘Add Feeds’ page button to read “Submit” instead of “Save Changes.”
* Fixed a bug to ensure that nominated items in all content remain marked as nominated.
* Items read in Reader View are now marked as “Read” in All Content.
* Fixed a bug that caused items nominated from both the Nominate This Bookmarklet and All Content to be duplicated.

= 5.0.0 =

* Fixed a bug that caused archived posts to be hidden due to pagination.
* Fix a bug that prevented the bookmarklet from increasing the nomination count on nominated posts.
* Fixed multiple bugs with the nomination counting process.
* Functionality of the tabs in Tools restored.
* OPML files no longer include empty outline elements.
* When a feed’s url is updated the preview of the url in Subscribed Feeds now reflects the updated url rather than the GUID.
* New endpoints with statistics about PressForward posts.
* The ```wp-json/pf/v1/stats/general``` endpoint includes general data about the PressForward installation including a count of all published PF posts, a count of all nominations, and a count of all the items.
* The ```wp-json/pf/v1/stats/pf_posted``` endpoint includes data on every PressForward post that has been published. Included are metrics for each post such as a word and sentence count, a flesch-kincaid score, and a list of users who nominated the item
* The ```wp-json/pf/v1/stats/authors``` endpoint includes statistics about the authors of PressForward published posts.
* Stats tab in the Tools panel now displays a count of each author published and the number of published posts per feed.
* New feature prompts users to opt-in or out to allow PressForward to collect non-sensitive diagnostic data about usage.
* Changes to how an item ID is generated to prevent duplication when a post is nominated via All Content and the Bookmarklet.


= 4.3.0 =

* Restructured Nominate This. It should now work with servers with non-standard WP_ADMIN folder configurations and those hosts who turn off direct access to the plugin folder, including HostGator and Reclaim. Users who could not get the bookmarklet to function should remove the bookmarklet from their Bookmark Bar and replace it.
* Conflicts with other plugins' options and settings pages are resolved.
* Prepared Readability Endpoint
* Redirects on published and forwarding posts will now redirect faster.
* Fix for post save interception leaking to other non-standard post types.
* Fixed Post Meta intercept breaking downstream filtering functions that also intercepted post meta.
* Add support for Dublin Core publisher information in PressForward's outbound RSS feed.
* Allow developers to filter the date limiting of RSS ingestion through filter `pf_rss_ingestion_retrieve_after_date`
* Nominate This errors no longer show if the site is not in WP_DEBUG mode.
* Significant improvements to Readability's parsing and selection of article content.
* Increase compatibility with Schema.org on-page meta.
* No longer throws error if MBString library is not active in PHP
* The Refresh All Feeds debugging tool shows its results properly again.
* Nominations without authors have default authors.

= 4.2.1 =

* Added feeds, feed items, and folders to the PressForward endpoint.
* Optimized the the retrieval code.
* Updated jQuery library and switched from jQuery Toggle to Click.
* Enhancements to the post delete function.
* Optimized overall Javascript features.
* Optimized old item deletion.
* Readability library optimized and cleaned for general release.
* Improved relationship table install and check process.
* PressForward post meta keys are now registered with WordPress Core functions.
* Assured compatibility with 4.7
* Optimized item link tracking for published posts.
* Added additional checks for keywords to add to the tags in the Nominate This bookmarklet.
* Fixed a bug that prevented the bookmarklet from retrieving tags
* Fixed an issue that caused the interface to stop functioning on Windows 8 touch screens.
* Word counts recorded in post metadata are now accurate.
* Fixed a conflict between PressForward and Jetpack.
* Repaired broken unit test methodology.
* A more useful error when Nominate This can’t add a post to the database.

= 4.1.1 =

* Patch for issues stemming from PHP version 5.4 compatibility.

= 4.1.0 =
* Added PressForward data to the WP-API post endpoint.
* Drastically decreased PressForward’s potential impact on overall site queries.
* Fixes a bug where servers without cURL would fail to load pages or Nominate This.
* Fixed bug that prevented Project MUSE RSS feeds from loading.
* Assured that readability catches all content on sites where the “div” structure is unclear.
* Fixed a bug that broke the outbound OPML link
* Assured that editing a folder does not redirect users to Post Categories in the WordPress menu
* Fixed a bug that caused large OPML subscriptions to trigger an error
* Assure that readability is catching content in the references block

= 4.0.4 =

* Remove cURL code that was causing some servers to crash unexpectedly.

= 4.0.2 =

* Removed forwarding alert.

= 4.0.0 =

* Added support for YouTube RSS Feeds
* Nominate This now pulls in existing tags and allows users to create new tags.
* Enhanced the identification of featured images in posts
* Added support for YouTube oEmbeds
* Increased database efficiency.
* Moved all meta value management to internal library.
* Fixed bug that caused item_author to default to first user in site if no author was identified in the post. If no author is found, the author will now default to the title of the source feed.
* Resolved issue that caused nominate this button to disappear
* Resolved issue where posts with URLs whose length exceeded the GUID field maximum in the WordPress post table could not be processed.
* Fixed issue where canonical URLs were not respected by Nominate This.
* Removed potential excess extra post entries created by Nominate This.

= 3.9.1 =

* Added check for potential conflict of shared library file.

= 3.9 =

* Moved Core code to dependency injection system.
* Removed compatibility with PHP5.2 and added warning for those attempting to activate on PHP5.2 or lower.
* Added additional hooks to the Nominate This bookmarklet and process to allow for additional functionality.
* Improve OpenGraph parsing
* Better remove errors from the response in AJAX handlers
* Resolve occasional activation error due to interface mis-match.

= 3.8 =
* A new option in preferences allows site administrators to change the location of the source link from the bottom of the post to the top.
* Restored “Send to Draft” functionality in All Content.
* List view now has a white background to conform to accessibility standards.
* Fixed a bug where the RSS-Quick add methodology would fail upon upload of an OPML that includes broken links.
* Fixed a bug where the “Nominate This” bookmarklet failed when nominating the same article twice.
* Fixed a user permissions error which prevented Author level contributors from adding a feed (when the Feeder menu preference was changed to allow author access).
* Fixed a bug where the “Nominate” button appeared as “Submit for Review” for contributors in the Bookmarklet.
* Ensured that feeds in OPMLs will have their title retrieved after submission.
* Made adjustments to ensure that Gothamist and Medium articles can be nominated via the Nominate This bookmarklet
* Changed quick edit post statuses to feed statuses in Subscribed Feeds.


= 3.7 =
* Administrators and Contributors can now submit a feed through the Nominate This bookmarklet.  Feeds are drafted and appear in yellow as “Under Review” in subscribed feeds.
* Created a template tag that can be added to a theme’s post template to reveal any internal PressForward comments on an item.
* Added a “Filter by Drafted” option to both All Content and Nominated.
* Added the ability to filter by unread items in All Content.
* Individual feeds can now be manually refreshed in Subscribed Feeds.
* The Site Redirect preference can now be overridden on a post by post basis in the Edit Post page.
* Created a “Clean Up” button to trigger the deletion process of old feed items.
* Leveraging the Heartbeat API, a new Feed retrieval progress bar appears in the Tools menu and indicates where PressForward is in the feed retrieval process.
* New column in Subscribed Feeds displays the total number of items retrieved from each feed.
* The feed url is now displayed in the Subscribed Feeds list.
* Alerted feeds now display the error message beneath the feed title in Subscribed Feeds.
* A count of alerted feeds now displays next to the Subscribed Feeds menu item.
* Alerted and pending feeds are now colored either red (alerted) or yellow (under review) in Subscribed feeds rather than appearing hidden.
* Added an option to set Press Forward’s feed reader to ignore all alerts and never make the feed inactive.
* Added an “Edit feed” link to the success and failure messages in Add Feeds.
* Adjusted transient feed ids to prevent problems with adding, removing, and reading OPML files.
* The archive action in Nominated is now a site wide action while the hide action in All Content is user specific.
* Archived items can now be unarchived.
* Fixed bug where the Refresh button wasn’t showing responses outside of Debug mode.
* Fixed a bug that caused the info box to remain open once the user has clicked out.
* Fixed a bug where the scroll location would reset when opening the comments dialog box.
* Fixed bug where the source link was sporadically generated for posts nominated or drafted using Nominate This!
* Nominate This now generates an error message rather than failing when no HTML is found.
* Nominate This button styling updated.
* Source link is now properly attached to Nominate This nominations.
* Fixed a bug where Send to Draft wasn’t working on some items.

= 3.6 =
* Added the ability to subscribe to an OPML file.
* OPML subscription imports any folders included in the OPML file.
* Added preference field for minutes between feed retrieval cycles.
* Added an OPML file link to Tools menu. Allows administrators to share their subscription list as an OPML file.
* Added edit link to the success/fail message when a feed is added.
* Added an in browser screen that indicates loading when a large OPML is uploaded.
* Added Folders and sorting by folders and feeds to the "Nominated" panel.
* Layout enhancements to the Add Feeds and Tools panels.
* Added a subscribe to feed option in the Nominate This bookmarklet.
* Added an option for users to select what post type and status nominations take on when they are sent to the next step.
* Fixed bug where alerts weren't closed upon successful feed retrieval.
* Fixed bug causing "Nominated" usernames to repeat.
* 'Save Inactive' in the "Edit Feed" page no longer saves the feed as 'Pending'.
* When sorting by 'Last Time Feed Checked' results now include unchecked feeds.
* Fixed in-modal comments in Nominated panel.
* Fixed bug where the Open Graph image was not being pulled in through nomination process.
* Fixed bug related to insure PressForward properly hooks to the Yoast SEO plugin's Open Graph and Canonical values.
* Fixed bug where the author custom field was no longer overriding WordPress author.
* Fixed bug where clean-up of old posts after user-set period was not cleaning up posts.

= 3.5 =
* Now checks for known aggregation services to insure proper resolution of URLs.
* Created the Amplify dropdown button in the actions bar.
* Added button to share to Twitter to the Amplify dropdown.
* Added button to Amplify to send from All Content directly to draft.
* For clarity, changed Last Retrieved column title to Last Time Item Retrieved.
* Added OG URL tag to forwarded pages.
* Updated Readability script to detect Medium.
* Updated Readability script to retrieve pages using File Get Contents when no other methods work.
* Separated out a number of template functions into their own class.
* Attach the navbar on scroll.
* Moved Refresh button to Tools.
* Better assure that the system retrieves open graph images and sets them as Featured Images.
* Allows feed URLs to be changed via Quick Edit.
* Set up functions to better detect current page.
* Set up a debug function for displaying current page.
* If WordPress installs are greater than 4.0, all feeds not in folders will display under folders.
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
