<?php
include 'lib.php';

$series = $_GET['series'];
$dblink = dbcon();
$query = "SELECT logo, imgtype, imgsize FROM series WHERE name='$series'";
$result = mysql_query($query, $dblink);
$info = mysql_fetch_assoc($result);
$type = $info['imgtype'];
$size = $info['imgsize'];
$content = $info['logo'];
mysql_free_result($result);
mysql_close($dblink);

header("Content-length: $size");
header("Content-type: $type");
echo $content;
?>
