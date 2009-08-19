<?php session_start();?>
<?php 
require_once 'lib.php';

$player = Player::getSessionPlayer();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Player Control Panel</title>
<?php print_header(); ?>
<?php include 'gathnav.php';?>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>PLAYER CONTROL PANEL</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content(); ?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3><?php version_tagline(); ?></h3>
</td></tr></table></div>
<br /><br /></div></div>
<?php print_footer(); ?>

<?php
function content() {
  global $player;
	if ($player == NULL) {
		echo "<center>You must <a href=\"login.php\">log in</a> to use your";
		echo " player control panel.</center>\n";
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'alldecks') {
		allContainer();
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'allratings') {
		if(!isset($_GET['format'])) {$_GET['format'] = "Composite";}
		ratingsTable($_SESSION['username']);
		echo "<br><br>";
		ratingHistoryForm($_GET['format']);	
		echo "<br>";
		ratingsHistory($_GET['format']);
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'allmatches') {
		allMatchForm($player);
		matchTable($player);
	}
	elseif(isset($_POST['mode']) && $_POST['mode'] == 'Filter Matches') {
		allMatchForm($player);
		matchTable($player);
	}
	else {
		mainPlayerCP($_SESSION['username']);
	}
}

function mainPlayerCP($player) {
	$upper = strtoupper($_SESSION['username']);
	echo "<table align=\"center\" width=600 style=\"border-width: 0px\">\n";
	echo "<tr><td><b>Welcome, $upper!";
	conditionalAllDecks();
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td width=300 valign=\"top\">";
	
	echo "<table align=\"center\" width=300 style=\"border-width: 0px\">\n";
	echo "<tr><td>"; recentDeckTable(); echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td>"; ratingsTableSmall(); echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td>"; recentMatchTable(); echo "</td></tr>\n";
	echo "</table>\n";
	
	echo "</td>\n<td width=300 align=\"right\" valign=\"top\">";
	echo "<table style=\"border-width: 0px;\" align=\"right\" width=300>\n";
	echo "<tr><td align=\"right\">"; statsTable(); echo "</td></tr>\n";
	echo "</table>\n";

	echo "</td></tr></table>\n";
}

function allContainer() {
	$rstar = "<font color=\"#FF0000\">*</font>";
	echo "<table style=\"border-width: 0px;\" width=600>\n";
	echo "<tr><td colspan=2>Decks marked with a $rstar have less than 60 ";
    echo "cards listed. Decks marked with $rstar$rstar have less than 6 cards ";
    echo "listed, and were created as placeholder decks.</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td valign=\"top\" width=275>";
	allDeckTable();
	echo "</td>\n<td valign=\"top\" width=275 align=\"right\">";
	noDeckTable();
	echo "</td></tr></table>";
}

function recentDeckTable() {
  global $player;
  $decks = $player->getRecentDecks(5);

	echo "<table style=\"border-width: 0px;\" width=300>\n";
	echo "<tr><td colspan=3><b>RECENT DECKS</td>\n";
	echo "<td align=\"right\">";
	echo "<a href=\"player.php?mode=alldecks\">";
  echo "(see all)</a></td>\n";
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

function noDeckTable() {
  global $player;
  $entriesnodecks = $player->getNoDeckEntries();
	#$query = "SELECT n.medal, e.name, e.format, e.threadurl
	#	FROM entries AS n, events AS e
	#	WHERE n.player=\"$player\" AND n.deck IS NULL
	#	AND n.event=e.name
	#	ORDER BY e.start DESC";
  
  echo "<table style=\"border-width: 0px;\" width=275>\n";
	echo "<tr><td colspan=3 style=\"font-size: 14px; color: red;\">";
  echo "<b>UNENTERED DECKS</td></tr>\n";
  foreach ($entriesnodecks as $entry) { 
		$imgcell = medalImgStr($entry->medal);
    echo "<tr><td>$imgcell</td>\n";
		echo "<td align=\"left\"><a style=\"font-size: 11px; color: #D28950;\" href=\"deck.php?mode=create&event={$entry->event->name}&";
		echo "player={$player->name}\">[Create Deck]</a></td>";
		echo "<td align=\"right\"><a href=\"{$entry->event->threadurl}\">{$entry->event->name}</a></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}

function allDeckTable() {
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

function recentMatchTable() {
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
		echo "<td><a href=\"profile.php?player=$opp\">$opp</td></tr>\n";
	}
	echo "</table>\n";
}

function matchTable($player, $limit=0) {
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
	echo "<table style=\"border-width: 0px\" width=600>\n";
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
		echo "<td><a href=\"profile.php?player=$opp\">$opp</a></td>\n";	
		echo "<td>$deckStr</td>\n";
		echo "<td align=\"center\">$oppRating</td>\n";
		echo "<td align=\"center\"><b><font color=\"$color\">$res</font>";
		echo "</td></tr>\n";
	}
	echo "</table>";
}

function ratingsTableSmall() {
  global $player;
	$composite = $player->getRating("Composite");
	$standard = $player->getRating("Standard");
	$futex = $player->getRating("Future Extended");
	$classic = $player->getRating("Classic");
	$other = $player->getRating("Other Formats");

	echo "<table style=\"border-width: 0px;\" width=300>";
	echo "<tr><td colspan=1><b>MY RATINGS</td>\n";
	echo "<td colspan=1 align=\"right\">";
	echo "<a href=\"player.php?mode=allratings\">(see all)</a></td></tr>\n";
	echo "<tr><td>Composite</td><td align=\"right\">$composite</td></tr>\n";
	echo "<tr><td>Standard</td><td align=\"right\">$standard</td></tr>\n";
	echo "<tr><td>Future Extended</td><td align=\"right\">$futex</td></tr>\n";
	echo "<tr><td>Classic</td><td align=\"right\">$classic</td></tr>\n";
	echo "<tr><td>Other Formats</td><td align=\"right\">$other</td></tr>\n";
	echo "</table>";
}

function ratingsTable() {
	echo "<table style=\"border-width: 0px;\" width=400 align=\"center\">\n";
	echo "<tr><td><b>Format</td>\n";
	echo "<td align=\"center\"><b>Rating</td>\n";
	echo "<td align=\"center\"><b>Record</td>\n";
	echo "<td align=\"center\"><b>Low</td>\n";
	echo "<td align=\"center\"><b>High</td></tr>\n";
	ratingLine("Composite");
	ratingLine("Standard");
	ratingLine("Future Extended");
	ratingLine("Classic");
	ratingLine("Other Formats");
	echo "</table>\n";
}

function ratingLine($format) {
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

function ratingsHistory($format) {
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

	echo "<table style=\"border-width: 0px;\" align=\"center\" width=600>\n";
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

function ratingHistoryForm($format) {
	$formats = array("Composite", "Standard", "Future Extended", "Classic",
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

function allMatchForm($player) {
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

function statsTable() {
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
	echo "<tr><td>Biggest Rival</td><td align=\"right\"> {$rivalname} ({$rivalrec})";
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
#Rival
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

function conditionalAllDecks() {
  global $player;
  $entries = $player->getNoDeckEntries();
  if (count($entries) > 0) { 
    $nodeckcount = count($entries);
		echo "<br><a href=\"player.php?mode=alldecks\" style=\"color: red;\">";
		echo "You have $nodeckcount unentered decks<br>";
		echo "Click here to enter them.</a>";
	}
}

?>
