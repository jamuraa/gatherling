<?php
require_once('lib.php');

$id = 0;
if(isset($_GET['id'])) {$id = $_GET['id'];}
if(isset($_POST['id'])) {$id = $_POST['id'];}

if ($id == 0) { 
  header("location: player.php");
  exit;
} 

$deck = new Deck($id);

$content = "";

foreach ($deck->maindeck_cards as $card => $qty) { 
  $content .= $qty . " " . $card . "\n"; 
}

$content .= "\nSideboard\n";

foreach ($deck->sideboard_cards as $card => $qty) { 
  $content .= $qty . " " . $card . "\n";
} 

$filename = preg_replace("/ /", "_", $deck->name) . ".txt";
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$filename");
echo $content;
exit;
?>

