<?php session_start();
require_once 'lib.php';

$js = <<<EOD

function deckAjaxResult(data) {
  if (data.id != 0) {
    $("#deck-name").val(data.name);
    $("#deck-archetype").val(data.archetype);
    var decktext =  "";
    for (var card in data.maindeck) {
      decktext = decktext + data.maindeck[card] + " " + card + "\n";
    }
    $("#deck-contents").val(decktext);
    decktext = "";
    for (var card in data.sideboard) {
      decktext = decktext + data.sideboard[card] + " " + card + "\n";
    }
    $("#deck-sideboard").val(decktext);
  }
}

$(document).ready(function() {
  $("#autoenter-deck").change(function() {
    var selid = $("#autoenter-deck").val();
    $.ajax({
      url: 'ajax.php?deck=' + selid,
      success: deckAjaxResult
    });
    $("#autoenter-deck").val(0);
  });
});

EOD;

$deckboxScript = "<script src=\"http://deckbox.org/javascripts/bin/tooltip.js\"></script>";

print_header("PauperKrew.com | Gatherling | Deck Database", $js, $deckboxScript);

?>
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle">Deck Database</div>
<?php
if (!isset($_GET['mode'])) { $_GET['mode'] = ''; }
if (strcmp($_GET['mode'], "view") == 0) {
  $deck = NULL;
  if(isset($_GET['event'])) {
    $event = new Event($_GET['event']);
    $deck = $event->getPlaceDeck("1st");
  } else {
    if (isset($_GET['id'])) {
      $deck = new Deck($_GET['id']);
    }
  }
  deckProfile($deck);
} else {
  // Need to auth for everything else.
  if (!isset($_POST['player']) and isset($_GET['player'])) {
    $_POST['player'] = $_GET['player'];
  }
  $deck_player = isset($_POST['player']) ? $_POST['player'] : Player::loginName();
  $deck = isset($_POST['id']) ? new Deck($_POST['id']) : NULL;
  if (!isset($_POST['event'])) {
    $_POST['event'] = $_GET['event'];
  }
  if (checkDeckAuth($_POST['event'], $deck_player, $deck)) {
    if (strcmp($_POST['mode'], "Create Deck") == 0) {
      $deck = insertDeck();
      if (count($deck->errors) == 0) {
        deckProfile($deck);
      }
    } elseif (strcmp($_POST['mode'], "Update Deck") == 0) {
      $deck = updateDeck($deck);
      if (count($deck->errors) == 0) {
        deckProfile($deck);
      }
    } elseif (strcmp($_POST['mode'], "Edit Deck") == 0) {
      deckForm($deck);
    } elseif (strcmp($_GET['mode'], "create") == 0) {
      deckForm();
    }
  }
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

  if (!checkDeckAuth($event, $player, $deck)) {
    return;
  }

  $vals = array('contents' => '', 'sideboard' => '');
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
  echo "<td>To enter your deck, please give it ";
  echo "a name and select an archetype from the drop-down menu below. If ";
  echo "you do not specify and archetype, your deck will be labeled as ";
  echo "\"Unclassified\". To enter cards, save your deck a a .txt file using the ";
  echo "official MTGO client, and then copy and paste the maindeck and ";
  echo "sideboard into the appropriate text boxes. ";
  echo "<font color=\"#FF0000\">Do not use a format such as \"1x Card\". ";
  echo "The parser will not accept this structure. The correct pattern is ";
  echo "\"1 Card\".</font></td></tr>\n";
  echo "<tr><td><b>Recent Decks</b></td>\n<td>\n";
  echo "<select id=\"autoenter-deck\">\n";
  echo "<option value=\"0\">Select a recent deck to start from there</option>\n";
  $deckplayer = new Player($player);
  if (count($deck->errors) == 0 && count($deck->maindeck_cards) == 0) {
    $recentdecks = $deckplayer->getRecentDecks();
    foreach ($recentdecks as $adeck) {
      echo "<option value=\"{$adeck->id}\">{$adeck->name}</option>\n";
    }
  }

  echo "</select></td></tr>";
  if (count($deck->errors) > 0) {
    echo "<tr><td>Errors</td><td> There are some problems adding your deck: <ul>";
    foreach ($deck->errors as $error) {
      echo "<li>$error</li>";
    }
    echo "</ul></td></tr>";
  }
  echo "<tr><td><b>Name</td>\n<td>";
  echo "<input id=\"deck-name\" type=\"text\" name=\"name\" value=\"{$vals['name']}\" ";
  echo "size=\"40\"></td></tr>\n";
  if(!is_null($deck)) {echo "<input type=\"hidden\" name=\"id\" value=\"{$deck->id}\">\n";}
  echo "<tr><td><b>Archetype</td>\n<td>";
  archetypeDropMenu($vals['archetype']);
  echo "</td></tr>\n";
  echo "<tr><td valign=\"top\"><b>Main Deck</td>\n<td>";
  echo "<textarea id=\"deck-contents\" rows=\"20\" cols=\"60\" name=\"contents\">";
  echo "{$vals['contents']}</textarea></td></tr>\n";
  echo "<tr><td valign=\"top\"><b>Sideboard</td>\n<td>";
  echo "<textarea id=\"deck-sideboard\" rows=\"10\" cols=\"60\" name=\"sideboard\">";
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
  $archetypes = Deck::getArchetypes();
  echo "<select id=\"deck-archetype\" name=\"archetype\">\n";
  echo "<option value=\"Unclassified\">- Archetype -</option>\n";
  foreach ($archetypes as $name) {
    $sel = (strcmp($name, $def) == 0) ? "selected" : "";
    echo "<option value=\"$name\" $sel>$name</option>\n";
  }
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

  if (!$deck->save()) {
    deckForm($deck);
  }

  return $deck;
}

