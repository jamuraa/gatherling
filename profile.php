<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Player Profile</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Profile</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>PLAYER PROFILE</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content();?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php #include 'gatherlingnav.php';?>
<?php include '../footer.ssi';?>

<?php
function content() {
	$player = "";
	if(isset($_SESSION['username'])) {$player = $_SESSION['username'];}
	if(isset($_GET['player'])) {$player = $_GET['player'];}
	if(isset($_POST['player'])) {$player = $_POST['player'];}
	if(chop($player) != "") {
		if(playerExists($player)) {profileTable($player);}
		else {
			echo "<center>\n";
			echo "$player could not be found in the database. Please check";
			echo " your spelling and try again.\n";
			echo "</center>\n";
		}
	}
	else {
		echo "<center>\n";
        echo "Please <a href=\"login.php\">log in</a> to automatically see";
        echo " your profile. You may also use the search below without";
        echo " logging in.\n";
        echo "</center>\n";
    }
	echo "<br><br>\n";
	searchForm($player);
}

function profileTable($player) {
	echo "<table style=\"border-width: 0px\" align=\"center\" width=600>\n";
    echo "<tr><td valign=\"top\">";
    infoTable($player);
    echo "</td><td align=\"right\" valign=\"top\">";
    medalTable($player);
    echo "</td></tr>";
    echo "<tr><td>&nbsp</td></tr>";
    echo "<tr><td valign=\"top\">";
    bestDecksTable($player);
    echo "</td><td align=\"right\">";
    trophyTable($player);
    echo "</td></tr>";
    echo "</table>";
}

function infoTable($player) {
	$db = dbcon();
	$query = "SELECT r.player, r.rating, r.updated, r.wins, r.losses
		FROM ratings AS r
		WHERE r.player=\"$player\" AND r.format=\"Composite\"
		ORDER BY r.updated DESC
		LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = array("rating" => 1600, "wins" => 0, "losses" => 0);
	if(mysql_num_rows($result) > 0) {$row = mysql_fetch_assoc($result);}
	mysql_free_result($result);
	
	$query = "SELECT count(*) AS cnt
		FROM events AS e
		WHERE host=\"$player\" or cohost=\"$player\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$tmprow = mysql_fetch_assoc($result);
	$hosted = $tmprow['cnt'];
	mysql_free_result($result);

	$query = "SELECT e.format, count(n.event) AS cnt
		FROM events AS e, entries AS n
		WHERE n.player=\"$player\" AND n.event=e.name
		GROUP BY e.format ORDER BY cnt DESC";
	$result = mysql_query($query, $db) or die(mysql_query());
	$ndx = 0; $sum = 0; $favF = "";
	while($tmprow = mysql_fetch_assoc($result)) {
		$sum += $tmprow['cnt'];
		if($ndx == 0) {
			$max = $tmprow['cnt'];
			$favF = $tmprow['format'];
		}
		$ndx++;
	}
	$pcgF = 0;
	if($sum > 0) {$pcgF = round(($max/$sum)*100);}
	mysql_free_result($result);

	$query = "SELECT MAX(UNIX_TIMESTAMP(e.start)) as d FROM entries AS n
		LEFT OUTER JOIN events AS e ON e.name= n.event
		WHERE n.player='$player'";
	$result = mysql_query($query) or die(mysql_error());
	$tmprow = mysql_fetch_assoc($result);
	$tmpArr = split(" ", $tmprow['d']);
	$lastActive = date("F j, Y", $tmpArr[0]);
	mysql_free_result($result);

	$query = "SELECT e.series, count(n.event) AS cnt
        FROM events AS e, entries AS n
        WHERE n.player=\"$player\" AND n.event=e.name
        GROUP BY e.series ORDER BY cnt DESC";
    $result = mysql_query($query, $db) or die(mysql_query());
    $ndx = 0; $sum = 0; $favS = "";
    while($tmprow = mysql_fetch_assoc($result)) {
        $sum += $tmprow['cnt'];
        if($ndx == 0) {
            $max = $tmprow['cnt'];
            $favS = $tmprow['series'];
        }
        $ndx++;
    }
	$pcgS = 0;
    if($sum > 0) {$pcgS = round(($max/$sum)*100);}
	mysql_free_result($result);

	$line1 = strtoupper($player);
	$matches = $row['wins'] + $row['losses'];

	echo "<table style=\"border-width: 0px;\" width=250>\n";
	echo "<tr><td align=\"left\" colspan=2 style=\"font-size: 10pt;\">";
	echo "<b>$line1</td></tr>\n";
	echo "<tr><td>Rating:</td>\n";
	echo "<td align=\"right\">{$row['rating']}</td></tr>\n";
	echo "<tr><td>Matches Played:</td>\n";
	echo "<td align=\"right\">$matches</td></tr>\n";
	echo "<tr><td>Record:</td>\n";
	echo "<td align=\"right\">{$row['wins']}&nbsp;-&nbsp;{$row['losses']}<td>";
	echo "</tr>\n";	
	if($hosted > 0) {
		echo "<tr><td>Events Hosted:</td>\n";
		echo "<td align=\"right\">$hosted</td></tr>\n";
	}
	echo "<tr><td>Favorite Format:</td>\n";
	echo "<td align=\"right\">$favF ($pcgF%)</td></tr>\n";
	echo "<tr><td>Favorite Series:</td>\n";
	echo "<td align=\"right\">$favS ($pcgS%)</td></tr>\n";
	echo "<tr><td>Last Active:</td>\n";
	echo "<td align=\"right\">$lastActive</td></tr>\n";
	echo "</table>";
	
	mysql_close($db);
}

