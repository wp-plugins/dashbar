=== DashBar ===
Contributors: z720
Tags: admin
Requires at least: 2.5
Tested up to: 3.0
Stable tag: 2.7.2

Display a Enhanced WordPress.com-like navigation bar for logged users: direct acces to Dashboard, Write, Edit, Awaiting Moderation, Profile...

== Description ==

This plugin allows logged in user to see an administration bar like the one displayed at [WordPress.com](http://wordpress.com).

The bar is displayed on every page of the frontend, with links to :

* DashBoard
* Profile
* Logout
* Manage (Posts, Pages, Links, Files)
* Write Post

and with contextual links to posts functions 

* Edit this post if single
* Choose from the list of posts displayed to edit one
* Comment moderation if moderation queue has comments to moderate

The look and feel of the bar is easy to customize via **Options**>**DashBar**.

Use the pot file included to localize the bar to your language. Languages currently available :

* English (Native as WordPress)
* French
* Japanese (thanks to Yoshitaka Fukunaga http://www.rhein-strasse.de/ )
* Belorussian (thanks to http://www.fatcow.com/ )

Feel free to send me your PO/MO files, I'll put them in the next release

== Changelog ==

Version 2.7.2: Added Belorussian and Japanese translation

== Installation ==

Install from the repository or:

1. Upload the content of the directory DashBar to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots == 

1. Standard DashBar view from the frontend
2. Custom DashBar look and feel editor

== Frequently Asked Questions ==

= How can I share my localization ? =

Don't hesitate to let me know that you have localized the plugin in an other language. [Drop me a mail](http://z720.net/about/contact "Contact me")

= What the hell, it doesn't work. What can I do ? =

[Drop me a mail](http://z720.net/about/contact "Contact me") with any relevant data: WordPress version, theme, active plugins... I'll do my best to fix the issue.

= My plugin is a really cool feature and I want to add a link in the DashBar =

DashBar declares a DashBarLink object you can use in combination with the DashBar hook.
 1. Create e new DashBarLink instance: $mylink = new DashBarLink($label, $url, $credential (optional)) 
 2. Add it to the List : add_filter('DashBar_pre_link', function($list) { $list[] = $mylink; return $list; });

This example might not work. But if you made a plugin. You got it...
