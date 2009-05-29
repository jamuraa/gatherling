<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Event Report</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Event Reports</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>EVENT REPORT</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content();?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2008-01-18</td></tr></table></div>
<br><br></div></div>
<?php #include 'gatherlingnav.php';?>
<?php include '../footer.ssi';?>

<?php
function content() {
	if(isset($_GET['event'])) {
		showReport($_GET['event']);
	}
	else {
		eventList();
	}
}

function eventList($series = "", $season = "") {
    $db = dbcon();
    $query = "SELECT e.name AS name, e.format AS format,
        COUNT(DISTINCT n.player) AS players, e.host AS host, e.start AS start,
        e.finalized, e.cohost
        FROM events e
        LEFT OUTER JOIN entries AS n ON n.event = e.name 
        WHERE 1=1 AND e.start < NOW()";
    if(isset($_POST['format']) && strcmp($_POST['format'], "") != 0) {
        $query = $query . " AND e.format=\"{$_POST['format']}\" ";
    }
    if(isset($_POST['series']) && strcmp($_POST['series'], "") != 0) {
        $query = $query . " AND e.series=\"{$_POST['series']}\" ";
    }
    if(isset($_POST['season']) && strcmp($_POST['season'], "") != 0) {
        $query = $query . " AND e.season=\"{$_POST['season']}\" ";
    }
    $query = $query . " GROUP BY e.name ORDER BY e.start DESC LIMIT 100";
    $result = mysql_query($query, $db);
    mysql_close($db);

    echo "<form action=\"eventreport.php\" method=\"post\">";
    echo "<table style=\"border-width: 0px\" align=\"center\">";
    echo "<tr><td colspan=\"2\" align=\"center\"><b>Filters</td></tr>";
    echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td>Format</td><td>";
    formatDropMenu($_POST['format'], 1);
    echo "</td></tr>";
    echo "<tr><td>Series</td><td>";
    seriesDropMenu($_POST['series'], 1);
    echo "</td></tr>";
    echo "<tr><td>Season</td><td>";
    seasonDropMenu($_POST['season'], 1);
    echo "</td></tr>";
    echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td colspan=\"2\" align=\"center\">";
    echo "<input type=\"submit\" name=\"mode\" value=\"Filter Events\">";
    echo "</td></tr></table>";
    echo "<table style=\"border-width: 0px\" align=\"center\" cellpadding=\"3\">";
    echo "<tr><td colspan=\"5\">&nbsp;</td></tr>";
    echo "<tr><td><b>Event</td><td><b>Format</td>";
    echo "<td align=\"center\"><b>No. Players</td>";
    echo "<td><b>Host(s)</td></tr>";
    #echo "<td><b>Date</td>";
    #echo "<td align=\"center\"><b>Finalized</td></tr>";

	while($thisEvent = mysql_fetch_assoc($result)) {
        $dateStr = $thisEvent['start'];
        $dateArr = split(" ", $dateStr);
        $date = $dateArr[0];
        echo "<tr><td>";
        echo "<a href=\"eventreport.php?event={$thisEvent['name']}\">";
        echo "{$thisEvent['name']}</a></td>";
        echo "<td>{$thisEvent['format']}</td>";
        echo "<td align=\"center\">{$thisEvent['players']}</td>";
        echo "<td>{$thisEvent['host']}";
        $ch = $thisEvent['cohost'];
        if(!is_null($ch) && strcmp($ch, "") != 0) {echo "/$ch";}
        echo "</td>";
        #echo "<td>$date</td>";
        # echo "<td align=\"center\"><input type=\"checkbox\" ";
        # if($thisEvent['finalized'] == 1) {echo "checked";}
       	# echo "></td>";
        echo "</tr>";
    }

    if(mysql_num_rows($result) == 100) {
        echo "<tr><td colspan=\"5\" width=\"500\">&nbsp;</td></tr>";
        echo "<tr><td colspan=\"5\" align=\"center\">";
        echo "<i>This list only shows the 100 most recent results. ";
        echo "Please use the filters at the top of this page to find older ";
        echo "results.</i></td></tr>";
    }
    mysql_free_result($result);

    echo "<tr><td colspan=\"5\" width=\"500\">&nbsp;</td></tr>";
#   echo "<tr><td colspan=\"5\" align=\"center\">";
#   echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
#   echo "</td></tr>";
    echo "</table></form>";
}
function showReport($event) {
	echo "<table style=\"border-width: 0px;\" width=600>\n";
	echo "<tr><td valign=\"top\">";
    imageCell($event); 
	echo "</td><td valign=\"top\">";
    infoCell($event); 
	echo "</td><td align=\"center\" valign=\"top\" width=220>";
    trophyCell($event);
    echo "</td></tr></table>";
    echo "<table style=\"border-width: 0px;\" width=600>\n<tr><td>";
    finalists($event);
    echo "</td><td align=\"right\">";
    metastats($event);
    echo "</td></tr></table>\n";
    echo "<br><br>";
    fullmetagame($event);
}

