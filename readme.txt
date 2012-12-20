=== Plugin Name ===
Contributors: trepmal
Donate link: http://kaileylampert.com/donate
Tags: storenvy
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 0.4

Get and display items from your Storenvy shop

== Description ==

This is a plugin for getting items from a Storenvy shop and displaying them. It gets the info from the 'products.rss' feed, so only information that's in the feed can be pulled in to your page/post (title, picture, description, date item was added)
The plugin allows you to configure how the info is displayed. How many items to show, which info is displayed...

The plugin has not been tested with anything prior to WordPress 3.0 (at least not recently)

Supports shorcodes and template tags.


== Installation ==

1. Upload `storenvy` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Find Settings > Storenvy
1. Enter your store's URL
1. Place [storenvy] in a page or post

== Frequently Asked Questions ==

= [some feature] isn't working, what's up with that? =

This is definitely still in beta. While the plugin works for my tests, I need more feedback to try and get it to rock-solid status.
If you find something that doesn't work, **please let me know (trepmal (at) gmail (dot) com)** rather than just leaving poor feedback.

= The layout is screwy =

Try adding `div.se-item { clear:both; }`

Better yet, find some CSS tutorials so you can fully customize the layout.

== Screenshots ==

1. 

== Other Notes ==

= Old Vimeo Walkthrough =
[vimeo http://vimeo.com/22814757]

== Upgrade Notice ==

= 0.4 =
* Works again. Basic start to get you going again.

== Changelog ==

= 0.4 =
* Beta again! (errr... still.)
* Works with new Storenvy API
* Hopefully a good start to get you going again. Basic options for now, will aim to get more added soon.

= 0.3 =
* Still "beta"
* Add default CSS
* Little bit of code cleanup

= 0.2 =
* (Still in beta) Better code for retrieving the RSS file
* whitespace cleanup

= 0.1 =
* First Beta release