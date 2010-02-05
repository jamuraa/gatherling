<?php session_start();
require_once 'lib.php';
$player = Player::getSessionPlayer();

print_header("PDCMagic.com | Gatherling | Player Control Panel");
?>
<div class="grid_10 suffix_1 prefix_1"> 
<div id="gatherling_main" class="box"> 
<div class="uppertitle"> Player Control Panel </div>
<?php 
if ($player == NULL) {  
  echo "<center> You must <a href=\"login.php\">log in</a> to use your player control panel.</center>\n";
} else { 
  // Handle actions
  if (isset($_POST['action'])) {
    if ($_POST['action'] == 'setIgnores') { 
      setPlayerIgnores(); 
    } else if ($_POST['action'] == 'changePassword') {
      $success = false;
      if ($_POST['newPassword2'] == $_POST['newPassword']) {
        if (strlen($_POST['newPassword']) >= 6) {
          $authenticated = Player::checkPassword($_POST['username'], $_POST['oldPassword']);
          if ($authenticated) {
            $player = new Player($_POST['username']); 
            $player->setPassword($_POST['newPassword']);
            $result = "Password changed.";
            $success = true;
          } else { 
            $result = "Password *not* changed, your old password was incorrect!";
          }
        } else { 
          $result = "Password *not* changed, your new password needs to be longer!";
        }
      } else { 
        $result = "Password *not* changed, your new passwords did not match!";
      }
    } else if ($_POST['action'] == 'verifyAccount') { 
      $success = false;
      if ($player->checkChallenge($_POST['challenge'])) { 
        $player->setVerified(true);
        $result = "Successfully verified your account with MTGO.";
        $success = true; 
      } else { 
        $result = "Your challenge is wrong.  Get a new one by sending the message 'ua pdcmagic' to infobot on MTGO!"; 
      } 
    }  
  } 
  // Handle modes 
  $dispmode = 'playercp';
  if (isset($_GET['mode'])) { 
    $dispmode = $_GET['mode'];
  }
  if (isset($_POST['mode'])) { 
    $dispmode = $_POST['mode']; 
  } 
  if ($dispmode == 'alldecks') { 
    print_allContainer();
  } elseif ($dispmode == 'allratings') { 
    if(!isset($_GET['format'])) {$_GET['format'] = "Composite";}
    print_ratingsTable($_SESSION['username']);
    echo "<br><br>";
    print_ratingHistoryForm($_GET['format']);	
    echo "<br>";
    print_ratingsHistory($_GET['format']);
  } elseif ($dispmode == 'allmatches') {  
    print_allMatchForm($player); 
    print_matchTable($player);
  } elseif ($dispmode == 'Filter Matches') {
    print_allMatchForm($player);
    print_matchTable($player);
  } elseif ($dispmode == 'changepass') {
    print_changePassForm($player, $result);
  } elseif ($dispmode == 'verifymtgo') { 
    print_verifyMtgoForm($player, $result);
  } else { 
    print_mainPlayerCP($player); 
  }
}
?>
</div> <!-- gatherling_main box -->
</div> <!-- grid 10 suff 1 pre 1 -->

<?php print_footer(); ?>

<?php

function print_changePassForm($player, $result) { 
  echo "<center><h3>Changing your password</h3>
    New passwords are required to be at least 6 characters long.</center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"action\" type=\"hidden\" value=\"changePassword\" />\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"changepass\" />\n";
  echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
  echo "<table class=\"form\">";
  echo "<tr><th>Current Password</th>\n";
  echo "<td> <input name=\"oldPassword\" type=\"password\" /></td> </tr> \n";
  echo "<tr><th>New Password</th>\n";
  echo "<td> <input name=\"newPassword\" type=\"password\" /></td> </tr> \n";
  echo "<tr><th>Repeat New Password</th>\n";
  echo "<td> <input name=\"newPassword2\" type=\"password\" /></td> </tr> \n";
  echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
  echo "<input name=\"submit\" type=\"submit\" value=\"Change Password\" />\n";
  echo "</td> </tr> </table> \n";
  echo "</form>\n"; 
  echo "<div class=\"clear\"> </div>\n";
} 