function finalists($event) {
	$nfinalists = nfinalists($event);
	$db = dbcon();
	$query = "SELECT medal, deck, player FROM entries
		WHERE event=\"$event\" AND medal!=\"dot\"
		ORDER BY medal, player";
	$result = mysql_query($query, $db) or die(mysql_error());
	echo "<table style=\"border-width: 0px;\" width=350>\n";
	echo "<tr><td colspan=3 align=\"center\"><b>TOP $nfinalists</td></tr>\n";
	while($row = mysql_fetch_assoc($result)) {
		$deckinfoarr = deckInfo($row['deck']);
		$redstar = "<font color=\"#FF0000\">*</font>";
		$append = " " . $row['medal'];
		if($row['medal'] == 't8' || $row['medal'] == 't4') {
			$append = " " . strtoupper($row['medal']);}
		$medalSTR = "<img src=\"/images/{$row['medal']}.gif\">";
		$medalSTR .= $append;
		$deckSTR = "<img src=\"/images/rename/{$deckinfoarr[1]}.gif\"> ";
		$deckSTR .= "<a href=\"deck.php?mode=view&id={$row['deck']}\">";
		$deckSTR .= "{$deckinfoarr[0]}</a>";
		if($deckinfoarr[2] < 60) {$deckSTR .= $redstar;}
		if($deckinfoarr[2] < 6)  {$deckSTR .= $redstar;}
		$playerSTR = "by <a href=\"profile.php?player={$row['player']}\">";
		$playerSTR .= "{$row['player']}</a>";
		echo "<tr><td>$medalSTR</td>\n<td>$deckSTR</td>\n";
		echo "<td align=\"right\">$playerSTR</td></tr>\n";
	}
	echo "</table>";
}

