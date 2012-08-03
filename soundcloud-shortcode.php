<?php
/*
Plugin Name: SoundCloud Shortcode
Plugin URI: http://wordpress.org/extend/plugins/soundcloud-shortcode/
Description: Converts SoundCloud WordPress shortcodes to a SoundCloud widget. Example: [soundcloud]http://soundcloud.com/forss/flickermood[/soundcloud]
Version: 2.1
Author: SoundCloud Inc.
Author URI: http://soundcloud.com
License: GPLv2

Original version: Johannes Wagener <johannes@soundcloud.com>
Options support: Tiffany Conroy <tiffany@soundcloud.com>
HTML5 & oEmbed support: Tim Bormans <tim@soundcloud.com>
*/


/* Register oEmbed provider
   ========================================================================== */

wp_oembed_add_provider('#https?://(?:api\.)?soundcloud\.com/.*#i', 'http://soundcloud.com/oembed', true);


/* Register SoundCloud shortcode
   ========================================================================== */

add_shortcode("soundcloud", "soundcloud_shortcode");

/**
 * SoundCloud shortcode handler
 * @param  {string|array}  $atts     The attributes passed to the shortcode like [soundcloud attr1="value" /].
 *                                   Is an empty string when no arguments are given.
 * @param  {string}        $content  The content between non-self closing [soundcloud]…[/soundcloud] tags.
 * @return {string}                  Widget embed code HTML
 */
function soundcloud_shortcode($atts, $content = null) {

  // We need to use the WP_Embed class instance
  global $wp_embed;

  // Custom shortcode options
  $shortcode_options = array_merge(array('url' => trim($content)), is_array($atts) ? $atts : array());

  // User preference options
  $plugin_options = array_filter(array(
    'iframe' => soundcloud_get_option('player_iframe', true),
    'width'  => soundcloud_get_option('player_width'),
    'height' =>  soundcloud_url_has_tracklist($shortcode_options['url']) ? soundcloud_get_option('player_height_multi') : soundcloud_get_option('player_height'),
    'params' => http_build_query(array_filter(array(
      'auto_play'     => soundcloud_get_option('auto_play'),
      'show_comments' => soundcloud_get_option('show_comments'),
      'color'         => soundcloud_get_option('color'),
      'theme_color'   => soundcloud_get_option('theme_color'),
    ))),
  ));

  // plugin options < shortcode options
  $options = array_merge(
    $plugin_options,
    $shortcode_options
  );

  // The "url" option is required
  if (!isset($options['url'])) { return ''; }

  // Both "width" and "height" need to be integers
  if (isset($options['width']) && !preg_match('/^\d+$/', $options['width'])) {
    // set to 0 so oEmbed will use the default 100% and WordPress themes will leave it alone
    $options['width'] = 0;
  }
  if (isset($options['height']) && !preg_match('/^\d+$/', $options['height'])) { unset($options['height']); }

  // The "iframe" option must be true to load widget via oEmbed
  $oEmbed = soundcloud_booleanize($options['iframe']);

  if ($oEmbed) {
    // This handler handles calling the oEmbed class
    // and more importantly will also do the caching!
    $embed = $wp_embed->shortcode($options, $options['url']);

    // Unfortunately WordPress only passes on "width" and "height" options
    // so we have to add custom params ourselves.
    if (isset($options['params'])) {
      $embed = soundcloud_oembed_params($embed, $options['params']);
    }

    return $embed;

  } else {
    // We can’t use default WordPress oEmbed implementation since
    // it doesn’t support sending the iframe=false parameter.
    return soundcloud_flash_widget($options);
  }

}

/**
 * Plugin options getter
 * @param  {string|array}  $option   Option name
 * @param  {mixed}         $default  Default value
 * @return {mixed}                   Option value
 */
function soundcloud_get_option($option, $default = false) {
  $value = get_option('soundcloud_' . $option);
  return $value === '' ? $default : $value;
}

/**
 * Booleanize a value
 * @param  {boolean|string}  $value
 * @return {boolean}
 */
function soundcloud_booleanize($value) {
  return is_bool($value) ? $value : $value === 'true' ? true : false;
}

/**
 * Decide if a url has a tracklist
 * @param  {string}   $url
 * @return {boolean}
 */
function soundcloud_url_has_tracklist($url) {
  return preg_match('/^(.+?)\/(sets|groups|playlists)\/(.+?)$/', $url);
}

/**
 * Add custom parameters to iframe embed code
 * @param  {string}  $embed  Embed code (html)
 * @param  {string}  $query  Parameters querystring
 * @return {string}          Embed code with added parameters
 */