function print_verifyMtgoForm($player, $result) { 
  echo "<center><h3>Verifying your MTGO account</h3>
    Verify your MTGO account by following these simple steps:<br />
    1. Chat 'ua pdcmagic' to infobot to get a verification code <br />
    2. Enter the verification code here to be verified <br />
    \n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  if ($player->verified == 1) { 
    echo "<center>You are already verified!</center>\n";
    echo "<a href=\"player.php\">Go back to the Player CP</a>\n";
  } else { 
    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"action\" type=\"hidden\" value=\"verifyAccount\" />\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"verifymtgo\" />\n";
    echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
    echo "<table class=\"form\">";
    echo "<tr><th>Verification Code</th>\n";
    echo "<td> <input name=\"challenge\" type=\"text\" /></td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Verify Account\" />\n";
    echo "</td> </tr> </table> \n";
    echo "</form>\n"; 
  }
  echo "<div class=\"clear\"> </div>\n";
} 

function setPlayerIgnores() {
  global $player; 
  $noDeckEntries = $player->getNoDeckEntries(); 
  foreach ($noDeckEntries as $entry) { 
    if (isset($_POST['ignore'][$entry->event->name])) { 
      $entry->setIgnored(1);
    } else { 
      $entry->setIgnored(0);
    } 
  }
}

function print_mainPlayerCP($player) {
  $upper = strtoupper($_SESSION['username']);
  echo "<div class=\"alpha grid_5\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_conditionalAllDecks(); 
  print_recentDeckTable(); 
  print_ratingsTableSmall(); 
  print_recentMatchTable(); 
  echo "</div></div>\n";
  echo "<div class=\"omega grid_5\">\n"; 
  echo "<div id=\"gatherling_righthalf\">\n";
  print_statsTable(); 
  echo "<b>ACTIONS</b><br />\n";
  echo "<ul>\n";
  echo "<li><a href=\"player.php?mode=changepass\">Change your password</a></li>\n";
  if ($player->verified == 0) { 
    echo "<li><a href=\"player.php?mode=verifymtgo\">Verify your MTGO account</a></li>\n";
  }
  echo "</ul>\n";
  echo "</div></div>\n";
  echo "<div class=\"clear\"></div>\n";
}

function print_allContainer() {
  $rstar = "<font color=\"#FF0000\">*</font>";
  echo "<p> Decks marked with a $rstar have less than 60 cards listed. <br />\n";
  echo " Decks marked with $rstar$rstar have less than 6 cards listed, and were created as placeholder decks.</p>\n";
  echo "<div class=\"alpha grid_6\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_allDeckTable();
  echo "</div> </div> \n";
  echo "<div class=\"omega grid_4\">\n"; 
  echo "<div id=\"gatherling_righthalf\">\n";
	print_noDeckTable();
  echo "</div> </div> \n";
  echo "<div class=\"clear\"> </div> ";
}

function print_recentDeckTable() {
  global $player;
  $event = $player->getLastEventPlayed(); 
  $entry = new Entry($event->name, $player->name); 
  if ($entry->deck) { 
    $decks = $player->getRecentDecks(6);
  } else { 
    $decks = $player->getRecentDecks(5);
  }

	echo "<table style=\"border-width: 5px solid black;\">\n";
	echo "<tr><td colspan=2><b>RECENT DECKS</td>\n";
	echo "<td colspan=2 align=\"right\">";
	echo "<a href=\"player.php?mode=alldecks\">";
  echo "(see all)</a></td>\n";
  if (!$entry->deck) { 
    $cell1 = medalImgStr($entry->medal);
    $cell4 = $entry->recordString();
    echo "<tr><td>$cell1</td>\n";
		echo "<td align=\"left\"><a style=\"font-size: 11px; color: #D28950;\" href=\"deck.php?mode=create&event={$entry->event->name}&";
		echo "player={$player->name}\">[Create Deck]</a></td>";
    echo "<td><a href=\"{$event->threadurl}\">{$event->name}</a></td>\n";
    echo "<td align=\"right\">$cell4</td></tr>\n";
  } 
  foreach ($decks as $deck) {
		$cell1 = medalImgStr($deck->medal);
		$cell4 = $deck->recordString();
		echo "<tr><td>$cell1</td>\n";
		echo "<td><a href=\"deck.php?mode=view&id={$deck->id}\">";
		echo "{$deck->name}</a></td>\n";
		echo "<td><a href=\"{$deck->getEvent()->threadurl}\">{$deck->eventname}</a></td>\n";
		echo "<td align=\"right\">$cell4</td></tr>\n";
	}
	echo "</table>\n";
}