function metastats($event) {
	$archcnt = initArchetypeCount();
	$colorcnt = array("w" => 0, "g" => 0, "u" => 0, "r" => 0, "b" => 0);
	$db = dbcon();
	$query = "SELECT deck FROM entries WHERE event=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$ndecks = mysql_num_rows($result);
	while($row = mysql_fetch_assoc($result)) {
		$deckarr = deckInfo($row['deck']);
		if($deckarr[1] != "blackout") {
			$archcnt[$deckarr[3]]++;
			for($ndx = 0; $ndx < strlen($deckarr); $ndx++) {
				$colorcnt[$deckarr[1]{$ndx}]++;
			}
		}
		else {$ndecks--;}
	}
	mysql_free_result($result);
	echo "<table style=\"border-width: 0px;\" width=200>\n";
	echo "<tr><td colspan=5 align=\"center\"><b>Metagame Stats</td></tr>\n";
	foreach($archcnt as $arch => $cnt) {
		if($cnt > 0) {
			$pcg = round(($cnt / $ndecks)*100);
			echo "<tr><td colspan=4 align=\"left\">$arch</td>";
			echo "<td align=\"right\">$pcg%</td></tr>\n";
		}
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td align=\"center\"><img src=\"/images/rename/w.gif\"</td>\n";
	echo "<td align=\"center\"><img src=\"/images/rename/g.gif\"></td>\n";
	echo "<td align=\"center\"><img src=\"/images/rename/u.gif\"></td>\n";
	echo "<td align=\"center\"><img src=\"/images/rename/r.gif\"></td>\n";
	echo "<td align=\"center\"><img src=\"/images/rename/b.gif\"></td></tr>\n";
	echo "<tr>";
	foreach($colorcnt as $col => $cnt) {
		if($col != "") {
			$pcg = round(($cnt / $ndecks)*100);
			echo "<td align=\"center\">$pcg%</td>\n";
		}
	}
	echo "</tr>\n";
	echo "</table>\n";
}

function fullmetagame($event) {
	$db = dbcon();
	$query = "SELECT n.player, d.name, d.archetype, n.deck, n.medal
		FROM entries n, decks d
		WHERE d.id=n.deck AND n.event=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$ndecks = mysql_num_rows($result);
	$players = array();
	while($row = mysql_fetch_assoc($result)) {
		$info = array("player" => $row['player'], "deckname" => $row['name'],
			"archetype" => $row['archetype'], "medal" => $row['medal'],
			"id" => $row['deck']);
		$arr = deckInfo($row['deck']);
		$info["colors"] = $arr[1]; 
		if($info['medal'] == "dot") {$info['medal'] = "z";}
		$players[] = $info;
	}
	$query = "CREATE TEMPORARY TABLE meta(
		player VARCHAR(40), deckname VARCHAR(40), archetype VARCHAR(20),
		colors VARCHAR(10), medal VARCHAR(10), id BIGINT UNSIGNED,
		srtordr TINYINT UNSIGNED DEFAULT 0)";
	$db = dbcon();
	mysql_query($query, $db) or die(mysql_error());
	for($ndx = 0; $ndx < sizeof($players); $ndx++) {
		$query = "INSERT INTO meta(player, deckname, archetype, 
			colors, medal, id)
			VALUES(\"{$players[$ndx]['player']}\", 
			\"{$players[$ndx]['deckname']}\", \"{$players[$ndx]['archetype']}\",
			\"{$players[$ndx]['colors']}\", \"{$players[$ndx]['medal']}\",
			{$players[$ndx]['id']})";
		mysql_query($query, $db) or die($query);
	}
	$query = "SELECT colors, COUNT(player) AS cnt FROM meta GROUP BY(colors)";
	$result = mysql_query($query, $db) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)) {
		$query = "UPDATE meta SET srtordr={$row['cnt']}
			WHERE colors=\"{$row['colors']}\"";
		mysql_query($query, $db) or die(mysql_error());
	}
	mysql_free_result($result);
	$query = "SELECT player, deckname, archetype, colors, medal, id, srtordr
		FROM meta
		ORDER BY srtordr DESC, colors, medal, player";
	$result = mysql_query($query, $db) or die(mysql_error());
	$color = "orange";
	echo "<table style=\"border-width: 0px;\" align=\"center\">";
	$hg = headerColor();
	echo "<tr style=\"background-color: $hg\">";
	echo "<td colspan=5 align=\"center\"><b>Metagame Breakdown</td></tr>\n";
	while($row = mysql_fetch_assoc($result)) {
		if($row['colors'] != $color) {
			$bg = rowColor();
			$color = $row['colors'];
			echo "<tr style=\"background-color: $bg;\"><td>";
			echo "<img src=\"/images/rename/$color.gif\">&nbsp;</td>\n";
			echo "<td colspan=4 align=\"left\"><i>{$row['srtordr']} Players ";
			#echo "<img src=\"/images/rename/$color.gif\">\n";
			echo "</td></tr>\n";
		}
		echo "<tr style=\"background-color: $bg;\"><td></td>\n";
		echo "<td align=\"left\">";
		if($row['medal'] != "z") {
			echo "<img src=\"/images/{$row['medal']}.gif\">&nbsp;";}
		echo "</td>\n<td align=\"left\">";
		echo "<a href=\"profile.php?player={$row['player']}\">";
		echo "{$row['player']}</a></td>\n";
		echo "<td align=\"left\">";
		echo"<a href=\"deck.php?mode=view&id={$row['id']}\">";
		echo "{$row['deckname']}</a></td>\n";
		echo "<td align=\"right\">{$row['archetype']}</td></tr>\n";
	}
	echo "</table>\n";
}

