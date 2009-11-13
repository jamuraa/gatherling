<?php session_start();
require_once 'lib.php';

print_header("PDCMagic.com | Gatherling | Deck Database");

?> 
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle">Deck Database</div>
<?php
if(strcmp($_POST['mode'], "Create Deck") == 0) {
  $deck = insertDeck();
  deckProfile($deck);
}
elseif(strcmp($_POST['mode'], "Update Deck") == 0) {
  $deck = new Deck($_POST['id']);
  if($deck->canEdit($_SESSION['username'])) {
    $deck = updateDeck($deck);
    deckProfile($deck);
  }
  else {authFailed();}
}
elseif(strcmp($_POST['mode'], "Edit Deck") == 0) {
  $deck = new Deck($_POST['id']); 
  if($deck->canEdit($_SESSION['username'])) {
    deckForm($deck);
  }
  else{authFailed();}
}
elseif(strcmp($_GET['mode'], "create") == 0) {
  deckForm();
}
elseif(strcmp($_GET['mode'], "view") == 0) {
  if(isset($_GET['event'])) {
    $event = new Event($_GET['event']);
    $deck = $event->getPlaceDeck("1st");
  } else { 
    $deck = new Deck($_GET['id']);
  } 
  deckProfile($deck);
}
?> 
</div> <!-- gatherling_main box -->
</div> <!-- grid 10 suf 1 pre 1 -->

<?php print_footer(); ?> 

<?php

function deckForm($deck = NULL) {
  $mode = is_null($deck) ? "Create Deck" : "Update Deck";
  if (!is_null($deck)) { 
    $player = $deck->playername; 
    $event = $deck->eventname;
  } else { 
    $player = (isset($_POST['player'])) ? $_POST['player'] : $_GET['player'];
    $event = (isset($_POST['player'])) ? $_POST['event'] : $_GET['event'];
  } 

  $auth = false;
  if (is_null($deck)) {
    // Creating a deck.
    $entry = new Entry($event, $player);
    $auth = $entry->canCreateDeck($_SESSION['username']);
  } else {
    // Updating a deck.
    $auth = $deck->canEdit($_SESSION['username']);
  }

  if (!$auth) { 
    authFailed(); 
    return; 
  } 

  $vals = array();
  if(!is_null($deck)) {
    foreach ($deck->maindeck_cards as $card => $amt) { 
      $line = $amt . " " . $card . "\n";
      $vals['contents'] = $vals['contents'] . $line;
    }
    foreach ($deck->sideboard_cards as $card => $amt) { 
      $line = $amt . " " . $card . "\n";
      $vals['sideboard'] = $vals['sideboard'] . $line;
    }
		$vals['desc'] = $deck->notes;
		$vals['archetype'] = $deck->archetype;
		$vals['name'] = $deck->name;
	}

	echo "<form action=\"deck.php\" method=\"post\">\n";
	echo "<table align=\"center\" style=\"border-width: 0px;\">\n";
	echo "<tr><td valign=\"top\"><b>Directions:</td>\n";
	echo "<td style=\"color: #000000\">To enter your deck, please give it ";
	echo "a name and select an archetype from the drop-down menu below. If ";
	echo "you do not specify and archetype, your deck will be labeled as ";
	echo "\"rogue.\" To enter cards, save your deck a a .txt file using the ";
	echo "official MTGO client, and then copy and paste the maindeck and ";
	echo "sideboard into the appropriate text boxes. ";
	echo "<font color=\"#FF0000\">Do not use a format such as \"1x Card\". ";
	echo "The parser will not accept this structure. The correct pattern is ";
	echo "\"1 Card\".</font></td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td><b>Name</td>\n<td>";
	echo "<input type=\"text\" name=\"name\" value=\"{$vals['name']}\" ";
	echo "size=\"40\"></td></tr>\n";
	if(!is_null($deck)) {echo "<input type=\"hidden\" name=\"id\" value=\"{$deck->id}\">\n";}
	echo "<tr><td><b>Archetype</td>\n<td>";
	archetypeDropMenu($vals['archetype']);
	echo "</td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Main Deck</td>\n<td>";
	echo "<textarea rows=\"20\" cols=\"60\" name=\"contents\">";
	echo "{$vals['contents']}</textarea></td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Sideboard</td>\n<td>";
	echo "<textarea rows=\"10\" cols=\"60\" name=\"sideboard\">";
	echo "{$vals['sideboard']}</textarea></td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Comments</td>\n<td>";
	echo "<textarea rows=\"10\" cols=\"60\" name=\"notes\">";
	echo "{$vals['desc']}</textarea></td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"$mode\">\n";
	echo "<input type=\"hidden\" name=\"player\" value=\"$player\">";
	echo "<input type=\"hidden\" name=\"event\" value=\"$event\">";
	echo "</td></tr></table></form>\n";
}

