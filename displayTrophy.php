<?php
include 'lib.php';

$eventName = $_GET['event'];
$db = Database::getConnection();
$stmt = $db->prepare("SELECT image, type, size FROM trophies WHERE event = ?");
$stmt->bind_param("s", $eventName);
$stmt->execute();
$stmt->bind_result($content, $type, $size);
$stmt->fetch();
$stmt->close();

header("Content-length: $size");
header("Content-type: $type");
echo $content;
?>
