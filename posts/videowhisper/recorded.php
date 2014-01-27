<?php
include_once("../../../../../wp-config.php");

$mod = $_GET['mod'];

$stream=$_POST['stream'];
if (!$stream) $stream=$_GET['stream'];
if (!$stream) exit;


$options = get_option('VWvideoRecorderOptions');

$video_from = $options['directory'];

  include_once("incsan.php");
  sanV($stream);
  sanV($recording);
  
  if (file_exists("snapshots/".$_POST['stream'].".jpg"))  copy("snapshots/".$_POST['stream'].".jpg","snapshots/".$_POST['recording'].".jpg");
  
  //conversion
  $vid = $stream;
  
  if (file_exists($file = $video_from . $vid  . ".flv"))
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
$cmd = "/usr/local/bin/ffmpeg2theora -y '$file' -o '$output_filew' -V 512 -A 96 &> '$log_filew' &";
exec($cmd, $output, $returnvalue);

	}
?>loadstatus=1