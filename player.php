<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Player Control Panel</title>
<?php include '../header2.ssi';?>
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
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php #include 'gatherlingnav.php';?>
<?php include '../footer.ssi';?>

<?php
function content() {
	if(!isset($_SESSION['username'])) {
		echo "<center>You must <a href=\"login.php\">log in</a> to use your";
		echo " player control panel.</center>\n";
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'alldecks') {
		allContainer($_SESSION['username']);
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'allratings') {
		if(!isset($_GET['format'])) {$_GET['format'] = "Composite";}
		ratingsTable($_SESSION['username']);
		echo "<br><br>";
		ratingHistoryForm($_GET['format']);	
		echo "<br>";
		ratingsHistory($_SESSION['username'], $_GET['format']);
	}
	elseif(isset($_GET['mode']) && $_GET['mode'] == 'allmatches') {
		allMatchForm($_SESSION['username']);
		matchTable($_SESSION['username']);
	}
	elseif(isset($_POST['mode']) && $_POST['mode'] == 'Filter Matches') {
		allMatchForm($_SESSION['username']);
		matchTable($_SESSION['username']);
	}
	else {
		mainPlayerCP($_SESSION['username']);
		#recentDeckTable($_SESSION['username']);
		#statsTable($_SESSION['username']);
	}
}

function mainPlayerCP($player) {
	$upper = strtoupper($_SESSION['username']);
	echo "<table align=\"center\" width=600 style=\"border-width: 0px\">\n";
	echo "<tr><td><b>Welcome, $upper!";
	conditionalAllDecks($player);
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td width=300 valign=\"top\">";
	
	echo "<table align=\"center\" width=300 style=\"border-width: 0px\">\n";
	echo "<tr><td>"; recentDeckTable($player); echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td>"; ratingsTableSmall($player); echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td>"; recentMatchTable($player); echo "</td></tr>\n";
	echo "</table>\n";
	
	echo "</td>\n<td width=300 align=\"right\" valign=\"top\">";
	echo "<table style=\"border-width: 0px;\" align=\"right\" width=300>\n";
	echo "<tr><td align=\"right\">"; statsTable($player); echo "</td></tr>\n";
	echo "</table>\n";

	echo "</td></tr></table>\n";
}

function allContainer($player) {
	$rstar = "<font color=\"#FF0000\">*</font>";
	echo "<table style=\"border-width: 0px;\" width=600>\n";
	echo "<tr><td colspan=2>Decks marked with a $rstar have less than 60 ";
    echo "cards listed. Decks marked with $rstar$rstar have less than 6 cards ";
    echo "listed, and were created as placeholder decks.</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td valign=\"top\" width=275>";
	allDeckTable($player);
	echo "</td>\n<td valign=\"top\" width=275 align=\"right\">";
	noDeckTable($player);
	echo "</td></tr></table>";
}