function medalTable($player) {
	$db = dbcon();
	$query = "SELECT count(n.event) AS cnt, n.medal
		FROM entries AS n
		WHERE n.player=\"$player\" AND n.medal!=\"dot\"
		GROUP BY n.medal
		ORDER BY n.medal";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px\" width=260>\n";
	echo "<tr><td align=\"center\" colspan=4><b>MEDALS EARNED</td></tr>\n";
	if(mysql_num_rows($result) == 0) {
		echo "<tr><td align=\"center\" colspan=2>";
		echo "<i>$player has not earned any medals.</td></tr>\n";
	}
	else {
		$medals = array();
		while($row = mysql_fetch_assoc($result)) {
			$medals[$row['medal']] = $row['cnt'];
		}
		medalCell("1st", $medals['1st']);
		medalCell("2nd", $medals['2nd']);
		medalCell("t4", $medals['t4']);
		medalCell("t8", $medals['t8']);
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function trophyTable($player) {
	$db = dbcon();
	$query = "SELECT e.name
		FROM events AS e, trophies AS t, entries AS n
		WHERE n.player=\"$player\"
		AND n.medal=\"1st\" AND n.event=e.name AND t.event=e.name
		ORDER BY e.start DESC";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px;\" width=260>\n";
	echo "<tr><td align=\"center\"><b>TROPHIES EARNED</td></tr>\n";
	if(mysql_num_rows($result) == 0) {
		echo "<tr><td align=\"center\"><i>$player has not earned any trophies.</td></tr>\n";
	}
	else {
		while($row = mysql_fetch_assoc($result)) {
			$event = $row['name'];
			echo "<tr><td align=\"center\">";
			echo "<a href=\"deck.php?mode=view&event=$event\">";
			echo "<img style=\"border-width: 0px;\" ";
			echo "src=\"displayTrophy.php?event=$event\">";
			echo "</a></td></tr>";
		}
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function bestDecksTable($player) {
	$db = dbcon();
	$query = "SELECT d.name, count(n.player) AS cnt, max(d.id) AS id,
		sum(n.medal='t8') AS t8,
		sum(n.medal='t4') AS t4, 
		sum(n.medal='2nd') AS 2nd, 
		sum(n.medal='1st') AS 1st,
		sum(n.medal='t8')+2*sum(n.medal='t4')+
		4*sum(n.medal='2nd')+8*sum(n.medal='1st') AS score
		FROM decks AS d, entries AS n
		WHERE d.id=n.deck
		AND n.player=\"$player\"
		GROUP BY d.name
		ORDER BY score DESC, 1st DESC, 2nd DESC, t4 DESC, t8 DESC, cnt ASC";
	$result = mysql_query($query, $db);

	echo "<table style=\"border-width: 0px\" width=250>\n";
	echo "<tr><td align=\"left\" colspan=3><b>MEDAL WINNING DECKS</td></tr>\n";
	$printed = 0;
	while($row = mysql_fetch_assoc($result)) {
		if($row['score'] > 0) {
			$record = deckRecordString($row['name'], $player);
			if(chop($row['name']) == "") {$row['name'] = "* NO NAME *";}
			echo "<tr><td>";
			echo "<a href=\"deck.php?mode=view&id={$row['id']}\">";
			echo "{$row['name']}</a></td>\n";
			echo "<td align=\"center\" width=50>$record</td>";
			echo "<td align=\"right\">";
			for($i = 0; $i < $row['1st']; $i++) {inlineMedal('1st');}
			for($i = 0; $i < $row['2nd']; $i++) {inlineMedal('2nd');}
			for($i = 0; $i < $row['t4']; $i++) {inlineMedal('t4');}
			for($i = 0; $i < $row['t8']; $i++) {inlineMedal('t8');}
			echo "</td></tr>\n";
			$printed++;
		}
	}
	if($printed == 0) {
		echo "<tr><td colspan=3><i>$player has no medal winning decks.";
		echo "</td></tr>\n";
	}
	echo "</table>\n";
	mysql_free_result($result);
	mysql_close($db);
}

function medalCell($medal, $n) {
	if(is_null($n)) {$n = 0;}
	echo "<tr><td align=\"right\" width=130>";
	echo "<img src=\"/images/$medal.gif\">&nbsp;</td>\n";
	echo  "<td>$n</td></tr>\n";
}

function inlineMedal($medal) {
	echo "<img src=\"/images/$medal.gif\">&nbsp;";
}

function deckRecordString($deckname, $player) {
    $db = dbcon();
    $query = "SELECT m.playera, m.playerb, m.result, n.player
        FROM matches AS m, events AS e, subevents AS s, decks AS d, entries AS n
        WHERE e.name=n.event
        AND m.subevent=s.id
        AND s.parent=e.name
        AND n.deck=d.id
        AND d.name=\"$deckname\"
		AND n.player=\"$player\"
        AND (m.playera=n.player OR m.playerb=n.player)
        ORDER BY timing, round";
    $result = mysql_query($query, $db) or die(mysql_error());
    $w = 0; $l = 0;
    while($row = mysql_fetch_assoc($result)) {
        if($row['player'] == $row['playera'] && $row['result'] == "A" ||
           $row['player'] == $row['playerb'] && $row['result'] == "B") {
            $w++;}
        if($row['player'] == $row['playera'] && $row['result'] == "B" ||
           $row['player'] == $row['playerb'] && $row['result'] == "A") {
            $l++;}
    }
    $str = $w . "-" . $l;
    return $str;
}

function playerExists($player) {
	$ret = 0;
	$db = dbcon();
	$query = "SELECT name FROM players WHERE name=\"$player\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {$ret = 1;}
	mysql_free_result($result);
	mysql_close($db);
	return $ret;
}

function searchForm($player) {
	echo "<form action=\"profile.php\" mode=\"post\">\n";
	echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
	echo "<tr><td colspan=2 align=\"center\"><b>Player Lookup</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td><input type=\"text\" name=\"player\" value=\"$player\">";
	echo "</td><td><input type=\"submit\" name=\"mode\"";
	echo " value=\"Lookup Profile\"></td></tr></table>\n";
	echo "</form>\n";
}
?>