function archetypeDropMenu($def) {
	$db = Database::getConnection();
	$result = $db->query("SELECT name FROM archetypes WHERE priority > 0
		ORDER BY priority DESC, name");
	echo "<select name=\"archetype\">\n";
	echo "<option value=\"Rogue\">- Archetype -</option>\n";
	while($arch = $result->fetch_assoc()) {
		$name = $arch['name'];
		$sel = (strcmp($name, $def) == 0) ? "selected" : "";
		echo "<option value=\"$name\" $sel>$name</option>\n";
  }
  $result->close(); 
}

function insertDeck() {
  $deck = new Deck(0);

  $deck->name = $_POST['name'];
  $deck->archetype = $_POST['archetype']; 
  $deck->notes = $_POST['notes'];

  $deck->playername = $_POST['player'];
  $deck->eventname = $_POST['event'];

  $deck->maindeck_cards = parseCards($_POST['contents']); 
  $deck->sideboard_cards = parseCards($_POST['sideboard']);

  $deck->save();

  return $deck;
}

function updateDeck($deck) {
  $deck->archetype = $_POST['archetype']; 
  $deck->name = $_POST['name'];
  $deck->notes = $_POST['notes'];
  
  $deck->maindeck_cards = parseCards($_POST['contents']); 
  $deck->sideboard_cards = parseCards($_POST['sideboard']);

  $deck->save();

  return $deck;
}

function parseCards($text) {
	$lines = split("\n", $text);
  $badcards = array();
  $cardarr = array();
  for ($ndx = 0; $ndx < sizeof($lines); $ndx++) {
    $chopped = chop($lines[$ndx]);
    if (preg_match("/[ \t]*([0-9]+)[ \t]+(.*)/i", $chopped, $m)) {
      $qty = $m[1];
      $card = chop($m[2]);
      // AE ligature. 
			$card = preg_replace("/\306/", "AE", $card);
			$card = strtolower($card);
      if(isset($cardarr[$card])) {
        $cardarr[$card] += $qty;
      } else {
        $cardarr[$card] = $qty;
      }
    }
  }

  return $cardarr; 
}

function printCardResults($result) {
	if(mysql_num_rows($result) == 0) {
		echo "<i>This deck has no cards in this category.</i>\n";}
	else {
		$firstcard = mysql_fetch_assoc($result);
		echo "{$firstcard['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$firstcard['name']}\" target=\"_blank\">";
		echo "{$firstcard['name']}</a>";
	}
	while($card = mysql_fetch_assoc($result)) {
		echo "<br>\n";
		echo "{$card['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$card['name']}\" target=\"_blank\">{$card['name']}</a>";
	}
}

function printPlaceString($medal) {
	$str = "";
	if(strcmp($medal, "t8") == 0) {$str = " - Top 8";}
	if(strcmp($medal, "t4") == 0) {$str = " - Top 4";}
	if(strcmp($medal, "2nd") == 0) {$str = " - 2nd Place";}
	if(strcmp($medal, "1st") == 0) {$str = " - 1st Place";}
	echo "$str";
}

function deckProfile($deck) {
  if ($deck == NULL || $deck->id == 0) { 
    echo "<span class=\"error\"><center>Deck is not found.  It is possible that it is not entered yet.</center></span>"; 
    return;
  } 
	echo "<center><form action=\"deckdl.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"id\" value={$deck->id}>\n";
  echo "<input type=\"submit\" name=\"mode\" ";
  echo "value=\"Download deck as .txt file\"></form></center><br>\n";
  echo "<div class=\"grid_5 alpha\"><div id=\"gatherling_lefthalf\">\n";
  deckInfoCell($deck);
  maindeckTable($deck);
  sideboardTable($deck);
  echo "</div> </div>\n"; 
  echo "<div class=\"grid_5 omega\"><div id=\"gatherling_righthalf\">\n";
  trophyCell($deck); 
  matchupTable($deck);
  echo "<div class=\"grid_2 alpha\">\n";
  symbolTable($deck); 
  echo "</div> <div class=\"grid_2 omega\">\n"; 
  ccTable($deck);
  echo "</div> </div> </div>\n"; 
  echo "<div class=\"clear\"></div>"; 
  echo "<div>";
  commentsTable($deck);
  echo "</div>"; 
  echo "<div class=\"clear\"></div>";
	echo "<center>\n";
	echo "<form action=\"deck.php\" method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"id\" value=\"$deck->id\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"Edit Deck\">\n";
	echo "</form></center>\n";
}

function commentsTable($deck) {
  $notes = $deck->notes;
	if($notes == "" || is_null($notes)) {
    $notes = "<i>No comments have been recorded for this deck.</i>";
  } else { 
    $notes = strip_tags($notes);
    $notes = preg_replace("/\n/", "<br />", $notes);
    $notes = preg_replace("/\[b\]/", "<b>", $notes);
    $notes = preg_replace("/\[\/b\]/", "</b>", $notes);
    $notes = preg_replace("/\[i\]/", "<i>", $notes);
    $notes = preg_replace("/\[\/i\]/", "</i>", $notes);
  } 
	echo "<table style=\"border-width: 0px; width: 100%; \" cellpadding=1>";
	echo "<tr><td><b>COMMENTS</td></tr>";
	echo "<tr><td>{$notes}</td></tr>";
	echo "</table>";
}

function deckInfoCell($deck) {
	$ncards = $deck->getCardCount();
  $event = $deck->getEvent();
	$day = date("F j, Y", strtotime($event->start));
	if($deck->medal == '1st') {
		$mstr = "<img src=\"/images/1st.gif\">&nbsp;";
		$placing = $mstr . "1st by";
	} else if($deck->medal == '2nd') {
		$mstr = "<img src=\"/images/2nd.gif\">&nbsp;";
		$placing = $mstr . "2nd by";
	} else if($deck->medal == 't4') {
		$mstr = "<img src=\"/images/t4.gif\">&nbsp;";
		$placing = $mstr . "Top 4 by";
	} else if($deck->medal == 't8') {
		$mstr = "<img src=\"/images/t8.gif\">&nbsp;";
		$placing = $mstr . "Top 8 by";
  } else {
    $placing = "Played by";
  }
	$line3 = "{$placing} <a href=\"profile.php?player={$deck->playername}\">";
  $line3 .= "{$deck->playername}</a> in ";
  $line3 .= "<span class=\"eventname\" title=\"{$day}\">{$event->name}</span>\n";

	$rstar = "<font color=\"#FF0000\">*</font>";
  $line1 = "<b>" . strtoupper($deck->name) . "</b>";
  $deck_format = $event->format;
	if($ncards < 6) {$line1 .= $rstar;}
	if($ncards < 60) {$line1 .= $rstar;}
	$line2 = $event->format . " &middot; " . $deck->getColorImages() . " " . $deck->archetype;
	$line3 .= "<i>(" . $deck->recordString() . ")</i>";

	echo "<table style=\"border-width: 0px\">\n";
	echo "<tr><td style=\"font-size: 10pt;\">$line1</td></tr>\n";
	echo "<tr><td>$line2</td></tr>\n";
  echo "<tr><td>$line3</td></tr>\n";
  echo "<tr><td>$line4</td></tr>\n";
	echo "</table>\n";

}

function trophyCell($deck) {
  if ($deck->medal == '1st') { 
    echo "<center>"; 
    if ($deck->getEvent()->hastrophy) { 
      echo $deck->getEvent()->getTrophyImageLink();
    } else { 
      echo "No trophy uploaded yet!";
    } 
    echo "</center><br /> <br />";
  } 
}

function sideboardTable($deck) {
  $sideboardcards = $deck->sideboard_cards;

  ksort($sideboardcards);
  arsort($sideboardcards, SORT_NUMERIC);
	echo "<table style=\"border-width: 0px\" cellpadding=1>\n";
  echo "<tr><td colspan=1><b>SIDEBOARD</td></tr>\n";
  foreach ($sideboardcards as $card => $amt) {
        echo "<tr><td>{$amt} ";
        echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
        echo "{$card}\" target=\"_blank\">{$card}</a></td></tr>\n";
    }
	echo "</table>\n";
}

function matchupTable($deck) {
  $matches = $deck->getMatches();

	echo "<table style=\"border-width: 0px\" cellpadding=1 align=\"right\">\n";
	echo "<tr><td colspan=4 align=\"left\"><b>MATCHUPS</td></tr>\n";
#	echo "<tr><td><b>Round</td><td><b>Result</td><td><b>Opponent</td>";
#	echo "<td><b>Deck</td></tr>\n";
  #	echo "<tr><td><b>MATCHUPS</td></tr>\n";
  if (count($matches) == 0) {
		echo "<tr><td colspan=4><i>No matches were found for this deck</td></tr>";
  }
  foreach ($matches as $match) { 
		$rnd = 'R' . $match->round;
		if($match->timing > 1 && $match->type == 'Single Elimination') {
			$rnd = 'T' . pow(2, $match->rounds - $match->round + 1);}
		$color = "#FF9900";
		$res = "Draw";
		if($match->playerWon($deck->playername)) {
			$color = "#009900";
			$res = "Win";
		}
		if($match->playerLost($deck->playername)) {
			$color = "#FF0000";
			$res = "Loss";
		}
		$resStr = "<b><font color=\"$color\">$res</font></b>";
		$opp = new Player($match->otherPlayer($deck->playername));
    $deckcell = "No Deck Found";
    $oppdeck = $opp->getDeckEvent($deck->eventname); 
		if($oppdeck != NULL) {
			$deckcell = "<a href=\"deck.php?id={$oppdeck->id}&mode=view\">" . 
         $oppdeck->name . "</a>";
		}

#		echo "<tr><td align=\"center\">$rnd</td>\n";
#		echo "<td><b><font color=\"$color\">$res</font></td>\n";
#		echo "<td>$opp</td>\n";
#		echo "<td>$deckcell</td></tr>\n";

#		echo "<tr><td>{$rnd}: $resStr vs. $opp, $deckcell</td></tr>";

		echo "<tr><td align=\"right\">$rnd:&nbsp;</td>\n";
		echo "<td align=\"left\"><b><font color=\"$color\">$res</font>&nbsp;</td>\n";
		echo "<td>vs.&nbsp;</td>\n";
		echo "<td align=\"left\"><a href=\"profile.php?player={$opp->name}\">{$opp->name}</a>&nbsp;</td>\n";
		echo "<td align=\"right\">$deckcell&nbsp;</td></tr>\n";
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "</table>\n";
}

function maindeckTable($deck) {
  $creatures = $deck->getCreatureCards();
  $lands = $deck->getLandCards(); 
  $other = $deck->getOtherCardS();
  
  echo "<table style=\"border-width: 0px\" cellpadding=1>\n";
	echo "<tr><td colspan=1><b>MAINDECK</td></tr>\n";
  echo "<tr><td colspan=2><i>Creatures</td></tr>\n";
  foreach ($creatures as $card => $amt) { 
		echo "<tr><td>{$amt} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$card}\" target=\"_blank\">{$card}</a></td></tr>\n";
  }
	echo "<tr><td colspan=2><i>Spells</td></tr>\n";
  foreach ($other as $card => $amt) { 
		echo "<tr><td>{$amt} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$card}\" target=\"_blank\">{$card}</a></td></tr>\n";
	}
	echo "<tr><td colspan=2><i>Lands</td></tr>\n";
  foreach ($lands as $card => $amt) { 
		echo "<tr><td>{$amt} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$card}\" target=\"_blank\">{$card}</a></td></tr>\n";
	}
	echo "</table>\n";
}

function ccTable($deck) {
  $convertedcosts = $deck->getCastingCosts();

	echo "<table style=\"border-width: 0px;\">\n";
	echo "<tr><td colspan=2 align=\"center\" width=150><b>CASTING COSTS</td></tr>";
  $total = 0; $cards = 0;
  foreach ($convertedcosts as $cost => $amt) { 
		echo "<tr><td align=\"right\" width=75>";
		echo "<img src=\"/images/mana{$cost}.gif\">";
		echo " &nbsp;</td>\n";
		echo "<td width=75 align=\"left\">{$amt}</td></tr>\n";
		$total += $cost * $amt;
		$cards += $amt;
	}
	if($cards == 0) {$cards = 1;}
	$avg = $total/$cards;
	echo "<tr><td align=\"right\"><i>Avg CMC:&nbsp;</td><td align=\"left\"><i>";
	printf("%1.2f", $avg);
	echo "</td></tr>\n";
	echo "</table>";
}

function symbolTable($deck) {
	echo "<table style=\"border-width: 0px\">\n";
	echo "<tr><td align=\"center\" colspan=2 width=150><b>MANA SYMBOLS";
  echo "</td></tr>\n";
  $cnts = $deck->getColorCounts();
	asort($cnts);
	$cnts = array_reverse($cnts, true);
	$sum = 0;
	foreach($cnts as $color => $num) {
		if($num > 0) {
		echo "<tr><td align=\"right\" width=75>";
		echo "<img src=\"/images/mana{$color}.gif\">";
		echo "&nbsp;</td>\n";
		echo "<td align=\"left\">$num</td></tr>\n";
		$sum += $num;
		}
	}
	echo "<tr><td align=\"right\"><i>Total:&nbsp;</td>\n";
	echo "<td align=\"left\"><i>$sum</td></tr>\n";
	echo "</table>\n";
}

function authFailed() {
    echo "You are not permitted to make that change. Please contact the ";
    echo "event host or deck owner to modify this deck. If you <b>are</b> the event host ";
    echo "or feel that you should have privilege to modify this deck, you ";
    echo "should contact jamuraa via the forums.<br><br>";
}

?>
