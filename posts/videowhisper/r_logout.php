<?
include("header.php");
  
  include_once("incsan.php");
  $message = $_GET['message'];
  sanV($message);

?>
<p>
  <?=$message?>


</p>
<p></b><a href="recorded_videos.php">Browse Video Recordings</a></p>
