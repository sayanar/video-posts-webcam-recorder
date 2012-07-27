<?

include_once("../../../../../wp-config.php");
$options = get_option('VWvideoRecorderOptions');

$rtmp_server = urlencode($options['rtmp_server']);
$videowhisper = $options['videowhisper'];

	$state = 'block' ;
	if (!$videowhisper) $state = 'none';
	
	$poweredby = '<div style=\"display: ' . $state . ';\"><p>Powered by <a href=\"http://www.videowhisper.com\"  target=\'_blank\'>VideoWhisper</a>,<a href=\"http://www.videowhisper.com/?p=Video+Recorder\"  target=\'_blank\'> Video Recorder</a>.</p></div>';
	
	$postId = 0;

	$streamname = $_GET['stream'];

	global $current_user;
	$current_user = wp_get_current_user();
	$userId = $current_user->ID;
	
	global $wpdb;
	$table_name = $wpdb->prefix."vw_videorecordings";
	$wpdb->insert( $table_name, array( 'time' => time(), 'streamname' => $streamname,'userId' => $userId, 'postId' => $postId) );

	setcookie("recIdCookie", $wpdb->insert_id, time()+3600*24,'/');
?>
<script type="text/javascript" src="../../../../../wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<script> 
var RecordDialog = {
	init : function(ed) {
	    tinyMCEPopup.execCommand('mceInsertContent', false, "<u><?php echo $streamname;?></u><div style=\" width:320px; height:240px \"><object height=\"100%\" width=\"100%\"><param name=\"movie\" value=\"<?php echo home_url();?>/wp-content/plugins/videoposts/posts/videowhisper/streamplayer.swf?streamName=<?php echo "$streamname";?>&amp;serverRTMP=<?php echo $rtmp_server;?>&amp;templateURL=\"><param name=\"scale\" value=\"noscale\"><param name=\"salign\" value=\"lt\"><param name=\"base\" value=\"<?php echo home_url();?>/wp-content/plugins/videoposts/posts/videowhisper/\"><param name=\"allowFullScreen\" value=\"true\"><param name=\"allowscriptaccess\" value=\"always\"><embed base=\"<?php echo home_url();?>/wp-content/plugins/videoposts/posts/videowhisper/\"  scale=\"noscale\" salign=\"lt\" src=\"<?php echo home_url();?>/wp-content/plugins/videoposts/posts/videowhisper/streamplayer.swf?streamName=<?php echo "$streamname";?>&amp;serverRTMP=<?php echo $rtmp_server;?>&amp;templateURL=\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" height=\"100%\" width=\"100%\"></object></div><i><h6><?php echo $poweredby;?></h6></i>");
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(RecordDialog.init, RecordDialog);
  </script>
  
