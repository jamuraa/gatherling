<?php
include 'lib.php';

$eventName = $_GET['event'];
$dblink = dbcon();
$query = "SELECT image, type, size FROM trophies WHERE event='$eventName'";
$result = mysql_query($query, $dblink);
$info = mysql_fetch_assoc($result);
$type = $info['type'];
$size = $info['size'];
$content = $info['image'];
mysql_free_result($result);
mysql_close($dblink);

header("Content-length: $size");
header("Content-type: $type");
echo $content;
?>
