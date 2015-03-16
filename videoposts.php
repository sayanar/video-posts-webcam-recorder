<?php
/*
Plugin Name: Video Posts Webcam Recorder
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Recorder+Posts+Comments
Description: Video Posts Webcam Recorder allows WordPress users to record and authors to directly insert videos in their posts. Integrates with VideoShareVOD plugin for advanced video management, multiple players and settings.
Version: 1.85.5
Author: VideoWhisper.com
Author URI: http://www.videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
*/

function videoposts_addbuttons() {
	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "add_videoposts_tinymce_plugin");
		add_filter('mce_buttons', 'register_videoposts_button');

	}
}

function register_videoposts_button($buttons) {
	array_push($buttons, "separator", "recorder");
	array_push($buttons, "separator", "import");
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_videoposts_tinymce_plugin($plugin_array) {
	$plugin_array['recorder'] = home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/editor_plugin.js';
	$plugin_array['import'] = home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/editor_plugin_i.js';
	return $plugin_array;
}

// init process for button control
add_action('init', 'videoposts_addbuttons');


if (!class_exists("VWvideoPosts"))
{
	class VWvideoPosts {

		function VWvideoPosts() { //constructor

		}

		function settings_link($links) {
			$settings_link = '<a href="options-general.php?page=videoposts.php&mod=settings">'.__("Settings").'</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		function recordings_link($links) {
			$recordings_link = '<a href="options-general.php?page=videoposts.php&mod=recordings">'.__("Recordings").'</a>';
			array_unshift($links, $recordings_link);
			return $links;
		}


		function wpse_hold_global_post_number( $post_id, $post )
		{
			global $post_ID;
			if($post_ID) $post_id = $post_ID;
			$recCookie = $_COOKIE["recIdCookie"];

			global $wpdb;
			$table_name = $wpdb->prefix."vw_videorecordings";
			$sql="UPDATE $table_name SET postId = '$post_id' WHERE id = '$recCookie' AND postId = '0'";
			$wpdb->query($sql);
		}

		function shortcode_recorder($atts)
		{
			$options = get_option('VWvideoRecorderOptions');

			if (class_exists("VWvideoShare"))
			{
				$optionsVSV = get_option( 'VWvideoShareOptions' );
				if (!VWvideoShare::hasPriviledge($optionsVSV['shareList'])) return __('You do not have permissions to share videos!', 'videosharevod');
			}

			$atts = shortcode_atts(array('height' => '550px'), $atts, 'videowhisper_recorder');

			$base = plugin_dir_url(__FILE__) . "posts/videowhisper/";
			$swfurl = $base  . "videorecorder.swf";

			$height = $atts['height'];

			$htmlCode .= <<<EOCODE
<div style="height:$height">
<object height="100%" width="100%" type="application/x-shockwave-flash" data="$swfurl">
<param name="base" value="$base" />
<param name="movie" value="$swfurl" />
<param bgcolor="#5a5152" />
<param name="scale" value="noscale" />
<param name="salign" value="lt" />
<param name="allowFullScreen" value="true" />
<param name="allowscriptaccess" value="always" />
</object>
</div>
EOCODE;

			$videowhisper = $options['videowhisper'];
			$state = 'block' ;
			if (!$videowhisper) $state = 'none';

			$poweredby = '<div style=\'display: ' . $state . ';\'><i><small>Powered by <a href=\'http://www.videowhisper.com\'  target=\'_blank\'>VideoWhisper</a>,<a href=\'http://www.videowhisper.com/?p=WordPress+Video+Recorder+Posts+Comments\'  target=\'_blank\'>Video Recorder</a>.</small></i></div>';

			return $htmlCode;
		}

		function post_shortcode($content)
		{
			$options = get_option('VWvideoRecorderOptions');

			$rtmp_server = urlencode($options['rtmp_server']);
			$videowhisper = $options['videowhisper'];
			$player = $options['selectPlayer'];
			$embedmode = $options['embedMode'];

			$embedWidth = $options['embedWidth'];
			$embedHeight = $options['embedHeight'];

			$autoplay = $options['autoplay'];
			$streams_url = $options['videos_url'];
			$videosPath = $options['directory'];
			$state = 'block' ;
			if (!$videowhisper) $state = 'none';

			$poweredby = '<div style=\'display: ' . $state . ';\'><i><small>Powered by <a href=\'http://www.videowhisper.com\'  target=\'_blank\'>VideoWhisper</a>,<a href=\'http://www.videowhisper.com/?p=Video+Recorder\'  target=\'_blank\'> Video Recorder</a>.</small></i></div>';

			preg_match_all("/\[videowhisper stream=\"([a-zA-Z0-9_\-\s]*)\"\]/i",$content,$matches);
			//var_dump($matches);
			$result = $content;
			//echo $player;

			for( $i=0; $i<count($matches[0]);$i++)
			{
				//echo $player;
				$home = home_url();
				$streamname = $matches[1][$i];

				switch($player)
				{
				case 'videosharevod';
					global $wpdb;
					$postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . sanitize_file_name($streamname) . "' and post_type='video' LIMIT 0,1" );

					$playercode = do_shortcode("[videowhisper_player video=\"$postID\"]");
					break;

				case 'vwplayer':
					$playercode = <<<EOD
<div id='vwplayer' style='width:$embedWidth; height:$embedHeight'><object height="100%" width="100%"><param name="movie" value=" $home/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/streamplayer.swf?streamName=$streamname&amp;serverRTMP=$rtmp_server&amp;templateURL=\"><param name="scale" value="noscale"><param name="salign" value="lt"><param name="base" value="$home/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/"><param name="allowFullScreen" value="true"><param name="allowscriptaccess" value="always"><embed base="$home/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/"  scale="noscale" salign="lt" src=" $home/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/streamplayer.swf?streamName=$streamname&amp;serverRTMP=$rtmp_server&amp;templateURL=" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" height="$embedHeight" width="$embedWidth"></object></div>$poweredby
EOD;

					break;
				case 'jwplayer':
					$image = file_exists("wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/$streamname.jpg")?$home."/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/$streamname.jpg":$home."/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/no_video.png";
					$playercode = <<<EOD
<div id='jwplayer_$streamname' style='width: ${embedWidth}; height: ${embedHeight}'><script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js'></script><script type='text/javascript'>var flashvars = { file: '$streamname', streamer: '$rtmp_server', autostart: '$autoplay',width: '${embedWidth}', height: '${embedHeight}', type: 'rtmp', image: '$image' }; swfobject.embedSWF('$home/wp-content/uploads/jw-player-plugin-for-wordpress/player/player.swf','jwplayer_$streamname','$embedWidth','$embedHeight','9','false', flashvars,  {allowfullscreen:'true',allowscriptaccess:'always'},   {id:'jwplayer',name:'jwplayer'}  );</script></div>$poweredby
EOD;
					break;

				case 'ffmpeg':
					if (file_exists($output_file = $videosPath . $streamname  . "-ip.mp4"))
					{
						$image = file_exists("wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/$streamname.jpg")?$home."/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/$streamname.jpg":$home."/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/no_video.png";

						//echo $videosPath.$streams_url;
						$play_url = $streams_url . $streamname  . "-ip.mp4";
						$play_urlw = $streams_url . $streamname  . ".ogv";
						$playercode = <<<EOD
<video width='$embedWidth' height='$embedHeight' autobuffer controls='controls' poster='$image' ><source src='$play_url' type='video/mp4'><source src='$play_urlw' type='video/ogg'>You must have an HTML5 capable browser.</video>$poweredby
EOD;
					} else $playercode = "Video not found: $output_file";
					break;

				}
				$result = str_replace($matches[0][$i],$playercode,$result);
			}
			return $result;
		}

		function init()
		{
			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin",  array('VWvideoPosts','settings_link') );
			add_filter("plugin_action_links_$plugin",  array('VWvideoPosts','recordings_link') );
			add_filter("the_content",array('VWvideoPosts','post_shortcode'));

			add_shortcode('videowhisper_recorder', array( 'VWvideoPosts', 'shortcode_recorder'));

			add_action( 'save_post', array('VWvideoPosts','wpse_hold_global_post_number'), null, 2 );
			// global post ID

			wp_register_sidebar_widget('videopostsWidget','VideoWhisper Video Posts', array('VWvideoPosts', 'widget') );

			//check db
			$vw_recorder_version = "1.2";

			global $wpdb;
			$table_name = $wpdb->prefix."vw_videorecordings";

			$installed_ver = get_option( "vw_recorder_version" );

			if( $installed_ver != $vw_recorder_version )
			{
				$wpdb->flush();

				$sql = "DROP TABLE IF EXISTS `$table_name`;
		CREATE TABLE `$table_name` (
		  `id` int(11) NOT NULL auto_increment,
		  `userId` int(12)  NOT NULL,
		  `postId` int(12)  NOT NULL,
		  `streamname` varchar(64) NOT NULL,
		  `time` int(12)  NOT NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Video Whisper: Recordings - 2009@videowhisper.com' AUTO_INCREMENT=1;";

				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);

				if (!$installed_ver) add_option("vw_recorder_version", $vw_recorder_version);
				else update_option( "vw_recorder_version", $vw_recorder_version );

				$wpdb->flush();
			}


		}

		function menu()
		{
			add_options_page('Video Posts Webcam Recorder Options', 'Video Posts', 9, basename(__FILE__), array('VWvideoPosts', 'options'));
		}

		function getAdminOptions() {

			$adminOptions = array(
				'embedMode' => 1,
				'autoplay' => true,
				'rtmp_server' => 'rtmp://localhost/videowhisper',
				'selectPlayer' => 'vwplayer',
				'embedWidth' => '480px',
				'embedHeight' => '360px',

				'videoCodec'=>'H264',
				'codecProfile' => 'baseline',
				'codecLevel' => '3.1',

				'soundCodec'=> 'Nellymoser',
				'soundQuality' => '9',
				'micRate' => '22',

				'camWidth' => 480,
				'camHeigth' => 360,
				'camFPS' => 30,

				'camBandwidth' => 65536,
				'camMaxBandwidth' => 131072,

				'showCamSettings' => 1,
				'advancedCamSettings' => 1,
				'disablePreview' => 0,
				'layoutCode' => '',
				'fillWindow' => 0,
				'recordLimit' => 600,
				'directory' => '/home/-youraccount-/public_html/streams/',
				'videos_url' => 'http://-yoursite.com-/streams/',
				'ffmpegcall' => '/usr/local/bin/ffmpeg -y -vcodec copy -acodec libfaac -ac 2 -ar 22050 -ab 96k',

				'videowhisper' => 0
			);

			$options = get_option('VWvideoRecorderOptions');
			if (!empty($options)) {
				foreach ($options as $key => $option)
					$adminOptions[$key] = $option;
			}
			update_option('VWvideoRecorderOptions', $adminOptions);
			return $adminOptions;
		}

		function options()
		{
			$mod = $_GET['mod'];
			$model = $_GET['model'];
			if ($mod == '') $mod = 'settings';
			if($mod == 'settings')
			{
				$options = VWvideoPosts::getAdminOptions();


				if (isset($_POST))
				{

					foreach ($options as $key => $value)
						if (isset($_POST[$key])) $options[$key] = $_POST[$key];

						update_option('VWvideoRecorderOptions', $options);
				}



				$options['layoutCode'] = htmlentities(stripslashes($options['layoutCode']));

?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Video Posts Webcam Recorder Settings</h2>
</div>

<p><H3>&gt; Settings |
<a href = "<?php echo home_url();?>/wp-admin/options-general.php?page=videoposts.php&mod=recordings">Recordings List </a></H3> </p>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

<h3>General Settings</h3>

<h4>RTMP Address</h4>
<p>To run this, make sure your hosting environment meets all <a href="http://www.videowhisper.com/?p=Requirements" target="_blank">requirements</a>.  If you don't have a videowhisper rtmp address yet (from a managed rtmp host), go to <a href="http://www.videowhisper.com/?p=RTMP+Applications" target="_blank">RTMP Application   Setup</a> for  installation details.</p>
<input name="rtmp_server" type="text" id="rtmp_server" size="80" maxlength="256" value="<?php echo $options['rtmp_server']?>"/>
<p>For video recordings we recommend <a href="http://www.videowhisper.com/?p=Wowza+Media+Server+Hosting">Wowza RTMP hosting</a>. To enable HTML5 playback, web server with ffmpeg support must be on same physical server as RTMP so scripts have access to video files saved by streaming server.</p>
<?php
				$detectedp[jwplayer] = 0;
				$detectedp[videosharevod] = 0;

				$cmd =$options['ffmpegcall'] ." -codecs";
				exec($cmd, $output, $returnvalue);
				if ($returnvalue == 127) $ffmpegdetected = 0;
				else $ffmpegdetected = 1;


				if (is_plugin_active('jw-player-plugin-for-wordpress/jwplayermodule.php')) $detectedp[jwplayer] = 1;

				if (is_plugin_active('video-share-vod/video-share-vod.php')) $detectedp[videosharevod] = 1;

?>
	<h4>Select Player</h4>
	<select name='selectPlayer' id='selectPlayer'>

	<?php
				if ($detectedp[videosharevod]) {
?>
	<option value='videosharevod' <?php echo $options['selectPlayer'] == 'videosharevod'?"selected":""?>>Video Share VOD</option>
	<?php
				}
?>
	<option value='vwplayer' <?php echo $options['selectPlayer'] == 'vwplayer'?"selected":""?>>VideoWhisper</option>
	<?php
				if ($detectedp[jwplayer]) {
?>
	<option value='jwplayer' <?php echo $options['selectPlayer'] == 'jwplayer'?"selected":""?>>JwPlayer</option>
	<?php
				}
				if ($ffmpegdetected == 1)
				{
?>
	<option value='ffmpeg' <?php echo $options['selectPlayer'] == 'ffmpeg'?"selected":""?>>HTML5</option>
	<?php
				}
?>
	</select>
	<p>Video Share VOD (recommended):
		<?php
				if ($detectedp[videosharevod]) {
					echo "Detected";
				}
				else{
					echo "Not Detected.";
				}
?>
<br>Video Share VOD is a free open source solution to manage videos and  setup video sharing / video on demand (VOD) features on WordPress. Includes multiple options and players.
<br>For more details see <a href='http://videosharevod.com/' target='_blank'>VideoShareVOD Home Site</a> and <a href='http://wordpress.org/plugins/video-share-vod/' target='_blank'>VideoShareVOD on WordPress</a>.</p>
	<p>JwPlayer:
		<?php
				if ($detectedp[jwplayer]) {
					echo "Detected";
				}
				else{
					echo"<a href='http://wordpress.org/extend/plugins/jw-player-plugin-for-wordpress/' target='_blank'> Not Detected </a>";
				}
				?></p>

	<?php


				echo "<p><h4> Conversion tools for HTML5 playback: </h4></p>";


				echo "<table><tr><td> ffmpeg: </td>";
				$cmd ="/usr/local/bin/ffmpeg -codecs";
				exec($cmd, $output, $returnvalue);
				if ($returnvalue == 127)  echo "<td><font color='red'> &nbsp &nbsp &nbsp &nbsp  Not detected: $cmd</font></td></tr>"; else echo "<td><font color='green'> &nbsp &nbsp &nbsp &nbsp  Detected</font></td></tr>";

				//detect codecs
				if ($output) if (count($output))
						foreach (array('h264','faac','speex', 'nellymoser') as $cod)
						{
							$det=0; $outd="";
							echo "<tr><td> $cod codec: </td>";
							foreach ($output as $outp) if (strstr($outp,$cod)) { $det=1; $outd=$outp; };
							if ($det) echo "<td><font color='green'> &nbsp &nbsp &nbsp &nbsp  Detected ($outd)</font></td></tr>"; else echo "<td><font color='red'> &nbsp &nbsp &nbsp &nbsp  Missing: please configure and install ffmpeg with lib$cod</font></td></tr>";
						}

					echo "<tr><td> ffmpeg2theora: </td> ";
				$cmd ="/usr/local/bin/ffmpeg2theora";
				echo exec($cmd, $output, $returnvalue);
				if ($returnvalue == 127)  echo "<td><font color='red'> &nbsp &nbsp &nbsp &nbsp  Not detected: $cmd</font></td></tr>"; else echo "<td><font color='green'> &nbsp &nbsp  &nbsp &nbsp Detected</font></td></tr>";
				echo "</table>";

?>

<h4>Shortcodes</h4>
[videowhisper_recorder height="550px"] - Displays video recording interface on a page. Uses VideoShareVOD video sharing permissions if enabled.
<br>[videowhisper stream="stream name"] - Displays video using configured player.

<h4>FFMPEG Conversion</h4>
<p>If ffmpeg is available use these to update parameters as needed for conversion.</p>
<input name="ffmpegcall" type="text" id="ffmpegcall" size="100" maxlength="256" value="<?php echo $options['ffmpegcall']?>"/> $output_file -i $input_file
<BR>For lower server load and higher performance, web clients should be configured to broadcast video already suitable for target device (H.264 Baseline 3.1 for most iOS devices) so only audio needs to be encoded.
<BR>Ex.(convert audio for iOS): /usr/local/bin/ffmpeg -y -vcodec copy -acodec libfaac -ac 2 -ar 22050 -ab 96k
<BR>Ex.(convert video+audio): /usr/local/bin/ffmpeg -y -vcodec libx264 -s 480x360 -r 15 -vb 512k -x264opts vbv-maxrate=364:qpmin=4:ref=4 -coder 0 -bf 0 -analyzeduration 0 -level 3.1 -g 30 -maxrate 768k -acodec libfaac -ac 2 -ar 22050 -ab 96k
<BR>For advanced settings see <a href="https://developer.apple.com/library/ios/technotes/tn2224/_index.html#//apple_ref/doc/uid/DTS40009745-CH1-SETTINGSFILES">iOS HLS Supported Codecs<a> and <a href="https://trac.ffmpeg.org/wiki/Encode/AAC">FFMPEG AAC Encoding Guide</a>.

<br>This is not used when videos are managed by VideoShareVOD (recommended).

<h4>Videos directory</h4>
<input name="directory" type="text" id="directory" size="80" maxlength="256" value="<?php echo $options['directory']?>"/>
<BR>

Example: /home/youraccount/public_html/streams/
<BR>
(Ending in / .)

<h4>Videos url</h4>
<input name="videos_url" type="text" id="videos_url" size="80" maxlength="256" value="<?php echo $options['videos_url']?>"/>
<BR>
Example: http://yourserver.com/streams/
<BR>
(Ending in / .)


<h4>Embed Mode</h4>
<select name="embedMode" id="embedMode">
  <option value="0" <?php echo $options['embedMode']?"":"selected"?>>Direct mode</option>
  <option value="1" <?php echo $options['embedMode']?"selected":""?>>Shortcode mode</option>
</select>

<h4>Embed Width</h4>
<input name="embedWidth" type="text" id="embedWidth" size="5" maxlength="5" value="<?php echo $options['embedWidth']?>"/>
<BR>Specify px or %.

<h4>Embed Height</h4>
<input name="embedHeight" type="text" id="embedHeight" size="5" maxlength="5" value="<?php echo $options['embedHeight']?>"/>
<BR>Specify px or %.

<h4>Autoplay</h4>
<select name="autoplay" id="autoplay">
  <option value="false" <?php echo $options['autoplay']?"":"selected"?>>Off</option>
  <option value="true" <?php echo $options['autoplay']?"selected":""?>>On</option>
</select>


<h4>Cam Width</h4>
<input name="camWidth" type="text" id="camWidth" size="5" maxlength="5" value="<?php echo $options['camWidth']?>"/>px

<h4>Cam Heigth</h4>
<input name="camHeigth" type="text" id="camHeigth" size="5" maxlength="5" value="<?php echo $options['camHeigth']?>"/>px

<h4>Cam FPS</h4>
<input name="camFPS" type="text" id="camFPS" size="5" maxlength="5" value="<?php echo $options['camFPS']?>"/>

<h4>Video Codec</h4>
<select name="videoCodec" id="videoCodec">
  <option value="H264" <?php echo $options['videoCodec']=='H264'?"selected":""?>>H264</option>
  <option value="H263" <?php echo $options['videoCodec']=='H263'?"selected":""?>>H263</option>
</select>
<BR>H264 provides better quality at same bandwidth but may not be supported by older RTMP server versions (ex. Red5).
<BR>When publishing to iOS with HLS, for lower server load and higher performance, web clients should be configured to broadcast video suitable for target device (H.264 Baseline 3.1) so only audio needs to be encoded.


<h4>H264 Video Codec Profile</h4>
<select name="codecProfile" id="codecProfile">
  <option value="main" <?php echo $options['codecProfile']=='main'?"selected":""?>>main</option>
  <option value="baseline" <?php echo $options['codecProfile']=='baseline'?"selected":""?>>baseline</option>
</select>
<br>Recommended: Baseline

<h4>H264 Video Codec Level</h4>
<select name="codecLevel" id="codecLevel">
<?php
				foreach (array('1', '1b', '1.1', '1.2', '1.3', '2', '2.1', '2.2', '3', '3.1', '3.2', '4', '4.1', '4.2', '5', '5.1') as $optItm)
				{
?>
  <option value="<?php echo $optItm;?>" <?php echo $options['codecLevel']==$optItm?"selected":""?>> <?php echo $optItm;?> </option>
  <?php
				}
?>
 </select>
<br>Recommended: 3.1

<h4>Sound Codec</h4>
<select name="soundCodec" id="soundCodec">
  <option value="Speex" <?php echo $options['soundCodec']=='Speex'?"selected":""?>>Speex</option>
  <option value="Nellymoser" <?php echo $options['soundCodec']=='Nellymoser'?"selected":""?>>Nellymoser</option>
</select>
<BR>Current web codecs used by Flash plugin are not currently supported by iOS. For delivery to iOS, audio should be transcoded to AAC (HE-AAC or AAC-LC up to 48 kHz, stereo audio).

<h4>Speex Sound Quality</h4>
<select name="soundQuality" id="soundQuality">
<?php
				foreach (array('0', '1','2','3','4','5','6','7','8','9','10') as $optItm)
				{
?>
  <option value="<?php echo $optItm;?>" <?php echo $options['soundQuality']==$optItm?"selected":""?>> <?php echo $optItm;?> </option>
  <?php
				}
?>
 </select>

<h4>Nellymoser Sound Rate</h4>
<select name="micRate" id="micRate">
<?php
				foreach (array('5', '8', '11', '22','44') as $optItm)
				{
?>
  <option value="<?php echo $optItm;?>" <?php echo $options['micRate']==$optItm?"selected":""?>> <?php echo $optItm;?> </option>
  <?php
				}
?>
 </select>



<h4>Cam Bandwidth</h4>
<input name="camBandwidth" type="text" id="camBandwidth" size="8" maxlength="8" value="<?php echo $options['camBandwidth']?>"/>

<h4>Cam Max Bandwidth</h4>
<input name="camMaxBandwidth" type="text" id="camMaxBandwidth" size="8" maxlength="8" value="<?php echo $options['camMaxBandwidth']?>"/>

<h4>Show Cam Settings</h4>
<select name="showCamSettings" id="showCamSettings">
  <option value="0" <?php echo $options['showCamSettings']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['showCamSettings']?"selected":""?>>Yes</option>
</select>

<h4>Advanced Cam Settings</h4>
<select name="advancedCamSettings" id="advancedCamSettings">
  <option value="0" <?php echo $options['advancedCamSettings']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['advancedCamSettings']?"selected":""?>>Yes</option>
</select>

<h4>Disable Preview</h4>
<select name="disablePreview" id="disablePreview">
  <option value="0" <?php echo $options['disablePreview']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['disablePreview']?"selected":""?>>Yes</option>
</select>

<h4>Layout Code</h4>
<textarea name="layoutCode" type="textarea" cols="50" rows="3" id="layoutCode">
<?php echo $options['layoutCode'];?>
</textarea>

<h4>Fill Window</h4>
<select name="fillWindow" id="fillWindow">
  <option value="0" <?php echo $options['fillWindow']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['fillWindow']?"selected":""?>>Yes</option>
</select>

<h4>Record Limit</h4>
<input name="recordLimit" type="text" id="recordLimit" size="5" maxlength="5" value="<?php echo $options['recordLimit']?>"/>s
<br>Maximum recording duration in seconds.

<h4>Show VideoWhisper Powered by</h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?php echo $options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['videowhisper']?"selected":""?>>Yes</option>
</select>

<div class="submit">
  <input type="submit" name="updateSettings" id="updateSettings" value="<?php _e('Update Settings', 'VWvideoPosts') ?>" />
</div>

</form>
	 <?php
			}
			if($mod == 'recordings')
			{
				if($model == "delete")
				{
					// sterg alea si afisez un msg
					$recs = $_POST['recs'];

					//$options = get_option('VWvideoRecorderOptions');
					$delete_from = $options['directory'];

					$loggedin=0;
					global $current_user;
					get_currentuserinfo();
					if ($current_user->user_nicename) $username=urlencode($current_user->user_nicename);

					if ($username) $loggedin=1;
					else
					{
						echo "<BR>";
						echo "<p aling='center'><H3>Only admin can access this page!</H3></p>";
					}
					if($loggedin == 1)
					{
						global $wpdb;
						$table_name = $wpdb->prefix."vw_videorecordings";

						if($recs){
							foreach ($recs as $rec)
							{
								$wpdb->query($sql = "DELETE FROM $table_name WHERE streamname = '$rec' ");
								//echo $sql;

								if (file_exists($file = $delete_from . $rec  . ".flv")) unlink($file);

								if (file_exists($file = $delete_from . $rec  . ".key")) unlink($file);

								if (file_exists($file = $delete_from . $rec  . ".meta")) unlink($file);

								if (file_exists($file = "recordings/" . $rec  . ".vwr")) unlink($file);

								if (file_exists($file = $delete_from . $rec  . "-ip.mp4")) unlink($file);
								if (file_exists($file = $delete_from . $rec  . ".log")) unlink($file);
								if (file_exists($file = $delete_from . $rec  . ".ogv")) unlink($file);
								if (file_exists($file = $delete_from . $rec  . "-ogv.log")) unlink($file);

								if (file_exists($file = "snapshots/" . $rec  . ".jpg")) unlink($file);

							}
							echo "<BR><BR>";
							echo "The files were successfully deleted!";

							echo "This deletes only the video recordings. You will have to  manually edit the post to remove embed code and references. ";
							echo "<BR><BR>";
						}
					}
					//var_dump($recs);
				}
?>

			<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Video Posts Recordings list</h2>
			<p><H3><a href = "<?php echo home_url();?>/wp-admin/options-general.php?page=videoposts.php&mod=settings">Settings</a> |
 &gt; Recordings List</H3> </p>
			</div>

			<?php

				$streamname = $_GET['stream'];

				global $wpdb;
				$table_name = $wpdb->prefix."vw_videorecordings";
				$items =  $wpdb->get_results("SELECT * FROM `$table_name` ORDER BY `id` DESC");

?>


			<script language="javascript">
			function fncDelete()
			{
				if(confirm('Are you sure you want to delete videos?')==true)
				{
					//window.location = 'page1.cgi';
					// $.cookie('deleted', deleted );
					//form.inputdelete.value = 1;
					form.submit();
				}
			}
			</script>


			<script language="JavaScript">
			function checkUncheck(form, setTo) {
				var c = document.getElementById(form).getElementsByTagName('input');
				for (var i = 0; i < c.length; i++) {
					if (c[i].type == 'checkbox') {
						c[i].checked = setTo;
					}
				}
			}

			</script>

			<form id = "myForm" name = "form" action="<?php echo home_url(); ?>/wp-admin/options-general.php?page=videoposts.php&mod=recordings&model=delete" method="post">
			<input type='button' onclick="checkUncheck('myForm', true);" value='Check All'>&nbsp;&nbsp;
			<input type='button' onclick="checkUncheck('myForm', false);" value='Uncheck All'><br><br><?php

				echo "<table>";
				if ($items) foreach ($items as $item)
					{
						echo "<tr>";
						echo "<td valign='center'>";
?>
				<input type="checkbox" id="recs[]" name="recs[]" value="<?php echo $item->streamname; ?>">
				<?php
						echo "</td><td>";
						echo "<a href='".home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/streamplay.php?vid='.$item->streamname."' target='_blank'>";

						if(file_exists('../'.$file = 'wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/'.$item->streamname.'.jpg'))
						{
							echo "<img src='".home_url().'/'.$file."'>";
						}
						else
						{
							echo "<img src='".home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/snapshots/no_video.png'."'>";
						}
						echo "</a>";
						echo "</td>";
						echo "<td>";
						echo "<a href= '".home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/streamplay.php?vid='.$item->streamname.'&postid='.$item->postId."' target='_blank'><B>".$item->streamname."</B></a>";
						echo "<BR>";
						echo "<a href='".home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/recorded.php?stream='.$item->streamname."&mod=regenerate' target='_blank'>Regenerate HTML5 Conversion</a>";
						echo "<BR>";
						echo "<a href='".home_url().'?p='.$item->postId."' target='_blank'><B> View Post </B></a>";

						//echo " <BR> ";
						//echo "<a href=".home_url().'/wp-content/plugins/video-posts-webcam-recorder/posts/videowhisper/recorded_videos.php?delete='. urlencode($item->streamname).'&postid='.$item->postId." target='_blank'><B> Delete this Recording </B></a>";
						echo " <BR> ";
						echo date("D M j G:i:s T Y",$item->time);
						echo " <BR> ";
						echo "User id: ".$item->userId;
						echo "</td>";
						echo "</tr>";
					}
				echo "</table>";
?>
			<INPUT onClick="JavaScript:fncDelete(this.form);"  type="button" value="Delete" id="delete" name="delete">
			  </form>
			<?php

			}
		}
	}
}
//instantiate
if (class_exists("VWvideoPosts")) {
	$videoPosts = new VWvideoPosts();
}

//Actions and Filters
if (isset($videoPosts)) {
	add_action("plugins_loaded", array(&$videoPosts, 'init'));
	add_action('admin_menu', array(&$videoPosts, 'menu'));
}
?>