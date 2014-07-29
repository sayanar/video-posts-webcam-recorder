<?php
  
  include_once("incsan.php");
  $message = $_GET['message'];
  sanV($message);

header('Location: /?msg='.$message);

?>