function updateDeck($deck) {
  $deck->archetype = $_POST['archetype'];
  $deck->name = $_POST['name'];
  $deck->notes = $_POST['notes'];

  $deck->maindeck_cards = parseCards($_POST['contents']);
  $deck->sideboard_cards = parseCards($_POST['sideboard']);

  if (!$deck->save()) {
    deckForm($deck);
  }

  return $deck;
}

function parseCards($text) {
  $lines = explode("\n", $text);
  $badcards = array();
  $cardarr = array();
  for ($ndx = 0; $ndx < sizeof($lines); $ndx++) {
    $chopped = chop($lines[$ndx]);
    if (preg_match("/[ \t]*([0-9]+)x?[ \t]+(.*)/i", $chopped, $m)) {
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
  echo "</div>\n";
  echo "<div class=\"clear\"></div>";
  exactMatchTable($deck);
  echo " </div> </div>\n";
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
    $mstr = "<img src=\"./imageset/1st.png\">&nbsp;";
    $placing = $mstr . "1st by";
  } else if($deck->medal == '2nd') {
    $mstr = "<img src=\"./imageset/2nd.png\">&nbsp;";
    $placing = $mstr . "2nd by";
  } else if($deck->medal == 't4') {
    $mstr = "<img src=\"./imageset/t4.png\">&nbsp;";
    $placing = $mstr . "Top 4 by";
  } else if($deck->medal == 't8') {
    $mstr = "<img src=\"./imageset/t8.png\">&nbsp;";
    $placing = $mstr . "Top 8 by";
  } else {
    $placing = "Played by";
  }
  $line3 = "{$placing} ";
  if ($deck->playername != NULL) {
    $deckplayer = new Player($deck->playername);
    $line3 .= $deckplayer->linkTo();
    $line3 .= " in <span class=\"eventname\" title=\"{$day}\">{$event->name}</span>\n";
  } else {
    $line3 .= "Never played (?) according to records.";
  }

  $rstar = "<font color=\"#FF0000\">*</font>";
  $name = $deck->name;
  if (empty($name)) {
    $name = "** NO NAME **";
  }
  $line1 = "<b>" . strtoupper($name) . "</b>";
  $deck_format = $event->format;
  if ($ncards < 6) {$line1 .= $rstar;}
  if ($ncards < 60) {
    $line1 .= $rstar;
    $line1 .= " ({$ncards} cards)";
  }
  $line2 = $event->format . " &middot; " . $deck->getColorImages() . " " . $deck->archetype;
  $line3 .= "<i>(" . $deck->recordString() . ")</i>";

  echo "<table style=\"border-width: 0px\">\n";
  echo "<tr><td style=\"font-size: 10pt;\">$line1</td></tr>\n";
  echo "<tr><td>$line2</td></tr>\n";
  echo "<tr><td>$line3</td></tr>\n";
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
    printCardLink($card);
    echo "</td></tr>";
  }
  echo "</table>\n";
}

