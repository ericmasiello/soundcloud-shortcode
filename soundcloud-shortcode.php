<?php
/*
Plugin Name: SoundCloud Shortcode
Plugin URI: http://www.soundcloud.com
Description: SoundCloud Shortcode. Usage in your posts: [soundcloud]http://soundcloud.com/TRACK_PERMALINK[soundcloud] . Works also with set or group instead of track. You can provide optional parameters height/width/params like that [soundcloud height="82" params="auto_play=true"]http....
Version: 1.0
Author: Johannes Wagener <johannes@soundcloud.com>
Author URI: http://johannes.wagener.cc
*/

/*
SoundCloud Shortcode (Wordpress Plugin)
Copyright (C) 2009 Johannes Wagener

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

add_shortcode("soundcloud", "soundcloud_shortcode");

function soundcloud_shortcode($atts,$url) {
  extract(shortcode_atts(array('params' => "", 'height' => "", 'width' => "100%"), $atts));
  $splitted_url = split("/",$url);
  $player_host = $splitted_url[2];
  $media_type = $splitted_url[count($splitted_url) - 2];
  
  if($height == ""){
    if($media_type == "groups" || $media_type == "sets"){
      $height = "225";
    }else{
      $height = "81";
    }
  }
  $url = urlencode($url);
  $player_params = "url=$url&g=1&$params";
  
  return "<object height=\"$height\" width=\"$width\"><param name=\"movie\" value=\"http://$player_host/player.swf?$player_params\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed allowscriptaccess=\"always\" height=\"$height\" src=\"http://$player_host/player.swf?$player_params\" type=\"application/x-shockwave-flash\" width=\"$width\"> </embed> </object>";
}
?>