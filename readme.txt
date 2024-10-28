=== bbPress Topic Sections ===
Contributors: grosbouff 
Tags: bbpress,buddypress,topic content,split,fields,description,textarea
Requires at least: Wordpress 3, bbPress 2
Tested up to: Wordpress 3.5.2, bbPress 2.2.4
Stable tag: trunk
License:GPLv2 or later
Donate link:http://bit.ly/gbreant

bbPress Topic Sections allows to split the topic content field into several sections.

== Description ==

bbPress Topic Sections allows to split the topic content field into several sections, allowing to setup more detailled topic forums; eg. for having an ads forum.

The content of the topic sections is just added to the post content (when saving a post); and extracted when editing/displaying it.
Which means that it gracefully degrades if you choose at some point to disable the plugin !

In the Administration Panel, there will be a new box "Topic Sections" on the forums pages, that works like regular post tags.
If you add some topics sections there, it will add new section fields when creating / editing a topic under this forum.

Under Forums > Topic Sections, you can edit each of your topic sections to add a description (that will be shown on the forms) and setup
several parameters for the section :

* section required (validation not yet implemented)
* maximum characters allowed (validation not yet implemented)
* WYSIWYG editor enabled

Built with a lot of hooks so you can customize it easily !


== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.
== Frequently Asked Questions ==

== Frequently Asked Questions ==


== Screenshots ==

1. Topic Sections box when editing a forum backend
2. Special settings when editing a topic section
3. Topic form with 2 topic sections
4. How the topic is displayed after that...

== Changelog ==
= 1.0.3 =
* In 'setup_actions()', replaced wordpress hooks by bbpress hooks (to avoid plugin to crash while bbPress is not enabled)
= 1.0.2 =
* Preserve line breaks
* Some bug fixes
= 1.0.0 =
First release