function exactMatchTable($deck) {
  if ($deck->cardcount < 5) {
    return;
  }
  $decks = $deck->findIdenticalDecks();
  if (count($decks) == 0) {
    return false;
  }
  echo "<table style=\"border-width: 0px\" cellpadding=1 align=\"right\">\n";
  echo "<tr><th colspan=5 align=\"left\"><b>THIS DECK ALSO PLAYED AS</td></tr>\n";
  foreach ($decks as $deck) {
    if (!isset($deck->playername)) {
      continue;
    }
    $cell1 = medalImgStr($deck->medal);
    $cell4 = $deck->recordString();
    echo "<tr><td>$cell1</td>\n";
    echo "<td style=\"width: 140px\">" . $deck->linkTo() . "</td>\n";
    echo "<td>{$deck->playername}</td>\n";
    echo "<td><a href=\"{$deck->getEvent()->threadurl}\">{$deck->eventname}</a></td>\n";
    echo "<td style=\"text-align: right; width: 30px;\">$cell4</td></tr>\n";
  }
  echo "</table>\n";
}

function matchupTable($deck) {
  $matches = $deck->getMatches();

  echo "<table style=\"border-width: 0px\" cellpadding=1 align=\"right\">\n";
  echo "<tr><td colspan=4 align=\"left\"><b>MATCHUPS</td></tr>\n";
#  echo "<tr><td><b>Round</td><td><b>Result</td><td><b>Opponent</td>";
#  echo "<td><b>Deck</td></tr>\n";
  #  echo "<tr><td><b>MATCHUPS</td></tr>\n";
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
      $deckcell = $oppdeck->linkTo();
    }

    echo "<tr><td align=\"right\">$rnd:&nbsp;</td>\n";
    echo "<td align=\"left\"><b><font color=\"$color\">$res</font>&nbsp;</td>\n";
    echo "<td>vs.&nbsp;</td>\n";
    echo "<td align=\"left\">" . $opp->linkTo() . "&nbsp;</td>\n";
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
    printCardLink($card);
    echo "</td></tr>\n";
  }
  echo "<tr><td colspan=2><i>Spells</td></tr>\n";
  foreach ($other as $card => $amt) {
    echo "<tr><td>{$amt} ";
    printCardLink($card);
    echo "</td></tr>\n";
  }
  echo "<tr><td colspan=2><i>Lands</td></tr>\n";
  foreach ($lands as $card => $amt) {
    echo "<tr><td>{$amt} ";
    printCardLink($card);
    echo "</td></tr>\n";
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
    echo "<img src=\"./imageset/mana{$cost}.png\">";
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
    echo "<img src=\"./imageset/mana{$color}.png\">";
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
  echo "You are not permitted to make that change.  It could be because you are not the ";
  echo "player who played the deck, or the event has been finalized. Please contact the ";
  echo "event host or deck owner to modify this deck. If you <b>are</b> the event host ";
  echo "or feel that you should have privilege to modify this deck, you ";
  echo "should contact jamuraa via the forums.<br><br>";
}

function loginRequired() {
  echo "<center>You can't do that unless you <a href=\"login.php\">log in first</a></center>";
}

function checkDeckAuth($event, $player, $deck = NULL) {
  if (!Player::isLoggedIn()) {
    loginRequired();
    return false;
  }
  if (is_null($deck)) {
    // Creating a deck.
    $entry = new Entry($event, $player);
    $auth = $entry->canCreateDeck(Player::loginName());
  } else {
    // Updating a deck.
    $auth = $deck->canEdit(Player::loginName());
  }

  if (!$auth) {
    authFailed();
  }
  return $auth;
}

?>
