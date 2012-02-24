<?php
include 'lib.php';

$series = $_GET['series'];
$db = Database::getConnection();
$stmt = $db->prepare("SELECT logo, imgtype, imgsize FROM series WHERE name = ?");
$stmt->bind_param("s", $series);
$stmt->execute();
$stmt->bind_result($content, $type, $size);
$stmt->fetch();
$stmt->close();

header("Content-length: $size");
header("Content-type: $type");
echo $content;
?>
