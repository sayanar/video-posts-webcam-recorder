<?php

//include("inc.php");
include_once("../../../../../wp-config.php");

$options = get_option('VWvideoRecorderOptions');

$rtmp_server = $options['rtmp_server'];
$camWidth = $options['camWidth'];
$camHeight = $options['camHeigth'];
$camFPS = $options['camFPS'];
$camBandwidth = $options['camBandwidth'];
$camMaxBandwidth = $options['camMaxBandwidth'];
$showCamSettings = $options['showCamSettings'];
$advancedCamSettings = $options['advancedCamSettings'];
$disablePreview = $options['disablePreview'];
$layoutCode = $options['layoutCode'];
$fillWindow = $options['fillWindow'];
$recordLimit = $options['recordLimit'];
$videowhisper = $options['videowhisper'];
$directory = $options['directory'];

$loggedin=0;
global $current_user;
get_currentuserinfo();
if ($current_user->user_nicename) $username=urlencode($current_user->user_nicename);

$msg="";
		if ($username) $loggedin=1;
		else $msg=urlencode("<a href=\"/\">Please login first or register an account if you don't have one! Click here to return to website.</a>");

//suffix to attach to $username and obtain recording filename
//$recordingId="-".base_convert(time(),10,36); //latest versions automatically add time stamp

$recordingId="";

$layoutCode=<<<layoutEND
layoutEND;

?>firstparam=fix&server=<?=$rtmp_server?>&serverAMF=<?=$rtmp_amf?>&username=<?=$username?>&recordingId=<?=$recordingId?>&msg=<?=$msg?>&loggedin=<?=$loggedin?>&camWidth=<?=$camWidth?>&camHeight=<?=$camHeight?>&camFPS=<?=$camFPS?>&camBandwidth=<?=$camBandwidth?>&showCamSettings=<?=$showCamSettings?>&camMaxBandwidth=<?=$camMaxBandwidth?>&videoCodec=<?php echo $options['videoCodec']?>&codecProfile=<?php echo $options['codecProfile']?>&codecLevel=<?php echo
				$options['codecLevel']?>&soundCodec=<?php echo $options['soundCodec']?>&soundQuality=<?php echo $options['soundQuality']?>&micRate=<?php echo
				$options['micRate']?>&advancedCamSettings=<?=$advancedCamSettings?>&recordLimit=<?=$recordLimit?>&bufferLive=900&bufferFull=900&bufferLivePlayback=0.2&bufferFullPlayback=10&layoutCode=<?=urlencode($layoutCode)?>&fillWindow=<?=$fillWindow?>&disablePreview=<?=$disablePreview?>&loadstatus=1