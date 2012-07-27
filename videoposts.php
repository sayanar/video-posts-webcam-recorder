<?php
/*
Plugin Name: Video Posts Webcam Recorder
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Recorder
Description: Video Posts Webcam Recorder
Version: 1.45
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
   return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_videoposts_tinymce_plugin($plugin_array) {
   $plugin_array['recorder'] = home_url().'/wp-content/plugins/videoposts/posts/editor_plugin.js';
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
	
	function wpse_hold_global_post_number( $post_id, $post ) {

	global $post_ID;
	if($post_ID) $post_id = $post_ID;
	$recCookie = $_COOKIE["recIdCookie"];
   
	global $wpdb;
	$table_name = $wpdb->prefix."vw_videorecordings";
	$sql="UPDATE $table_name SET postId = '$post_id' WHERE id = '$recCookie' AND postId = '0'";
	$wpdb->query($sql);

}
function init()
	{
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin",  array('VWvideoPosts','settings_link') );
		add_filter("plugin_action_links_$plugin",  array('VWvideoPosts','recordings_link') );
		
		
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
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Video Whisper: Sessions - 2009@videowhisper.com' AUTO_INCREMENT=1;
		
		";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			if (!$installed_ver) add_option("vw_recorder_version", $vw_recorder_version);
			else update_option( "vw_recorder_version", $vw_recorder_version );
			
		$wpdb->flush();
		}
			

	}
	function menu() {
	  add_options_page('Video Posts Webcam Recorder Options', 'Video Posts', 9, basename(__FILE__), array('VWvideoPosts', 'options'));
	}
	
	function getAdminOptions() {
				
				$adminOptions = array(
				'rtmp_server' => 'rtmp://localhost/videowhisper',
				'camWidth' => 320,
				'camHeigth' => 240,
				'embedWidth' => 320,
				'embedHeight' => 240,
				'camFps' => 15,
				'micRate' => 22,
				'camBandwidth' => 49158,
				'camMaxBandwidth' => 131072,
				'layoutCode' => '',
				'fillWindow' => 0,
				'recordLimit' => 600,
				'directory' => 'c:Program Files/Wowza Media Systems/Wowza Media Server 3.1.1/content',
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
		if ($mod == '') $mod = 'settings';
		if($mod == 'settings')
		{
		$options = VWvideoPosts::getAdminOptions();

		if (isset($_POST['updateSettings'])) 
		{
				if (isset($_POST['rtmp_server'])) $options['rtmp_server'] = $_POST['rtmp_server'];
				if (isset($_POST['camWidth'])) $options['camWidth'] = $_POST['camWidth'];
				if (isset($_POST['camHeigth'])) $options['camHeigth'] = $_POST['camHeigth'];
				if (isset($_POST['camFps'])) $options['camFps'] = $_POST['camFps'];
				if (isset($_POST['micRate'])) $options['micRate'] = $_POST['micRate'];
				if (isset($_POST['camBandwidth'])) $options['camBandwidth'] = $_POST['camBandwidth'];
				if (isset($_POST['camMaxBandwidth'])) $options['camMaxBandwidth'] = $_POST['camMaxBandwidth'];
				if (isset($_POST['layoutCode'])) $options['layoutCode'] = $_POST['layoutCode'];
				if (isset($_POST['fillWindow'])) $options['fillWindow'] = $_POST['fillWindow'];
				if (isset($_POST['recordLimit'])) $options['recordLimit'] = $_POST['recordLimit'];
				if (isset($_POST['directory'])) $options['directory'] = $_POST['directory'];
				if (isset($_POST['videowhisper'])) $options['videowhisper'] = $_POST['videowhisper'];
				update_option('VWvideoRecorderOptions', $options);
		}
	  ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Video Posts Webcam Recorder Settings</h2>
</div>

	<a href = "<?php echo home_url();?>/wp-admin/options-general.php?page=videoposts.php&mod=recordings"><H2>Recordings list</H2> </a> 
	
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

<h3>General Settings</h3>

<h5>RTMP Address</h5>
<p>To run this, make sure your hosting environment meets all <a href="http://www.videowhisper.com/?p=Requirements" target="_blank">requirements</a>.  If you don't have a videowhisper rtmp address yet (from a managed rtmp host), go to <a href="http://www.videowhisper.com/?p=RTMP+Applications" target="_blank">RTMP Application   Setup</a> for  installation details.</p>
<input name="rtmp_server" type="text" id="rtmp_server" size="80" maxlength="256" value="<?=$options['rtmp_server']?>"/>

<h5>Cam Width</h5>
<input name="camWidth" type="text" id="camWidth" size="5" maxlength="5" value="<?=$options['camWidth']?>"/>

<h5>Cam Heigth</h5>
<input name="camHeigth" type="text" id="camHeigth" size="5" maxlength="5" value="<?=$options['camHeigth']?>"/>

<h5>Cam Fps</h5>
<input name="camFps" type="text" id="camFps" size="5" maxlength="5" value="<?=$options['camFps']?>"/>

<h5>Mic Rate</h5>
<input name="micRate" type="text" id="micRate" size="5" maxlength="5" value="<?=$options['micRate']?>"/>

<h5>Cam Bandwidth</h5>
<input name="camBandwidth" type="text" id="camBandwidth" size="8" maxlength="8" value="<?=$options['camBandwidth']?>"/>

<h5>Cam Max Bandwidth</h5>
<input name="camMaxBandwidth" type="text" id="camMaxBandwidth" size="8" maxlength="8" value="<?=$options['camMaxBandwidth']?>"/>

<h5>Layout Code</h5>
<input name="layoutCode" type="text" id="layoutCode" size="5" maxlength="5" value="<?=$options['layoutCode']?>"/>

<h5>Fill Window</h5>
<select name="fillWindow" id="fillWindow">
  <option value="0" <?=$options['fillWindow']?"":"selected"?>>No</option>
  <option value="1" <?=$options['fillWindow']?"selected":""?>>Yes</option>
</select>

<h5>Record Limit</h5>
<input name="recordLimit" type="text" id="recordLimit" size="5" maxlength="5" value="<?=$options['recordLimit']?>"/>

<h5>Videos directory</h5>
<input name="directory" type="text" id="directory" size="80" maxlength="256" value="<?=$options['directory']?>"/>
<BR>
Example: /home/youraccount/public_html/streams/ 
<BR>
(Ending in / .)

<h5>Show VideoWhisper Powered by</h5>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?=$options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?=$options['videowhisper']?"selected":""?>>Yes</option>
</select>

<div class="submit">
  <input type="submit" name="updateSettings" id="updateSettings" value="<?php _e('Update Settings', 'VWvideoPosts') ?>" />
</div>

</form>
	 <?
	}
		if($mod == 'recordings')
		{ 
			?>
			<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Video Posts Recordings list</h2>
			</div>

			<?php
			
			$streamname = $_GET['stream'];

			global $wpdb;
			$table_name = $wpdb->prefix."vw_videorecordings";
			$items =  $wpdb->get_results("SELECT * FROM `$table_name` ORDER BY `id` DESC");
			echo "<table>";
			if ($items)	foreach ($items as $item) 
			{
			echo "<tr>";
				echo "<td>";
				echo "<a href= ".home_url().'/wp-content/plugins/videoposts/posts/videowhisper/streamplay.php?vid='.$item->streamname." target='_blank'>";
				if(file_exists('../'.$file = 'wp-content/plugins/videoposts/posts/videowhisper/snapshots/'.$item->streamname.'.jpg')) 
				{
					echo "<img src=".home_url().'/'.$file.">";
				}
				else
				{
					echo "<img src=".home_url().'/wp-content/plugins/videoposts/posts/videowhisper/snapshots/no_video.png'.">";
				}
				echo "</a>";
				echo "</td>";
				echo "<td>";
				echo "<a href= ".home_url().'/wp-content/plugins/videoposts/posts/videowhisper/streamplay.php?vid='.$item->streamname.'&postid='.$item->postId." target='_blank'><B>".$item->streamname."</B></a>";
				echo " <BR><BR> ";
				echo "<a href=".home_url().'?p='.$item->postId." target='_blank'><B> View Post </B></a>";
				echo " <BR> ";
				echo "<a href=".home_url().'/wp-content/plugins/videoposts/posts/videowhisper/recorded_videos.php?delete='. urlencode($item->streamname).'&postid='.$item->postId." target='_blank'><B> Delete this Recording </B></a>";
				echo " <BR> ";
				echo date("D M j G:i:s T Y",$item->time);
				echo " <BR> ";
				echo "User id: ".$item->userId;
				echo "</td>";
			echo "</tr>";
			}
			echo "</table>";
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