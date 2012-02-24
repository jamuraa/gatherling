<?php
require_once('../lib.php');
$set = $argv[1];
$file = fopen($argv[2], "r");
$card = array();

$database = Database::getConnection();
$stmt = $database->prepare("INSERT INTO cards(cost, convertedcost, name, cardset, type,
  isw, isu, isb, isr, isg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");

while(!feof($file)) {
  $line = fgets($file);
  if(preg_match("/^(.*):\s+(.*)$/", $line, $matches)) {
    $card[$matches[1]] = $matches[2];
    if($matches[1] == "Set/Rarity") {
      preg_match("/$set (Common|Uncommon|Rare|Mythic Rare)/",
             $card[$matches[1]], $submatches);
      $card[$matches[1]] = $submatches[1];
      if($card['Set/Rarity'] == 'Common') {
        insertCard($card, $set, $stmt);
      }
    }
  }
}

$stmt->close();

function insertCard($card, $set, $stmt) {
  $cmc = getConvertedCost($card['Cost']);
  $isw = $isu = $isb = $isr = $isg = 0;
  if(preg_match("/W/", $card['Cost'])) {$isw = 1;}
  if(preg_match("/U/", $card['Cost'])) {$isu = 1;}
  if(preg_match("/B/", $card['Cost'])) {$isb = 1;}
  if(preg_match("/R/", $card['Cost'])) {$isr = 1;}
  if(preg_match("/G/", $card['Cost'])) {$isg = 1;}
  # new gatherer - card type is now a . because of unicode
    $card['Type'] = str_replace('.', '-', $card['Type']);
  echo "{$card['Name']}<br />\n";
  $stmt->bind_param("sdsssddddd", $card['Cost'], $cmc, $card['Name'], $set, $card['Type'], $isw, $isu, $isb, $isr, $isg);
  if (!$stmt->execute()) {
    die($stmt->error);
  }
}

function getConvertedCost($cost) {
  $cost = chop($cost);
  $cmc = strlen($cost);
  if(preg_match("/^([0-9])/", $cost, $matches)) {
    $cmc = $matches[1] + strlen($cost) - 1;
  }
  return $cmc;
}

?>
