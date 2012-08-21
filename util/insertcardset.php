<?php
require_once('../lib.php');
$set = "Put cardset name here";
$file = fopen("Put spoiler text file name here.txt", "r");
$card = array();
$rarity = "Common";
$cardsinserted = 0;

$database = Database::getConnection();
$stmt = $database->prepare("INSERT INTO cards(cost, convertedcost, name, cardset, type,
  isw, isu, isb, isr, isg, isp, rarity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);"); 

while(!feof($file)) 
{
  $line = fgets($file);
  echo "Grabbing Line: {$line}<br />";
  if(preg_match("/^(.*):::\s+(.*)$/", $line, $matches)) 
  { 
    echo "Card Attribute : {$matches[1]}<br />";
    echo "Attribute Value: {$matches[2]}<br />";
    $card[$matches[1]] = $matches[2];
    if($matches[1] == "Set/Rarity") 
    {
      preg_match("/$set (Land|Common|Uncommon|Rare|Mythic Rare)/", $card[$matches[1]], $submatches);
      $card[$matches[1]] = $submatches[1];
      echo "<br /><br />**********   Inserting Card   **********<br />";
      $cardsinserted++;
      insertCard($card, $set, $submatches[1], $stmt);
    }
  }
  else
  {
      echo "Line is not usable content so will be ignored<br />";
  }
}

echo "End of File Reached<br />";
echo "Total Cards Inserted: {$cardsinserted}<br />";
$stmt->close();

function insertCard($card, $set, $rarity, $stmt) {
  $cmc = getConvertedCost($card['Cost']);
  # new gatherer - card type is now a . because of unicode
  $card['Type'] = str_replace('.', '-', $card['Type']);

  if (is_null($card['Cost'])) {$card['Cost'] = 0;} 

  echo "Card Name:           {$card['Name']}<br />";
  echo "Card Mana Cost:      {$card['Cost']}<br />";
  echo "Converted Mana Cost: {$cmc}<br />";
  echo "Card Type:           {$card['Type']}<br />";
  echo "Card Rarity:         {$rarity}<br />";
    
  $isw = $isu = $isb = $isr = $isg = $isp = 0;
  if(preg_match("/W/", $card['Cost'])) {$isw = 1;echo "Card is:             White<br />";}
  if(preg_match("/U/", $card['Cost'])) {$isu = 1;echo "Card is:             Blue<br />";}
  if(preg_match("/B/", $card['Cost'])) {$isb = 1;echo "Card is:             Black<br />";}
  if(preg_match("/R/", $card['Cost'])) {$isr = 1;echo "Card is:             Red<br />";}
  if(preg_match("/G/", $card['Cost'])) {$isg = 1;echo "Card is:             Green<br />";}
  if(preg_match("/P/", $card['Cost'])) {$isp = 1;echo "Card is:             Phyrexian<br />";}

  echo "Card Set:            {$set}<br /><br />";

  $stmt->bind_param("sdsssdddddds", $card['Cost'], $cmc, $card['Name'], $set, $card['Type'], $isw, $isu, $isb, $isr, $isg, $isp, $rarity); 

  if (!$stmt->execute()) 
  {
    echo "!!!!!!!!!! Card Insertion Error !!!!!!!!!<br /><br /><br />";
    die($stmt->error);
  } 
  else
  {
      echo "Card Inserted Successfully!<br /><br />";
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

