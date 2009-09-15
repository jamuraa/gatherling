<?php
session_start(); 
require_once('lib.php');

$player = Player::getSessionPlayer(); 
if (!$player->isSuper()) {
  header("Location: index.php");
} 

if ($_POST['mode'] == 'Upload') {
	if ($_FILES['logo']['size'] > 0) {
    $file = $_FILES['logo'];
    $seriesname = $_POST['series'];
   
    $name = $file['name'];
    $tmp = $file['tmp_name'];
    $size = $file['size'];
    $type = $file['type'];

    $f = fopen($tmp, 'r');
    $content = fread($f, filesize($tmp));
    fclose($f);

    $db = Database::getConnection(); 
    $stmt = $db->prepare("UPDATE series SET logo = ?, imgsize = ?,
      imgtype = ? WHERE name = ?"); 
    $stmt->bind_param("bdss", $null, $size, $type, $seriesname); 
    $stmt->send_long_data(0, $content);
    $stmt->execute() or die($stmt->error); 
    $stmt->close(); 
  } else {
    echo "No file found";
  }
}
else {
	echo "<form action=\"logo.php\" method=\"post\" enctype=\"multipart/form-data\">";
	echo "<input type=\"file\" id=\"logo\" name=\"logo\">";
	echo "<input type=\"text\" name=\"series\">";
  echo "<input type=\"submit\" name=\"mode\" value=\"Upload\">";
	echo "</form>";
}
	
?>
