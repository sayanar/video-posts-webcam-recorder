<?php
if (!$recording=$_POST['recording']) exit;
if (!$stream=$_POST['stream']) exit;

  include_once("incsan.php");
  sanV($stream);
  sanV($recording);
  
  // save file
  $fp=fopen("recordings/".$_POST['recording'].".vwr","w");
  if ($fp)
  {
    fwrite($fp, $_POST['stream'].";;;".time().";;;".$_POST['rectime']);
    fclose($fp);
  }
  if (file_exists("snapshots/".$_POST['stream'].".jpg"))  copy("snapshots/".$_POST['stream'].".jpg","snapshots/".$_POST['recording'].".jpg");
?>loadstatus=1