function deckInfo($deck) {
	$ret = array("No Deck Submitted", "blackout", 0, "Rogue");
	if(!is_null($deck)) {
		$db = dbcon();
		$query = "SELECT d.name, SUM(c.isw*dc.qty) AS w, SUM(c.isg*dc.qty) AS g,
			SUM(c.isu*dc.qty) AS u, SUM(c.isr*dc.qty) AS r,
			SUM(c.isb*dc.qty) AS b, SUM(dc.qty) AS cnt, d.archetype
			FROM decks AS d
			LEFT OUTER JOIN deckcontents AS dc ON dc.deck=d.id
			AND dc.issideboard != 1
			LEFT OUTER JOIN cards AS c ON c.id=dc.card
			WHERE d.id=$deck 
			GROUP BY d.id";
		$result = mysql_query($query, $db) or die(mysql_error());
		$row = mysql_fetch_assoc($result);
		mysql_free_result($result); mysql_close($db);
		$colorstr = "";
		if($row['w'] > 0) {$colorstr .= 'w';}
		if($row['g'] > 0) {$colorstr .= 'g';}
		if($row['u'] > 0) {$colorstr .= 'u';}
		if($row['r'] > 0) {$colorstr .= 'r';}
		if($row['b'] > 0) {$colorstr .= 'b';}
		if($colorstr == "") {$colorstr = "blackout";}
		$ret = array($row['name'], $colorstr, $row['cnt'], $row['archetype']);
	}
	return $ret;
}

# Returns the number of finalists for an event based on the timing=2 subevent
function nfinalists($event) {
	$db = dbcon();
	$query = "SELECT rounds FROM subevents
		WHERE parent=\"$event\" and timing=2";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_row($result);
	# Clean up mysql result and connection
	mysql_free_result($result); mysql_close($db);
	return pow(2, $row[0]);
}

function initArchetypeCount() {
	$ret = array();
	$db = dbcon();
	$query = "SELECT name FROM archetypes ORDER BY priority DESC";
	$result = mysql_query($query) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)) {
		$ret[$row['name']] = 0;
	}
	mysql_free_result($result); mysql_close($db);
	return $ret;
}

function imageCell($event) {
	$db = dbcon();
	$query = "SELECT series FROM events WHERE name=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result); mysql_close($db);
	$series = $row['series'];
	echo "<img width=150 height=150 src=\"displaySeries.php?series=$series\">";
}

function infoCell($event) {
	$db = dbcon();
	$query = "SELECT threadurl, UNIX_TIMESTAMP(start) AS dt, format, host, reporturl
		 FROM events WHERE name=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$query = "SELECT COUNT(*) FROM entries WHERE event=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$nrow = mysql_fetch_row($result);
	mysql_free_result($result);
	$query = "SELECT rounds, type FROM subevents WHERE parent=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(!is_null($row['threadurl'])) {
		echo "<a href=\"{$row['threadurl']}\">$event</a><br>\n";}
	else {echo "$event<br>\n";}
	$date = date('j F Y', $row['dt']);
	echo "$date<br>\n";
	echo "{$row['format']}<br>\n";
	echo "{$nrow[0]} Players<br>\n";
	while($srow = mysql_fetch_assoc($result)) {
		if($srow['type'] != "Single Elimination") {
			echo "{$srow['rounds']} rounds {$srow['type']}<br>\n";
		}
		else {
			$finalists = pow(2, $srow['rounds']);
			echo "Top $finalists playoff<br>\n";
		}
	}
	echo "Hosted by <a href=\"profile.php?player={$row['host']}\">";
	echo "{$row['host']}</a><br>\n";
	if(!is_null($row['reporturl'])) {
		echo "<a href=\"{$row['reporturl']}\">Event Report</a><br>\n";}
}

function trophyCell($event) {
	$db = dbcon();
	echo "<img src=\"displayTrophy?event=$event\"><br>\n";
	$query = "SELECT player, deck FROM entries 
		WHERE medal='1st' AND event=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	echo "<a href=\"profile.php?player={$row['player']}\">";
	echo "{$row['player']}</a>, ";
	$info = deckInfo($row['deck']);
	echo "<img src=\"/images/rename/{$info[1]}.gif\"> ";
	echo "<a href=\"deck.php?mode=view&id={$row['deck']}\">";
	echo "{$info[0]}</a><br>\n";
}
?>