function print_noDeckTable() {
  global $player;
  $entriesnodecks = $player->getNoDeckEntries();
	#$query = "SELECT n.medal, e.name, e.format, e.threadurl
	#	FROM entries AS n, events AS e
	#	WHERE n.player=\"$player\" AND n.deck IS NULL
	#	AND n.event=e.name
	#	ORDER BY e.start DESC";

  echo "<form action=\"player.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"action\" value=\"setIgnores\" />";
  echo "<table style=\"border-width: 0px;\" width=275>\n";
	echo "<tr><td colspan=4 style=\"font-size: 14px; color: red;\">";
  echo "<b>UNENTERED DECKS</td></tr>\n";
  foreach ($entriesnodecks as $entry) { 
		$imgcell = medalImgStr($entry->medal);
    echo "<tr><td>$imgcell</td>\n";
		echo "<td align=\"left\"><a style=\"font-size: 11px; color: #D28950;\" href=\"deck.php?mode=create&event={$entry->event->name}&";
		echo "player={$player->name}\">[Create Deck]</a></td>";
    echo "<td align=\"right\"><a href=\"{$entry->event->threadurl}\">{$entry->event->name}</a></td>\n";
    echo "<td><input type=\"checkbox\" name=\"ignore[{$entry->event->name}]\" value=\"yes\" ";
    if ($entry->ignored) {
      echo " checked";
    } 
    echo " /> </td> ";
		echo "</tr>\n";
	}
  echo "</table>\n";
  echo "<input type=\"hidden\" name=\"mode\" value=\"alldecks\" />";
  echo "<center><input type=\"submit\" value=\"Set Ignored / Unremembered Decks\" /></center>";
  echo "</form>";
}

function print_allDeckTable() {
  global $player;
  $decks = $player->getAllDecks();
	$rstar = "<font color=\"#FF0000\">*</font>";
	$upPlayer = strtoupper($player->name);

  echo "<table style=\"border-width: 0px;\" width=275>\n";
  echo "<tr><td colspan=3><b>$upPlayer'S DECKS</td></tr>\n";
  foreach ($decks as $deck) { 
    $imgcell = medalImgStr($deck->medal);
    echo "<td width=20>$imgcell</td>\n";
    echo "<td><a href=\"deck.php?mode=view&id={$deck->id}\">";
    echo "{$deck->name}</a>";
    $cards = $deck->getCardCount();
		if($cards < 60) {print $rstar;}
		if($cards < 6)  {print $rstar;}
    echo "</td>\n";
    $event = $deck->getEvent();
    echo "<td align=\"right\"><a href=\"{$event->threadurl}\">{$event->name}</a></td>\n";
    echo "</td></tr>\n";
  }
  echo "</table>\n";
}

function print_recentMatchTable() {
  global $player;
  $matches = $player->getRecentMatches();
  
  echo "<table style=\"border-width: 0px\" width=300>\n";
	echo "<tr><td colspan=3><b>RECENT MATCHES</td><td align=\"right\">\n";
  echo "<a href=\"player.php?mode=allmatches\">(see all)</a></td></tr>\n";
  foreach ($matches as $match) {
		$res = "D"; $color = "#FF9900";
		if ($match->playerWon($player->name)) {
      $res = "W";
      $color = "#009900";
    }
    if ($match->playerLost($player->name)) { 
      $res = "L";
      $color = "#FF0000";
    }
		$opp = $match->playera;
    if (strcasecmp($player->name, $opp) == 0) {
      $opp = $match->playerb;
    }
		echo "<tr><td><b><font color=\"$color\">$res</font></b></td>\n";
    echo "<td>vs.</td>\n";
    $oppplayer = new Player($opp); 
    echo "<td>" . $oppplayer->linkTo() . "</td></tr>\n";
	}
	echo "</table>\n";
}

