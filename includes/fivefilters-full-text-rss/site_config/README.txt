Full-Text RSS Site Patterns
---------------------------

NOTE: The information here is not up to date, but probably covers what you need for this version. For the most up to date information on Full-Text RSS site patterns, please see http://help.fivefilters.org/customer/portal/articles/223153-site-patterns

We recommend using the latest release of Full-Text RSS - available for purchase at http://fivefilters.org/content-only/#download - which also comes bundled with hundreds of site patterns to improve extraction.

Site patterns allow you to specify what should be extracted from specific sites.

How it works
------------
After we fetch the contents of a URL, we use the hostname (e.g. example.org) and check to see if a config file exists for that hostname. If there is a matching file (e.g. example.org.txt) in one of the config folders, we will fetch the rules it contains. If no such file is found, we attempt to detect the appropriate content block and title automatically. When there is a matching site config, we first try to use the patterns within to extract the content. If these patterns fail to match, we will, by default, revert to auto-detection - this gives us another chance to get at the content (useful when site redesigns invalidate our stored patterns).

The 'standard' folder contains site config files bundled with Full-Text RSS. Users may contribute their own patterns and we will try to update with each release.

The 'custom' folder can be used for sites not listed in standard, or to override sites in standard. If a site has an entry in both folders, only the one in 'custom' will be used. The custom folder allows you to separate your entries from the bundled ones, which also makes the task of upgrading to a new release easier (you benefit from the updated patterns in standard and copy over your existing patterns to custom).

The pattern format has been borrowed from Instapaper. Please see http://blog.instapaper.com/post/730281947 and http://www.instapaper.com/bodytext (requires login). We make use of the patterns provided by Instapaper and, in the same spirit, will soon make available our own additions.


Command reference (based on Instapaper)
---------------------------------------
title: [XPath]
The page title.
XPaths evaluating to strings are also accepted. 
Multiple statements accepted.
Will evaluate in order until a result is found.

body: [XPath]
The body-text container. Auto-detected by default.
Multiple statements accepted.
Will evaluate in order until a result is found.

strip: [XPath]
Strip any matching element and its children.
Multiple statements accepted.

strip_id_or_class: [string]
Strip any element whose @id or @class contains this substring.
Multiple statements accepted.

strip_image_src: [string]
Strip any <IMG> whose @src contains this substring.
Multiple statements accepted.

tidy: [yes|no] (default: yes)
Preprocess with Tidy. May cause "no text" errors.

prune: [yes|no] (default: yes)
Strip elements within body that do not resemble content elements.

autodetect_on_failure: [yes|no] (default: yes)
If set to no, we will not attempt to auto-detect the title or content block.

test_url: [string]
Must be URL of an article from this site, not the site's front page.

# comments
Lines beginning with # are ignored.