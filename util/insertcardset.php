<?php
session_start();
require_once('../lib.php');

if (!Player::isLoggedIn() || !Player::getSessionPlayer()->isSuper()) {
  redirect("index.php");
}

$set = $_POST['cardsetname'];
$settype = $_POST['settype'];
$releasedate = $_POST['releasedate'];
$file = fopen($_FILES['cardsetfile']['tmp_name'], "r");

if ($file == FALSE) {
  die("Can't open the file you uploaded: {$_FILES['cardsetfile']['tmp_name']}");
}

//$set = "Put cardset name here";
//$file = fopen("Put spoiler text file name here.txt", "r");
$card = array();
$rarity = "Common";
$cardsparsed = 0;
$cardsinserted = 0;

$database = Database::getConnection();

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

$stmt = $database->prepare("INSERT INTO cards(cost, convertedcost, name, cardset, type,
  isw, isu, isb, isr, isg, isp, rarity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");

while(!feof($file))
{
  $line = fgets($file);
  ## echo "Grabbing Line: {$line}<br />";
  if(preg_match("/^([^:]*):\s+(.*)$/", $line, $matches))
  {
    ## echo "Card Attribute : {$matches[1]}<br />";
    ## echo "Attribute Value: {$matches[2]}<br />";
    $card[$matches[1]] = $matches[2];
    if($matches[1] == "Set/Rarity")
    {
      preg_match("/$set (Land|Common|Uncommon|Rare|Mythic Rare)/", $card[$matches[1]], $submatches);
      $card[$matches[1]] = $submatches[1];
      $cardsparsed++;
      if ($card['Set/Rarity'] == 'Common') {
        insertCard($card, $set, $submatches[1], $stmt);
        $cardsinserted++;
      }
    }
  }
  else
  {
      ## echo "Line is not usable content so will be ignored<br />";
  }
}

echo "End of File Reached<br />";
echo "Total Cards Parsed: {$cardsparsed}<br />";
echo "Total Cards Inserted: {$cardsinserted}<br />";
$stmt->close();

function insertCard($card, $set, $rarity, $stmt) {
  $card['CMC'] = getConvertedCost($card['Cost']);
  $card['Rarity'] = $rarity;
  $card['Cardset'] = $set;
  # new gatherer - card type is now a . because of unicode
  $card['Type'] = str_replace('.', '-', $card['Type']);

  if (is_null($card['Cost'])) {$card['Cost'] = 0;}

  echo "<table class=\"new_card\">";
  foreach (array('Name', 'Cost', 'CMC', 'Type', 'Rarity', 'Cardset') as $attr) {
    echo "<tr><th>{$attr}:</th><td>{$card[$attr]}</td></tr>";
  }
  echo "<tr><th>Card Colors:</th><td>";
  $isw = $isu = $isb = $isr = $isg = $isp = 0;
  if(preg_match("/W/", $card['Cost'])) {$isw = 1;echo "White ";}
  if(preg_match("/U/", $card['Cost'])) {$isu = 1;echo "Blue ";}
  if(preg_match("/B/", $card['Cost'])) {$isb = 1;echo "Black ";}
  if(preg_match("/R/", $card['Cost'])) {$isr = 1;echo "Red ";}
  if(preg_match("/G/", $card['Cost'])) {$isg = 1;echo "Green ";}
  if(preg_match("/P/", $card['Cost'])) {$isp = 1;echo "Phyrexian ";}
  echo "</td></tr>";

  $stmt->bind_param("sdsssdddddds", $card['Cost'], $card['CMC'], $card['Name'], $set, $card['Type'], $isw, $isu, $isb, $isr, $isg, $isp, $rarity);

  if (!$stmt->execute()) {
    echo "<tr><td colspan=\"2\" style=\"background-color: LightRed;\">!!!!!!!!!! Card Insertion Error !!!!!!!!!</td></tr>";
    echo "</table>";
    die($stmt->error);
  } else {
    echo "<tr><th colspan=\"2\" style=\"background-color: LightGreen;\">Card Inserted Successfully</th></tr>";
    echo "</table>";
  }
}

function getConvertedCost($cost) {
  if (is_null($cost)) {$cost = 0;}
  $cost = str_replace ('(','',$cost);
  $cost = str_replace (')','',$cost);
  $cost = str_replace ('/P','',$cost);
  $cost = str_replace ('W/','',$cost);
  $cost = str_replace ('R/','',$cost);
  $cost = str_replace ('G/','',$cost);
  $cost = str_replace ('U/','',$cost);
  $cost = str_replace ('B/','',$cost);
  $cost = str_replace ('1/','',$cost);
  $cost = str_replace ('2/','',$cost);
  $cost = str_replace ('3/','',$cost);
  $cost = str_replace ('4/','',$cost);
  $cost = str_replace ('5/','',$cost);
  $cost = str_replace ('6/','',$cost);
  $cost = str_replace ('7/','',$cost);
  $cost = str_replace ('8/','',$cost);
  $cost = str_replace ('9/','',$cost);
  $cost = chop($cost);
  $cmc = strlen($cost);
  if(preg_match("/^([0-9])/", $cost, $matches)) {
    $cmc = $matches[1] + strlen($cost) - 1;
  }
  return $cmc;
}

?>