function soundcloud_oembed_params($embed, $query) {
  // Needs to be global because we can’t pass parameters to regex callback function < PHP 5.3
  global $soundcloud_oembed_query;
  $soundcloud_oembed_query = $query;
  return preg_replace_callback('/src="(https?:\/\/(?:w|wt)\.soundcloud\.(?:com|dev)\/[^"]*)/i', 'soundcloud_oembed_params_callback', $embed);
}

/**
 * Parameterize url
 * @param  {array}  $match  Matched regex
 * @return {string}          Parameterized url
 */
function soundcloud_oembed_params_callback($match) {
  global $soundcloud_oembed_query;

  // Convert oEmbed query to array
  parse_str(html_entity_decode($soundcloud_oembed_query), $oembed_query_array);
  // Convert URL to array
  $url = parse_url(urldecode($match[1]));
  // Convert URL query to array
  parse_str($url['query'], $query_array);
  // Build new query string
  $query = http_build_query(array_merge($query_array, $oembed_query_array));

  return 'src="' . $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $query;
}

/**
 * Legacy Flash widget embed code
 * @param  {options}  $options  Querystring
 * @return {string}             Flash embed code
 */
function soundcloud_flash_widget($options) {

  $params = array();
  // Create an array of 'param=value&param2=value' string
  if (isset($options['params'])) {
    parse_str(html_entity_decode($options['params']), $params);
  }
  // Merge in "url" value
  $params = array_merge(array(
    'url' => $options['url']
  ), $params);

  // Build URL
  $url = 'http://player.soundcloud.com/player.swf?' . http_build_query($params);
  // Set default width if not defined
  $width = isset($options['width']) && $options['width'] !== 0 ? $options['width'] : '100%';
  // Set default height if not defined
  $height = isset($options['height']) && $options['height'] !== 0 ? $options['height'] : (soundcloud_url_has_tracklist($options['url']) ? '255' : '81');

  return preg_replace('/\s\s+/', "", sprintf('<object width="%s" height="%s">
                                <param name="movie" value="%s"></param>
                                <param name="allowscriptaccess" value="always"></param>
                                <embed width="%s" height="%s" src="%s" allowscriptaccess="always" type="application/x-shockwave-flash"></embed>
                              </object>', $width, $height, $url, $width, $height, $url));
}


/* Register reverse shortcode filter
   ========================================================================== */

// Disabling this for now because it seems to mess up content it shouldn’t
// http://wordpress.org/support/topic/plugin-soundcloud-shortcode-disastrous-update-223-breaks-encoding-of-existing-and-new-posts-when-saving
// http://wordpress.org/support/topic/plugin-soundcloud-shortcode-postpage-content-not-saved-beyond-nbsp-etc-when-plugin-is-activated
// add_filter("content_save_pre", "soundcloud_reverse_shortcode");

/**
 * Replace SoundCloud <iframe> widgets with [soundcloud] shortcodes
 * @param  {string} $content
 * @return {string}
 */
function soundcloud_reverse_shortcode($content) {
  return preg_replace_callback('/<iframe(?:(?!>).)*>.*?<\/iframe>/i', 'soundcloud_reverse_shortcode_callback', stripslashes(html_entity_decode($content)));
}
/**
 * Matched iframe regex result to shortcode
 * @param  {array}   $match  Matches
 * @return {string}          Shortcode
 */
function soundcloud_reverse_shortcode_callback($match) {

  $tag = $match[0];
  $dom = new DOMDocument();
  @$dom->loadHTML($tag);

  $iframeElement = $dom->getElementsByTagName('iframe')->item(0);

  $src = $iframeElement->getAttribute('src');
  if (!preg_match('/w.soundcloud.com/i', $src)) { return $tag; }

  $srcObj   = parse_url($src);
  $srcQuery = isset($srcObj['query']) ? $srcObj['query'] : '';

  // Create an array of querystring
  parse_str(html_entity_decode($srcQuery), $params);

  $url    = isset($params['url']) ? $params['url'] : '';
  unset($params['url']);
  $params = http_build_query($params);
  $width  = $iframeElement->getAttribute('width');
  $height = $iframeElement->getAttribute('height');

  return sprintf('[soundcloud url="%s" width="%s" height="%s" params="%s" iframe="true" /]', $url, $width, $height, $params);
}


/* Settings
   ========================================================================== */

/* Add settings link on plugin page */
add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'soundcloud_settings_link');

function soundcloud_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=soundcloud-shortcode">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

/* Add admin menu */
add_action('admin_menu', 'soundcloud_shortcode_options_menu');
function soundcloud_shortcode_options_menu() {
  add_options_page('SoundCloud Options', 'SoundCloud', 'manage_options', 'soundcloud-shortcode', 'soundcloud_shortcode_options');
  add_action('admin_init', 'register_soundcloud_settings');
}

function register_soundcloud_settings() {
  register_setting('soundcloud-settings', 'soundcloud_player_height');
  register_setting('soundcloud-settings', 'soundcloud_player_height_multi');
  register_setting('soundcloud-settings', 'soundcloud_player_width ');
  register_setting('soundcloud-settings', 'soundcloud_player_iframe');
  register_setting('soundcloud-settings', 'soundcloud_auto_play');
  register_setting('soundcloud-settings', 'soundcloud_show_comments');
  register_setting('soundcloud-settings', 'soundcloud_color');
  register_setting('soundcloud-settings', 'soundcloud_theme_color');
}

function soundcloud_shortcode_options() {
  if (!current_user_can('manage_options')) {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
?>
<div class="wrap">
  <h2>SoundCloud Shortcode Default Settings</h2>
  <p>These settings will become the new defaults used by the SoundCloud Shortcode throughout your blog.</p>
  <p>You can always override these settings on a per-shortcode basis. Setting the 'params' attribute in a shortcode overrides these defaults individually.</p>

  <form method="post" action="options.php">
    <?php settings_fields( 'soundcloud-settings' ); ?>
    <table class="form-table">

      <tr valign="top">
        <th scope="row">Widget Type</th>
        <td>
          <input type="radio" id="player_iframe_true"  name="soundcloud_player_iframe" value="true"  <?php if (strtolower(get_option('soundcloud_player_iframe')) === 'true')  echo 'checked'; ?> />
          <label for="player_iframe_true"  style="margin-right: 1em;">HTML5</label>
          <input type="radio" id="player_iframe_false" name="soundcloud_player_iframe" value="false" <?php if (strtolower(get_option('soundcloud_player_iframe')) === 'false') echo 'checked'; ?> />
          <label for="player_iframe_false" style="margin-right: 1em;">Flash</label>
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Player Height for Tracks</th>
        <td>
          <input type="text" name="soundcloud_player_height" value="<?php echo get_option('soundcloud_player_height'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Player Height for Groups/Sets</th>
        <td>
          <input type="text" name="soundcloud_player_height_multi" value="<?php echo get_option('soundcloud_player_height_multi'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Player Width</th>
        <td>
          <input type="text" name="soundcloud_player_width" value="<?php echo get_option('soundcloud_player_width'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Current Default 'params'</th>
        <td>
          <?php echo http_build_query(array_filter(array(
            'auto_play'     => get_option('soundcloud_auto_play'),
            'show_comments' => get_option('soundcloud_show_comments'),
            'color'         => get_option('soundcloud_color'),
            'theme_color'   => get_option('soundcloud_theme_color'),
          ))) ?>
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Auto Play</th>
        <td>
          <label for="auto_play_none"  style="margin-right: 1em;"><input type="radio" id="auto_play_none"  name="soundcloud_auto_play" value=""      <?php if (get_option('soundcloud_auto_play') == '')      echo 'checked'; ?> />Default</label>
          <label for="auto_play_true"  style="margin-right: 1em;"><input type="radio" id="auto_play_true"  name="soundcloud_auto_play" value="true"  <?php if (get_option('soundcloud_auto_play') == 'true')  echo 'checked'; ?> />True</label>
          <label for="auto_play_false" style="margin-right: 1em;"><input type="radio" id="auto_play_false" name="soundcloud_auto_play" value="false" <?php if (get_option('soundcloud_auto_play') == 'false') echo 'checked'; ?> />False</label>
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Show Comments</th>
        <td>
          <label for="show_comments_none"  style="margin-right: 1em;"><input type="radio" id="show_comments_none"  name="soundcloud_show_comments" value=""      <?php if (get_option('soundcloud_show_comments') == '')      echo 'checked'; ?> />Default</label>
          <label for="show_comments_true"  style="margin-right: 1em;"><input type="radio" id="show_comments_true"  name="soundcloud_show_comments" value="true"  <?php if (get_option('soundcloud_show_comments') == 'true')  echo 'checked'; ?> />True</label>
          <label for="show_comments_false" style="margin-right: 1em;"><input type="radio" id="show_comments_false" name="soundcloud_show_comments" value="false" <?php if (get_option('soundcloud_show_comments') == 'false') echo 'checked'; ?> />False</label>
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Color</th>
        <td>
          <input type="text" name="soundcloud_color" value="<?php echo get_option('soundcloud_color'); ?>" /> (color hex code e.g. ff6699)<br />
          Defines the color to paint the play button, waveform and selections.
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Theme Color</th>
        <td>
          <input type="text" name="soundcloud_theme_color" value="<?php echo get_option('soundcloud_theme_color'); ?>" /> (color hex code e.g. ff6699)<br />
          Defines the background color of the player.
        </td>
      </tr>

    </table>

      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
      </p>

  </form>
</div>
<?php
}
?>
