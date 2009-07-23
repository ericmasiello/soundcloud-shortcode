<?php
/*
Plugin Name: SoundCloud Shortcode
Plugin URI: http://www.soundcloud.com
Description: SoundCloud Shortcode. Usage in your posts: [soundcloud]http://soundcloud.com/TRACK_PERMALINK[/soundcloud] . Works also with set or group instead of track. You can provide optional parameters height/width/params like that [soundcloud height="82" params="auto_play=true"]http....
Version: 1.1.4
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

add_filter("pre_kses", "soundcloud_reverse_shortcode");
function soundcloud_reverse_shortcode_preg_replace_callback($a){
  preg_match('/(.*)&?url=([^&]+)&?(.*)/', str_replace("&amp;", "&", $a[3]), $params);
  return('[soundcloud width="'. $a[1] .'" height="'. $a[2] .'" params="'. 
         $params[1] . $params[3] .'"]' . urldecode($params[2]) . '[/soundcloud]' );
}

function soundcloud_reverse_shortcode($content){
  $pattern = '/<object.*width="([^"]+)".*height="([^"]+)".*src="http:\/\/.*soundcloud\.com\/player.swf\?(.*)".*<\/object>/U';
  return(preg_replace_callback($pattern, 'soundcloud_reverse_shortcode_preg_replace_callback', $content));
}

add_shortcode("soundcloud", "soundcloud_shortcode");
function soundcloud_shortcode($atts,$url) {
  extract(shortcode_atts(array('params' => "", 'height' => "", 'width' => "100%"), $atts));
  $encoded_url = urlencode($url);
  if($url = parse_url($url)){
    $splitted_url = split("/",$url['path']);
    $media_type = $splitted_url[count($splitted_url) - 2];
  
    if($height == ""){
      if($media_type == "groups" || $media_type == "sets"){
        $height = "225";
      }else{
        $height = "81";
      }
    }
    $player_params = "url=$encoded_url&g=1&$params";
  
    return "<object height=\"$height\" width=\"$width\"><param name=\"movie\" value=\"http://".$url['host']."/player.swf?$player_params\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed allowscriptaccess=\"always\" height=\"$height\" src=\"http://".$url['host']."/player.swf?$player_params\" type=\"application/x-shockwave-flash\" width=\"$width\"> </embed> </object>";
  }
}
?>