function print_matchTable($player, $limit=0) {
  if (!isset($_POST['format'])) { 
    $_POST['format'] = "%"; 
  }
  if (!isset($_POST['series'])) { 
    $_POST['series'] = "%"; 
  } 
  if (!isset($_POST['season'])) { 
    $_POST['season'] = "%"; 
  } 
  if (!isset($_POST['opp'])) { 
    $_POST['opp'] = "%"; 
  } 

  $matches = $player->getFilteredMatches($_POST['format'], $_POST['series'], $_POST['season'], $_POST['opp']);

	$hc = headerColor();
	echo "<table style=\"border-width: 0px\">\n";
	echo "<tr style=\"background-color: $hc;\"><td><b>Event</td><td align=\"center\"><b>Round</td>";
	echo "<td><b>Opponent</td>\n";
	echo "<td><b>Deck</td>\n";
	echo "<td align=\"center\"><b>Rating</td>\n";
	echo "<td align=\"center\"><b>Result</td></tr>\n";
	$oldname = "";
  foreach ($matches as $match) { 
		$rnd = $match->round;
		if($match->timing == 2 && $match->type == "Single Elimination") {
      $rnd = "T" . pow(2, $match->rounds + 1 - $match->round);
    }
		$opp = $match->otherPlayer($player->name);
		$res = "Draw";
		$color = "#FF9900";
		if ($match->playerWon($player->name)) {
			$res = "Win";
			$color = "#009900";
		}
		if ($match->playerLost($player->name)) { 
			$res = "Loss";
			$color = "#FF0000";
    }
    $opponent = new Player($opp);

    $event = $match->getEvent();
    $oppRating = $opponent->getRating("Composite", $event->start); 
		$oppDeck = $opponent->getDeckEvent($event->name);
		$deckStr = "No Deck Found";
		if(!is_null($oppDeck)) {
			$deckStr = "<a href=\"deck.php?mode=view&id={$oppDeck->id}\">" .
				"{$oppDeck->name}</a>";
		}

		if($oldname != $event->name) {
			$bg = rowColor();
			echo "<tr style=\"background-color: $bg\"><td>{$event->name}</td>";
		}
		else {echo "<tr style=\"background-color: $bg;\"><td></td>\n";}
		$oldname = $event->name;
    echo "<td align=\"center\">$rnd</td>\n";
    echo "<td>" . $opponent->linkTo() . "</td>\n";
		echo "<td>$deckStr</td>\n";
		echo "<td align=\"center\">$oppRating</td>\n";
		echo "<td align=\"center\"><b><font color=\"$color\">$res</font>";
		echo "</td></tr>\n";
	}
	echo "</table>";
}

function print_ratingsTableSmall() {
  global $player;
	$composite = $player->getRating("Composite");
	$standard = $player->getRating("Standard");
	$extended = $player->getRating("Extended");
	$classic = $player->getRating("Classic");
	$other = $player->getRating("Other Formats");

	echo "<table style=\"border-width: 0px;\" width=300>";
	echo "<tr><td colspan=1><b>MY RATINGS</td>\n";
	echo "<td colspan=1 align=\"right\">";
	echo "<a href=\"player.php?mode=allratings\">(see all)</a></td></tr>\n";
	echo "<tr><td>Composite</td><td align=\"right\">$composite</td></tr>\n";
	echo "<tr><td>Standard</td><td align=\"right\">$standard</td></tr>\n";
  echo "<tr><td>Extended</td><td align=\"right\">$extended</td></tr>\n";
	echo "<tr><td>Classic</td><td align=\"right\">$classic</td></tr>\n";
	echo "<tr><td>Other Formats</td><td align=\"right\">$other</td></tr>\n";
	echo "</table>";
}

function print_ratingsTable() {
	echo "<table style=\"border-width: 0px;\" width=400 align=\"center\">\n";
	echo "<tr><td><b>Format</td>\n";
	echo "<td align=\"center\"><b>Rating</td>\n";
	echo "<td align=\"center\"><b>Record</td>\n";
	echo "<td align=\"center\"><b>Low</td>\n";
	echo "<td align=\"center\"><b>High</td></tr>\n";
	print_ratingLine("Composite");
	print_ratingLine("Standard");
	print_ratingLine("Extended");
	print_ratingLine("Classic");
	print_ratingLine("Other Formats");
	echo "</table>\n";
}

function print_ratingLine($format) {
  global $player;
	$rating = $player->getRating($format);
  $record = $player->getRatingRecord($format);
  $max = $player->getMaxRating($format);
  $min = $player->getMinRating($format);

	echo "<tr><td>$format</td>\n";
	echo "<td align=\"center\">$rating</td>\n";
	echo "<td align=\"center\">$record</td>\n";
	if(isset($min)) {
		echo "<td align=\"center\">$min</td>\n";
		echo "<td align=\"center\">$max</td>\n";
	}
	else {
		echo "<td colspan=2 align=\"center\">";
		echo "<i>Less than 20 matches played</td>\n";
	}
	echo "</tr>\n";
}

