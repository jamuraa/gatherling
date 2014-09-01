<?php
session_start();
require_once('../lib.php');

if (!Player::isLoggedIn() || !Player::getSessionPlayer()->isSuper()) {
  redirect("index.php");
}

$file = file_get_contents($_FILES['cardsetfile']['tmp_name']);

if ($file == FALSE) {
  die("Can't open the file you uploaded: {$_FILES['cardsetfile']['tmp_name']}");
}

$data = json_decode($file);

$set = $data->name;
$settype = $_POST['settype'];
$releasedate = $data->releaseDate;

//$set = "Put cardset name here";
//$file = fopen("Put spoiler text file name here.txt", "r");
$card = array();
$rarity = "Common";
$cardsparsed = 0;
$cardsinserted = 0;

$database = Database::getConnection();

$stmt = $database->prepare("SELECT * FROM cardsets where name = ?");

$stmt->bind_param("s", $set);

$set_already_in = false;

if (!$stmt->execute()) {
  echo "!!!!!!!!!! Set Insertion Error !!!!!!!!!<br /><br /><br />";
  die($stmt->error);
} else {
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $set_already_in = true;
  }
}

if (!$set_already_in) {
  echo "Inserting card set ($set, $releasedate, $settype)...<br />";

  // Insert the card set
  $stmt = $database->prepare("INSERT INTO cardsets(released, name, type) values(?, ?, ?)");
  $stmt->bind_param("sss", $releasedate, $set, $settype);

  if (!$stmt->execute()) {
    echo "!!!!!!!!!! Set Insertion Error !!!!!!!!!<br /><br /><br />";
    die($stmt->error);
  } else {
    echo "Inserted new set {$set}!<br /><br />";
  }
  $stmt->close();
}

$stmt = $database->prepare("INSERT INTO cards(cost, convertedcost, name, cardset, type,
  isw, isu, isb, isr, isg, isp, rarity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");

foreach ($data->cards as $card) {
  $cardsparsed++;
  if ($card->rarity === "Common") {
    insertCard($card, $set, $card->rarity, $stmt);
    $cardsinserted++;
  }
}

echo "End of File Reached<br />";
echo "Total Cards Parsed: {$cardsparsed}<br />";
echo "Total Cards Inserted: {$cardsinserted}<br />";
$stmt->close();

function insertCard($card, $set, $rarity, $stmt) {
  # new gatherer - card type is now a . because of unicode
  $typeline = join($card->types, " ");
  if (count($card->subtypes) > 0) {
    $typeline = $typeline . " - " . join($card->subtypes, " ");
  }

  echo "<table class=\"new_card\">";
  foreach (array('name', 'manaCost', 'cmc', 'type', 'rarity') as $attr) {
    echo "<tr><th>{$attr}:</th><td>" . $card->{$attr} . "</td></tr>";
  }
  echo "<tr><th>Card Colors:</th><td>";
  $isw = $isu = $isb = $isr = $isg = $isp = 0;
  if(preg_match("/W/", $card->manaCost)) {$isw = 1;echo "White ";}
  if(preg_match("/U/", $card->manaCost)) {$isu = 1;echo "Blue ";}
  if(preg_match("/B/", $card->manaCost)) {$isb = 1;echo "Black ";}
  if(preg_match("/R/", $card->manaCost)) {$isr = 1;echo "Red ";}
  if(preg_match("/G/", $card->manaCost)) {$isg = 1;echo "Green ";}
  if(preg_match("/P/", $card->manaCost)) {$isp = 1;echo "Phyrexian ";}
  echo "</td></tr>";

  $empty_string = "";
  $zero = 0;

  if (property_exists($card, "manaCost")) {
    $stmt->bind_param("sdsssdddddds", $card->manaCost, $card->cmc, $card->name, $set, $typeline, $isw, $isu, $isb, $isr, $isg, $isp, $rarity);
  } else {
    $stmt->bind_param("sdsssdddddds", $empty_string, $zero, $card->name, $set, $typeline, $isw, $isu, $isb, $isr, $isg, $isp, $rarity);
  }

  if (!$stmt->execute()) {
    echo "<tr><td colspan=\"2\" style=\"background-color: LightRed;\">!!!!!!!!!! Card Insertion Error !!!!!!!!!</td></tr>";
    echo "</table>";
    die($stmt->error);
  } else {
    echo "<tr><th colspan=\"2\" style=\"background-color: LightGreen;\">Card Inserted Successfully</th></tr>";
    echo "</table>";
  }
}