function recentDeckTable($player) {
	$db = dbcon();
	$query = "SELECT n.medal, d.name, e.name AS event, d.id , e.threadurl
		FROM entries AS n, decks AS d, events AS e
		WHERE n.player=\"$player\" AND n.event=e.name AND d.id=n.deck
		ORDER BY e.start DESC
		LIMIT 5";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px;\" width=300>\n";
	echo "<tr><td colspan=3><b>RECENT DECKS</td>\n";
	echo "<td align=\"right\">";
	echo "<a href=\"player.php?mode=alldecks\">";
	echo "(see all)</a></td>\n";
	while($row = mysql_fetch_assoc($result)) {
		$cell1 = medalImgStr($row['medal']);
		$cell4 = recordString($row['id']);
		echo "<tr><td>$cell1</td>\n";
		echo "<td><a href=\"deck.php?mode=view&id={$row['id']}\">";
		echo "{$row['name']}</a></td>\n";
		echo "<td><a href=\"{$row['threadurl']}\">{$row['event']}</a></td>\n";
		echo "<td align=\"right\">$cell4</td></tr>\n";
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function noDeckTable($player) {
	$db = dbcon();
	$query = "SELECT n.medal, e.name, e.format, e.threadurl
		FROM entries AS n, events AS e
		WHERE n.player=\"$player\" AND n.deck IS NULL
		AND n.event=e.name
		ORDER BY e.start DESC";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px;\" width=275>\n";
	echo "<tr><td colspan=3 style=\"font-size: 14px; color: red;\">";
	echo "<b>UNENTERED DECKS</td></tr>\n";
	while($row = mysql_fetch_assoc($result)) {
		$imgcell = medalImgStr($row['medal']);
		echo "<td>$imgcell</td>\n";
		echo "<td align=\"left\"><a style=\"font-size: 11px; color: #D28950;\" href=\"deck.php?mode=create&event={$row['name']}&";
		echo "player=$player\">[Create Deck]</a></td>";
		echo "<td align=\"right\"><a href=\"{$row['threadurl']}\">{$row['name']}</a></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function allDeckTable($player) {
	$db = dbcon();
    $query = "SELECT n.medal, e.name, e.format, 
		d.name AS deck, d.id, e.threadurl, SUM(dc.qty) AS cnt
        FROM entries AS n, events AS e, decks AS d
		LEFT OUTER JOIN deckcontents AS dc ON dc.deck=d.id AND dc.issideboard=0
        WHERE n.player=\"$player\" AND n.deck IS NOT NULL
        AND n.event=e.name AND n.deck=d.id
		GROUP BY d.id
        ORDER BY e.start DESC";
    $result = mysql_query($query, $db) or die(mysql_error());
	$upPlayer = strtoupper($player);

	$rstar = "<font color=\"#FF0000\">*</font>";
    echo "<table style=\"border-width: 0px;\" width=275>\n";
    echo "<tr><td colspan=3><b>$upPlayer'S DECKS</td></tr>\n";
    while($row = mysql_fetch_assoc($result)) {
        $imgcell = medalImgStr($row['medal']);
        echo "<td width=20>$imgcell</td>\n";
        echo "<td><a href=\"deck.php?mode=view&id={$row['id']}\">";
        echo "{$row['deck']}</a>";
		if($row['cnt'] < 60) {print $rstar;}
		if($row['cnt'] < 6)  {print $rstar;}
		echo "</td>\n";
        echo "<td align=\"right\"><a href=\"{$row['threadurl']}\">{$row['name']}</a></td>\n";
        echo "</td></tr>\n";
    }
    echo "</table>\n";
    mysql_free_result($result);
    mysql_close($db);
}

function recentMatchTable($player) {
	$db = dbcon();
	$query = "SELECT m.playera, m.playerb, m.result
		FROM matches AS m, events AS e, subevents AS s
		WHERE (m.playera=\"$player\" OR m.playerb=\"$player\")
		AND m.subevent=s.id AND s.parent=e.name
		ORDER BY e.start DESC, s.timing DESC, m.round DESC
		LIMIT 6";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px\" width=300>\n";
	echo "<tr><td colspan=3><b>RECENT MATCHES</td><td align=\"right\">\n";
	echo "<a href=\"player.php?mode=allmatches\">(see all)</a></td></tr>\n";
	while($row = mysql_fetch_assoc($result)) {
		$res = "D"; $color = "#FF9900";
		if((strcasecmp($row['playera'], $player) == 0 && $row['result'] == 'A')
        ||(strcasecmp($row['playerb'], $player) == 0 && $row['result'] == 'B')){
            $res = "W";
            $color = "#009900";
        }
        if((strcasecmp($row['playera'], $player) == 0 && $row['result'] == 'B')
        ||(strcasecmp($row['playerb'], $player) == 0 && $row['result'] == 'A')){
            $res = "L";
            $color = "#FF0000";
        }
		$opp = $row['playera'];
		if(strcasecmp($player, $row['playera']) == 0) {$opp = $row['playerb'];}
		echo "<tr><td><b><font color=\"$color\">$res</font></b></td>\n";
		echo "<td>vs.</td>\n";
		echo "<td><a href=\"profile.php?player=$opp\">$opp</td></tr>\n";
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function matchTable($player, $limit=0) {
	$where = "";
	if(isset($_POST['format']) && $_POST['format'] != "%") {
		$where = $where . " AND e.format=\"{$_POST['format']}\" ";}
	if(isset($_POST['series']) && $_POST['series'] != "%") {
		$where = $where . " AND e.series=\"{$_POST['series']}\" ";}
	if(isset($_POST['season']) && $_POST['season'] != "%") {
		$where = $where . " AND e.season=\"{$_POST['season']}\" ";}
	if(isset($_POST['opp']) && $_POST['opp'] != "%") {
		$opp = $_POST['opp'];
		$where = $where . " AND (m.playera=\"$opp\" OR m.playerb=\"$opp\") ";
	}

	$db = dbcon();
	$query = "SELECT e.name, m.playera, m.playerb, m.result, m.round, s.timing,
		s.rounds, e.start, s.type
		FROM matches m, events e, subevents s 
		WHERE (m.playera=\"$player\" OR m.playerb=\"$player\") "
		. $where . 
		" AND m.subevent = s.id AND s.parent=e.name
		ORDER BY e.start DESC, s.timing DESC, m.round DESC";
	if($limit > 0) {$query = $query . " LIMIT $limit";}
	$result = mysql_query($query, $db) or die(mysql_error());
	mysql_close($db);
	
	$hc = headerColor();
	echo "<table style=\"border-width: 0px\" width=600>\n";
	echo "<tr style=\"background-color: $hc;\"><td><b>Event</td><td align=\"center\"><b>Round</td>";
	echo "<td><b>Opponent</td>\n";
	echo "<td><b>Deck</td>\n";
	echo "<td align=\"center\"><b>Rating</td>\n";
	echo "<td align=\"center\"><b>Result</td></tr>\n";
	$oldname = "";
	while($row = mysql_fetch_assoc($result)) {
		$rnd = $row['round'];
		if($row['timing'] == 2 && $row['type'] == "Single Elimination") {
			$rnd = "T" . pow(2, $row['rounds'] + 1 - $row['round']);}
		$opp = $row['playera'];
		if(strcasecmp($row['playera'], $player) == 0) {
			$opp = $row['playerb'];
		}
		$res = "Draw";
		$color = "#FF9900";
		if((strcasecmp($row['playera'], $player) == 0 && $row['result'] == 'A')
		||(strcasecmp($row['playerb'], $player) == 0 && $row['result'] == 'B')){
			$res = "Win";
			$color = "#009900";
		}
		if((strcasecmp($row['playera'], $player) == 0 && $row['result'] == 'B')
		||(strcasecmp($row['playerb'], $player) == 0 && $row['result'] == 'A')){
			$res = "Loss";
			$color = "#FF0000";
		}
		$oppRating = getRating($opp, "Composite", $row['start']);
		$deckArr = getDeckInfo($opp, $row['name']);
		$deckStr = "No Deck Found";
		if(!is_null($deckArr['id']) && $deckArr['id'] != "") {
			$deckStr = "<a href=\"deck.php?mode=view&id={$deckArr['id']}\">" .
				"{$deckArr['name']}</a>";
		}

		if($oldname != $row['name']) {
			$bg = rowColor();
			echo "<tr style=\"background-color: $bg\"><td>{$row['name']}</td>";
		}
		else {echo "<tr style=\"background-color: $bg;\"><td></td>\n";}
		$oldname = $row['name'];
		echo "<td align=\"center\">$rnd</td>\n";
		echo "<td><a href=\"profile.php?player=$opp\">$opp</a></td>\n";	
		echo "<td>$deckStr</td>\n";
		echo "<td align=\"center\">$oppRating</td>\n";
		echo "<td align=\"center\"><b><font color=\"$color\">$res</font>";
		echo "</td></tr>\n";
	}
	echo "</table>";
	mysql_free_result($result);
}

function getRating($player, $format="Composite", $date="3000-01-01 00:00:00") {
	$db = dbcon();
	$query = "SELECT rating FROM ratings
		WHERE player=\"$player\" AND updated<\"$date\" 
		AND format=\"$format\"
		ORDER BY updated DESC LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	$ret = 1600;
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$ret = $row['rating'];
	}
	mysql_free_result($result);
	mysql_close($db);
	return $ret;
}

function getRecord($player, $format="Composite", $date="3000-01-01 00:00:00") {
	$db = dbcon();
	$query = "SELECT wins, losses FROM ratings
		WHERE player=\"$player\" AND updated<\"$date\"
		AND format=\"$format\"
		ORDER BY updated DESC LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	$ret = "0-0";
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$ret = $row['wins'] . '-' . $row['losses'];
	}
	mysql_free_result($result);
	mysql_close($db);
	return $ret;
}

function getDeckInfo($player, $event) {
	$arr = array("id" => "", "name" => "");
	$db = dbcon();
	$query = "SELECT deck FROM entries WHERE player=\"$player\"
		AND event=\"$event\" AND deck IS NOT NULL";
	$result = mysql_query($query, $db) or die(mysql_error());	
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$arr['id'] = $row['deck'];
		mysql_free_result($result);
		$query = "SELECT name FROM decks WHERE id={$arr['id']}";
		$result = mysql_query($query, $db) or die(mysql_error());
		$row = mysql_fetch_assoc($result);
		$arr['name'] = $row['name'];
	}
	mysql_free_result($result);
	mysql_close($db);
	return $arr;
}	

function ratingsTableSmall($player) {
	$composite = getRating($player, "Composite");
	$standard = getRating($player, "Standard");
	$futex = getRating($player, "Future Extended");
	$classic = getRating($player, "Classic");
	$other = getRating($player, "Other Formats");

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

function ratingsTable($player) {
	echo "<table style=\"border-width: 0px;\" width=400 align=\"center\">\n";
	echo "<tr><td><b>Format</td>\n";
	echo "<td align=\"center\"><b>Rating</td>\n";
	echo "<td align=\"center\"><b>Record</td>\n";
	echo "<td align=\"center\"><b>Low</td>\n";
	echo "<td align=\"center\"><b>High</td></tr>\n";
	ratingLine($player, "Composite");
	ratingLine($player, "Standard");
	ratingLine($player, "Future Extended");
	ratingLine($player, "Classic");
	ratingLine($player, "Other Formats");
	echo "</table>\n";
}

function ratingline($player, $format) {
	$rating = getRating($player, $format);
	$record = getRecord($player, $format);
	$db = dbcon();
	$query = "SELECT MAX(rating) AS max, MIN(rating) AS min
		FROM ratings AS r
		WHERE r.player=\"$player\" AND r.format=\"$format\"
		AND r.wins+r.losses >= 20";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$min = $row['min'];
		$max = $row['max'];
	}
	mysql_free_result($result);
	mysql_close($db);
	
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

function ratingsHistory($player, $format) {
	$db = dbcon();
	$query = "SELECT e.name, r.rating, n.medal, n.deck AS id
		FROM events e, entries n, ratings r
		WHERE r.format=\"$format\" AND r.player=\"$player\"
		AND e.start=r.updated AND n.player=r.player AND n.event=e.name
		ORDER BY e.start DESC";
	$result = mysql_query($query, $db) or die(mysql_error());
	mysql_close($db);

	echo "<table style=\"border-width: 0px;\" align=\"center\" width=600>\n";
	echo "<tr><td align=\"center\"><b>Pre-Event</td>\n";
	echo "<td><b>Event</td>\n";
	echo "<td><b>Deck</td>\n";
	echo "<td align=\"center\"><b>Record</td>\n";
	echo "<td align=\"center\"><b>Medal</td>\n";
	echo "<td align=\"center\"><b>Post-Event</td></tr>\n";

	if(mysql_num_rows($result) > 0) {
		$prevrow = mysql_fetch_assoc($result);
		while($row = mysql_fetch_assoc($result)) {
			$deck = getDeckInfo($player, $prevrow['name']);
			$wl = playerRecord($player, $prevrow['name']);
			$img = medalImgStr($prevrow['medal']);

			echo "<tr><td align=\"center\">{$row['rating']}</td>\n";
			echo "<td>{$prevrow['name']}</td>\n";
			echo "<td><a href=\"deck.php?id={$deck['id']}&mode=view\">";
			echo "{$deck['name']}</a></td>\n";
			echo "<td align=\"center\">$wl</td>\n";
			echo "<td align=\"center\">$img</td>";
			echo "<td align=\"center\">{$prevrow['rating']}</td></tr>";
			$prevrow = $row;
		}
		$deck = getDeckInfo($player, $prevrow['name']);
        $wl = playerRecord($player, $prevrow['name']);
        $img = medalImgStr($prevrow['medal']);
		echo "<tr><td align=\"center\">1600</td>\n";
        echo "<td>{$prevrow['name']}</td>\n";
        echo "<td><a href=\"deck.php?id={$deck['id']}&mode=view\">";
        echo "{$deck['name']}</a></td>\n";
        echo "<td align=\"center\">$wl</td>\n";
        echo "<td align=\"center\">$img</td>";
        echo "<td align=\"center\">{$prevrow['rating']}</td></tr>";
	}
	else {
		echo "<tr><td colspan=6 align=\"center\"><i>";
		echo "You have not played any $format events.</td></tr>\n";
	}
	echo "</table>\n";
	mysql_free_result($result);
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
	$db = dbcon();
	$query = "SELECT e.format
		FROM entries n, events e, formats f
		WHERE n.player=\"$player\" AND e.name=n.event AND e.format=f.name
		GROUP BY e.format
		ORDER BY f.priority DESC, f.name";
	$result = mysql_query($query, $db) or die(mysql_error());
	
	echo "<select name=\"format\">\n";
	echo "<option value=\"%\">- Format -</option>\n";
	while($row = mysql_fetch_assoc($result)) {
		$thisformat = $row['format'];
		$sel = ($thisformat == $def) ? "selected" : "";
		echo "<option value=\"$thisformat\" $sel>$thisformat</option>\n";
	}
	echo "</select>\n";
	mysql_free_result($result);
    mysql_close($db);
}

function seriesDropMenuP($player, $def) {
	$db = dbcon();
    $query = "SELECT e.series
        FROM entries n, events e, series s
        WHERE n.player=\"$player\" AND e.name=n.event AND e.series=s.name
        GROUP BY e.series
        ORDER BY s.isactive DESC, s.name";
    $result = mysql_query($query, $db) or die(mysql_error());
    
    echo "<select name=\"series\">\n";
    echo "<option value=\"%\">- Series -</option>\n";
    while($row = mysql_fetch_assoc($result)) {
        $thisseries = $row['series'];
        $sel = ($thisseries == $def) ? "selected" : "";
        echo "<option value=\"$thisseries\" $sel>$thisseries</option>\n";
    }
    echo "</select>\n";
	mysql_free_result($result);
    mysql_close($db);
}

function seasonDropMenuP($player, $def) {
	$db = dbcon();
    $query = "SELECT e.season
        FROM entries n, events e
        WHERE n.player=\"$player\" AND e.name=n.event
        GROUP BY e.season
        ORDER BY e.season ASC";
    $result = mysql_query($query, $db) or die(mysql_error());
    
    echo "<select name=\"season\">\n";
    echo "<option value=\"%\">- Season -</option>\n";
    while($row = mysql_fetch_assoc($result)) {
        $thisseason = $row['season'];
        $sel = ($thisseason == $def) ? "selected" : "";
        echo "<option value=\"$thisseason\" $sel>$thisseason</option>\n";
    }
    echo "</select>\n";
	mysql_free_result($result);
    mysql_close($db);
}

function oppDropMenu($player, $def) {
	$db = dbcon();
	$query = "SELECT q.p AS opp, COUNT(q.p) AS cnt FROM
		(SELECT playera AS p FROM matches WHERE playerb=\"$player\"
		 UNION ALL
		 SELECT playerb AS p FROM matches WHERE playera=\"$player\") AS q
		GROUP BY opp
		ORDER BY cnt DESC";
	$result = mysql_query($query, $db) or die(mysql_error());
	
	echo "<select name=\"opp\">\n";
	echo "<option value=\"%\">- Opponent -</option>\n";
	while($row = mysql_fetch_assoc($result)) {
		$thisopp = $row['opp'];
		$cnt = $row['cnt'];
		$sel = ($thisopp == $def) ? "selected" : "";
		echo "<option value=\"$thisopp\" $sel>$thisopp [$cnt]</option>\n";
	}
	echo "</select>";
	mysql_free_result($result);
	mysql_close($db);
}

function statsTable($player) {
	echo "<table style=\"border-width: 0px\">";
	echo "<tr><td colspan=2><b>STATISTICS</td></tr>\n";
	echo "<tr><td>Record</td><td align=\"right\">"; statRecord($player); 
	echo "</td></tr>\n";
	echo "<tr><td>Longest Winning Streak</td><td align=\"right\">"; 
	statStreak($player, "W");
	echo "</td></tr>\n";
	echo "<tr><td>Longest Losing Streak</td><td align=\"right\">"; 
	statStreak($player, "L");
	echo "</td></tr>\n";
	echo "<tr><td>Biggest Rival</td><td align=\"right\">"; statRival($player);
	echo "</td></tr>";
	echo "<tr><td>Favorite Card</td><td align=\"right\">"; faveCard($player); 
	echo "</td></tr>\n";
	echo "<tr><td>Favorite Land</td><td align=\"right\">"; faveLand($player);
    echo "</td></tr>\n";
	echo "<tr><td>Medals Won</td><td align=\"right\">"; statMedals($player); 
	echo "</td></tr>\n";
	echo "<tr><td>Events Won</td><td align=\"right\">"; statWins($player); 
	echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\"><b>Most Recent Trophy</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\">"; statTrophy($player); 
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

function statRecord($player) {
	$db = dbcon();
	$query = "SELECT r.wins, r.losses
		FROM ratings AS r
		WHERE player=\"$player\" AND format=\"Composite\"
		ORDER BY r.updated DESC LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	$str = "0-0";
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$str = $row['wins'] . '-' . $row['losses'];
	}
	mysql_free_result($result);
	mysql_close($db);
	print $str;
}

function statStreak($player, $type="W") {
	$db = dbcon();
	$query = "SELECT m.playera, m.playerb, m.result
		FROM matches m, subevents s, events e
		WHERE (m.playera=\"$player\" OR m.playerb=\"$player\")
		AND m.subevent=s.id AND s.parent=e.name
		ORDER BY e.start ASC, s.timing ASC, m.round ASC";
	$result = mysql_query($query, $db) or die(mysql_error());
	$arr = array();
	while($row = mysql_fetch_assoc($result)) {
		$thisres = 'D';
		if(strcasecmp($player, $row['playera']) == 0 && $row['result'] == 'A'
		|| strcasecmp($player, $row['playerb']) == 0 && $row['result'] == 'B') {
			$thisres = 'W';}
		if(strcasecmp($player, $row['playera']) == 0 && $row['result'] == 'B'
		|| strcasecmp($player, $row['playerb']) == 0 && $row['result'] == 'A') {
			$thisres = 'L';}
		$arr[] = $thisres;
	}
	mysql_free_result($result);
	mysql_close($db);
	$max = 0;
	$streak = 0;
	for($ndx = 0; $ndx < sizeof($arr); $ndx++) {
		if($arr[$ndx] == $type) {$streak++;}
		else {$streak = 0;}
		if($streak > $max) {$max = $streak;}
	}
	print $max;
}

function faveCard($player) {
	$db = dbcon();
	$query = "SELECT c.name, sum(t.qty)AS qty
		FROM cards c, deckcontents t, entries n
		WHERE n.player=\"$player\" AND t.deck=n.deck AND t.issideboard=0
		AND t.card=c.id AND c.type NOT LIKE '%Land%'
		GROUP BY c.name
		ORDER BY qty DESC, c.name
		LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	$str = "none (0)";
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$str = $row['name'] . " ({$row['qty']})";
	}
	mysql_free_result($result);
	mysql_close($db);
	print $str;
}

function faveLand($player) {
    $db = dbcon();
    $query = "SELECT c.type, sum(t.qty)AS qty
        FROM cards c, deckcontents t, entries n
        WHERE n.player=\"$player\" AND t.deck=n.deck
        AND t.card=c.id AND c.type LIKE 'Basic%'
        GROUP BY c.name
        ORDER BY qty DESC, c.name";
    $result = mysql_query($query, $db) or die(mysql_error());
    $str = "none";
	$arr = array("Plains" => 0, "Forest" => 0, "Island" => 0, "Mountain" => 0,
		"Swamp" => 0);
	while($row = mysql_fetch_assoc($result)) {
		$tok = split(" - ", $row['type']);
		$arr[$tok[1]] += $row['qty'];
	}
	mysql_free_result($result);
	mysql_close($db);
	$max = 0;
	$land = "none";
	foreach($arr as $key => $qty) {
		if($qty > $max) {$max = $qty; $land = $key;}
	}
	$str = $land . " ($max)";
    print $str;
}

function statTrophy($player) {
	$db = dbcon();
	$query = "SELECT e.name
		FROM events AS e, entries AS n, trophies AS t
		WHERE n.event=e.name AND n.player=\"$player\"
		AND n.medal=\"1st\" AND t.event=e.name AND t.image IS NOT NULL
		ORDER BY e.start DESC LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		echo "<a href=\"deck.php?mode=view&event={$row['name']}\">\n";
		echo "<img style=\"border-width: 0px;\" src=\"displayTrophy.php?event={$row['name']}\">\n";
		echo "</a>\n";
	}
	else {
		echo "<i>No trophies earned</i>\n";
	}
	mysql_free_result($result);
	mysql_close($db);
}

function statMedals($player) {
	$db = dbcon();
	$query = "SELECT count(*) AS cnt FROM entries
		WHERE player=\"$player\" AND medal!=\"dot\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	print $row['cnt'];
	mysql_free_result($result);
	mysql_close($db);
}

function statWins($player) {
	$db = dbcon();
    $query = "SELECT count(*) AS cnt FROM entries
        WHERE player=\"$player\" AND medal=\"1st\"";
    $result = mysql_query($query, $db) or die(mysql_error());
    $row = mysql_fetch_assoc($result);
    print $row['cnt'];
    mysql_free_result($result);
    mysql_close($db);
}

function statRival($player) {
	$db = dbcon();
	$query = "SELECT q.p AS opp, count(q.p) n FROM
		(SELECT playera AS p FROM matches WHERE playerb=\"$player\"
		 UNION ALL
		 SELECT playerb AS p FROM matches WHERE playera=\"$player\") AS q
		GROUP BY q.p
		ORDER BY n DESC LIMIT 1;";
	$result = mysql_query($query, $db) or die(mysql_error());
	$rival = "none"; $record = "0-0";
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		$rival = $row['opp'];
	}
	mysql_free_result($result);
	$query = "SELECT m.playera, m.playerb, m.result
		FROM matches AS m
		WHERE (m.playera=\"$player\" AND m.playerb=\"$rival\")
		OR (m.playera=\"$rival\" AND m.playerb=\"$player\")";
	$result = mysql_query($query, $db) or die(mysql_error());
	$w = 0; $l = 0;
	while($row = mysql_fetch_assoc($result)) {
		$a = $row['playera']; $b = $row['playerb']; $res = $row['result'];
		if((strcasecmp($a, $player) == 0 && $res == 'A')
		|| (strcasecmp($b, $player) == 0 && $res == 'B')) {$w++;}	
		if((strcasecmp($a, $player) == 0 && $res == 'B')
        || (strcasecmp($b, $player) == 0 && $res == 'A')) {$l++;}
	}
	mysql_free_result($result);
	mysql_close($db);
	$record = $w . '-' . $l;
	$str = $rival . " (" . $record . ")";
	print $str;	
}

function conditionalAllDecks($player) {
	$db = dbcon();
	$query = "SELECT COUNT(*) FROM entries
		WHERE deck IS NULL AND player=\"$player\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_row($result);
	if($row[0] > 0) {
		echo "<br><a href=\"player.php?mode=alldecks\" style=\"color: red;\">";
		echo "You have {$row[0]} unentered decks<br>";
		echo "Click here to enter them.</a>";
	}
	mysql_free_result($result);
	mysql_close($db);
}

?>