function print_ratingsHistory($format) {
  global $player;
	$db = Database::getConnection();
	$stmt = $db->prepare("SELECT e.name, r.rating, n.medal, n.deck AS id
		FROM events e, entries n, ratings r
		WHERE r.format= ? AND r.player = ?
		AND e.start=r.updated AND n.player=r.player AND n.event=e.name
    ORDER BY e.start DESC"); 
  $stmt->bind_param("ss", $format, $player->name); 
  $stmt->execute(); 
  $stmt->bind_result($eventname, $rating, $medal, $deckid);

  $stmt->store_result();

	echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
	echo "<tr><td align=\"center\"><b>Pre-Event</td>\n";
	echo "<td><b>Event</td>\n";
	echo "<td><b>Deck</td>\n";
	echo "<td align=\"center\"><b>Record</td>\n";
	echo "<td align=\"center\"><b>Medal</td>\n";
	echo "<td align=\"center\"><b>Post-Event</td></tr>\n";

  if($stmt->num_rows > 0) {
    $stmt->fetch();
    $preveventname = $eventname; 
    $prevrating = $rating;
    while($stmt->fetch()) {
      $entry = new Entry($preveventname, $player->name);
			$wl = $entry->recordString();
			$img = medalImgStr($entry->medal);

			echo "<tr><td align=\"center\">{$rating}</td>\n";
			echo "<td>{$preveventname}</td>\n";
			echo "<td><a href=\"deck.php?id={$entry->deck->id}&mode=view\">";
			echo "{$entry->deck->name}</a></td>\n";
			echo "<td align=\"center\">$wl</td>\n";
			echo "<td align=\"center\">$img</td>";
			echo "<td align=\"center\">{$prevrating}</td></tr>";
      $prevrating = $rating;
      $preveventname = $eventname;
    }
    
    $entry = new Entry($preveventname, $player->name);
    $wl = $entry->recordString();
    $img = medalImgStr($entry->medal);
		echo "<tr><td align=\"center\">1600</td>\n";
        echo "<td>{$preveventname}</td>\n";
        echo "<td><a href=\"deck.php?id={$entry->deck->id}&mode=view\">";
        echo "{$entry->deck->name}</a></td>\n";
        echo "<td align=\"center\">$wl</td>\n";
        echo "<td align=\"center\">$img</td>";
        echo "<td align=\"center\">{$prevrating}</td></tr>";
	} else {
		echo "<tr><td colspan=6 align=\"center\"><i>";
		echo "You have not played any $format events.</td></tr>\n";
	}
	echo "</table>\n";
}	

function print_ratingHistoryForm($format) {
	$formats = array("Composite", "Standard", "Extended", "Classic",
		"Other Formats");
	echo "<center>\n";
	echo "<form action=\"player.php\" method=\"get\">\n";
	echo "Show history for&nbsp;";
	echo "<select name=\"format\">\n";
	for($i = 0; $i < sizeof($formats); $i++) {
		$sel = ($formats[$i] == $format) ? "selected" : "";
		echo "<option value=\"{$formats[$i]}\" $sel>{$formats[$i]}</option>\n";
	}
	echo "</select><br><br>\n";
	echo "<input type=\"submit\" name=\"button\" value=\"Show History\">\n";
	echo "<input type=\"hidden\" name=\"mode\" value=\"allratings\">\n";
	echo "</form></center>\n";
}

function print_allMatchForm($player) {
  if (!isset($_POST['format'])) { 
    $_POST['format'] = "%";
  } 
  if (!isset($_POST['series'])) { 
    $_POST['series'] = "%";
  } 
  if (!isset($_POST['season'])) { 
    $_POST['season'] = "%"; 
  } 
  if (!isset($_POST['opp'])) { 
    $_POST['opp'] = "%";
  } 
	echo "<form action=\"player.php\" method=\"post\">\n";
	echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
	echo "<tr><td align=\"center\" colspan=2><b>Filters</td></tr>\n";
	echo "<tr><td>&nbsp;</td>\n";
	echo "<tr><td>Format&nbsp</td><td>";
	formatDropMenuP($player, $_POST['format']);
	echo "</td></tr>\n";
	echo "<tr><td>Series&nbsp;</td><td>";
	seriesDropMenuP($player, $_POST['series']);
	echo "</td></tr>\n";
	echo "<tr><td>Season&nbsp;</td><td>";
	seasonDropMenuP($player, $_POST['season']);
	echo "</td></tr>\n";
	echo "<tr><td>Opponent&nbsp;</td><td>";
	oppDropMenu($player, $_POST['opp']);
	echo "</td></tr><tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\">";
	echo "<input type=\"submit\" name=\"mode\" value=\"Filter Matches\">";
	echo "</td></tr><tr><td>&nbsp;</td></tr></table></form>\n";
}

function formatDropMenuP($player, $def) {
  $formats = $player->getFormatsPlayed();
  
  echo "<select name=\"format\">\n";
	echo "<option value=\"%\">- Format -</option>\n";
  foreach ($formats as $thisformat) { 
		$sel = ($thisformat == $def) ? "selected" : "";
		echo "<option value=\"$thisformat\" $sel>$thisformat</option>\n";
	}
	echo "</select>\n";
}

function seriesDropMenuP($player, $def) {
  $series = $player->getSeriesPlayed();
  
  echo "<select name=\"series\">\n";
  echo "<option value=\"%\">- Series -</option>\n";
  foreach ($series as $thisseries) {
    $sel = ($thisseries == $def) ? "selected" : "";
    echo "<option value=\"$thisseries\" $sel>$thisseries</option>\n";
  }
  echo "</select>\n";
}

function seasonDropMenuP($player, $def) {
  $seasons = $player->getSeasonsPlayed();

  echo "<select name=\"season\">\n";
  echo "<option value=\"%\">- Season -</option>\n";
  foreach ($seasons as $thisseason) {
    $sel = (($thisseason == $def) && ($def != "%")) ? "selected" : "";
    echo "<option value=\"$thisseason\" $sel>$thisseason</option>\n";
  }
  echo "</select>\n";
}

function oppDropMenu($player, $def) {
  $opponents = $player->getOpponents();
	
	echo "<select name=\"opp\">\n";
	echo "<option value=\"%\">- Opponent -</option>\n";
  foreach ($opponents as $row) { 
		$thisopp = $row['opp'];
		$cnt = $row['cnt'];
		$sel = ($thisopp == $def) ? "selected" : "";
		echo "<option value=\"$thisopp\" $sel>$thisopp [$cnt]</option>\n";
	}
	echo "</select>";
}

function print_statsTable() {
  global $player;
	echo "<table style=\"border-width: 0px\">";
	echo "<tr><td colspan=2><b>STATISTICS</td></tr>\n";
	echo "<tr><td>Record</td><td align=\"right\"> {$player->getRecord()}";
	echo "</td></tr>\n";
	echo "<tr><td>Longest Winning Streak</td><td align=\"right\"> {$player->getStreak("W")}";
	echo "</td></tr>\n";
	echo "<tr><td>Longest Losing Streak</td><td align=\"right\"> {$player->getStreak("L")}"; 
  echo "</td></tr>\n";
  $rivalname = $player->getRival(); 
  $rivalrec = $player->getRecordVs($rivalname);
  $rival = new Player($rivalname);
  echo "<tr><td>Biggest Rival</td><td align=\"right\"> "; 
  echo $rival->linkTo(); 
  echo " ({$rivalrec})";
	echo "</td></tr>";
	echo "<tr><td>Favorite Card</td><td align=\"right\"> {$player->getFavoriteNonLand()}";
	echo "</td></tr>\n";
	echo "<tr><td>Favorite Land</td><td align=\"right\"> {$player->getFavoriteLand()}";
    echo "</td></tr>\n";
	echo "<tr><td>Medals Won</td><td align=\"right\"> {$player->getMedalCount()}";
	echo "</td></tr>\n";
	echo "<tr><td>Events Won</td><td align=\"right\"> {$player->getMedalCount("1st")}";
	echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\"><b>Most Recent Trophy</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\">"; statTrophy(); 
	echo "</td></tr>\n";
	echo "</table>\n";
#Fave Series
#Fave Format
#highrating
#lowrating
#bestdeck
#creativity
}

function statTrophy() {
  global $player;
  $trophyevent = $player->getLastEventWithTrophy();
  if ($trophyevent != NULL) { 
    $event = new Event($trophyevent); 
    echo $event->getTrophyImageLink(); 
  } else { 
		echo "<i>No trophies earned</i>\n";
	}
}

function print_conditionalAllDecks() {
  global $player;
  $noentrycount = $player->getUnenteredCount();
  if ($noentrycount > 0) { 
		echo "<br><a href=\"player.php?mode=alldecks\" style=\"color: red;\">";
		echo "You have $noentrycount unreported decks<br>";
		echo "Click here to enter them.</a>";
	}
}

?>
