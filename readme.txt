=== SoundCloud Shortcode ===
Contributors: jwagener
Donate link: http://soundcloud.com
Tags: soundcloud, flash, player, shortcode,
Requires at least: 2.5.0
Tested up to: 2.7
Stable tag: trunk

The SoundCloud Shortcode plugin allows you to integrate a player widget from SoundCloud into your Wordpress Blog by using a Wordpress shortcodes.

== Description ==

The SoundCloud Shortcode plugin allows you to easily integrate a player widget for a track, set or group from SoundCloud into your Wordpress Blog by using a Wordpress shortcode. 
Use it like that in your blog post: [soundcloud]http://soundcloud.com/LINK_TO_TRACK_SET_OR_GROUP[shortcode]
It also supports these optional parameters: width, height and params.
The "params" parameter will pass the given options on to the player widget.
Our player accepts the following parameter options:

* auto_play = (true or false)
* show_comments = (true or false)
* color = (color hex code) will paint the play button, waveform and selections in this color
* theme_color = (color hex code) will set the background color

Examples:

`[soundcloud params="auto_play=true&show_comments=false"]http://soundcloud.com/forss/flickermood[/soundcloud]`
Embed a track player which starts playing automaticly and won't show any comments.

`[soundcloud params="color=33e040&theme_color=80e4a0"]http://soundcloud.com/forss/sets/live-4[/soundcloud]`
Embeds a set player with a green theme.

`[soundcloud height="150" width="250"]http://soundcloud.com/groups/experimental[/soundcloud]` 
Embeds a group player with 150px height and 250px width. 

== Installation ==



== Frequently Asked Questions ==


== Screenshots ==

1. This is how the player looks like.

== Changelog ==

= 1.0 =
* First version