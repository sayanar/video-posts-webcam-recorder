<?php
include_once("../../../../../wp-config.php");
error_reporting(E_ALL & ~E_NOTICE);

$mod = $_GET['mod'];

$stream=sanitize_file_name($_POST['stream']);
if (!$stream) $stream=sanitize_file_name($_GET['stream']);
if (!$stream) exit;

$recording = sanitize_file_name($_POST['recording']);

$options = get_option('VWvideoRecorderOptions');

$video_from = $options['directory'];

//echo "&stream=$stream&from=$video_from";
  
  if (file_exists("snapshots/".$stream.".jpg"))  copy("snapshots/".$stream.".jpg","snapshots/".$recording.".jpg");
  
  //conversion
  $vid = $stream;

if (file_exists($file = $video_from . $vid  . ".flv"))
{
//echo "&file=$file";

if ($options['selectPlayer'] == 'videosharevod')
{
	if (class_exists("VWvideoShare"))
	{
		global $current_user;
		get_currentuserinfo();
					
		VWvideoShare::importFile($file, sanitize_file_name($stream), $current_user->ID, sanitize_file_name($current_user->display_name), '', 'webcam', $description = $current_user->display_name . ' webcam wecording');
		
//		echo '&videosharevod=1';

	}
	
	
}
else 
	{
		//mp4
		$output_file= $video_from . $vid  . "-ip.mp4";
		$log_file = $video_from . $vid  . ".log";
		$cmd = $options['ffmpegcall'] . " '$output_file' -i '$file' >& '$log_file' &";
		exec($cmd, $output, $returnvalue);
		exec("echo '$cmd' >> '$log_file.cmd'", $output, $returnvalue);
		
		if ($mod) echo $cmd;
		
		//ogv    
		$output_filew= $video_from . $vid  . ".ogv";
		$log_filew = $video_from . $vid  . "-ogv.log";
		$cmd = "/usr/local/bin/ffmpeg2theora \"$file\" -o \"$output_filew\" -V 512 -A 96 -y &> '$log_filew' &";
		exec($cmd, $output, $returnvalue);
		exec("echo '$cmd' >> '$log_file.cmd2'", $output, $returnvalue);
	}
}
?>&loadstatus=1