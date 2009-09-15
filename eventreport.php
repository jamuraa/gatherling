<?php session_start();
include 'lib.php';

print_header("PDCMagic.com | Gatherling | Event Report");

?> 
<div class="grid_10 prefix_1 suffix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Event Report </div>

<?php
if (isset($_GET['event'])) { 
  $event = new Event($_GET['event']);
  showReport($event);
} else { 
  eventList(); 
} 

?> 

</div> 
</div> 

<?php print_footer(); ?>

<?php

function eventList($series = "", $season = "") {
  $db = Database::getConnection();
  $result = $db->query("SELECT e.name AS name, e.format AS format,
        COUNT(DISTINCT n.player) AS players, e.host AS host, e.start AS start,
        e.finalized, e.cohost, e.series, e.season
        FROM events e
        LEFT OUTER JOIN entries AS n ON n.event = e.name 
        WHERE 1=1 AND e.start < NOW() GROUP BY e.name ORDER BY e.start DESC");

  $onlyformat = false;  
  if(isset($_POST['format']) && strcmp($_POST['format'], "") != 0) {
    $onlyformat = $_POST['format'];
  }
  $onlyseries = false;
  if(isset($_POST['series']) && strcmp($_POST['series'], "") != 0) {
    $onlyseries = $_POST['series'];
  }
  $onlyseason = false;
  if(isset($_POST['season']) && strcmp($_POST['season'], "") != 0) {
    $onlyseason = $_POST['season'];
  }
  
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
  $count = 0;
  while($count < 100 && $thisEvent = $result->fetch_assoc()) {
    if (($onlyformat && strcmp($thisEvent['format'], $onlyformat) != 0) 
     || ($onlyseries && strcmp($thisEvent['series'], $onlyseries) != 0)
     || ($onlyseason && strcmp($thisEvent['season'], $onlyseason) != 0)) { 
       continue;
    } 
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
    $count = $count + 1;
  }

  $result->close();

  if ($count == 100) {
    echo "<tr><td colspan=\"5\" width=\"500\">&nbsp;</td></tr>";
    echo "<tr><td colspan=\"5\" align=\"center\">";
    echo "<i>This list only shows the 100 most recent results. ";
    echo "Please use the filters at the top of this page to find older ";
    echo "results.</i></td></tr>";
  }

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
	echo "<table style=\"border-width: 0px;\" width=350>\n";
  echo "<tr><td colspan=3 align=\"center\"><b>TOP $nfinalists</td></tr>\n";
  foreach ($event->getFinalists() as $finalist) { 
    $finaldeck = new Deck($finalist['deck']);
		$deckinfoarr = deckInfo($finaldeck);
		$redstar = "<font color=\"#FF0000\">*</font>";
		$append = " " . $finalist['medal'];
		if($finalist['medal'] == 't8' || $finalist['medal'] == 't4') {
			$append = " " . strtoupper($finalist['medal']);}
		$medalSTR = "<img src=\"/images/{$finalist['medal']}.gif\">";
		$medalSTR .= $append;
		$deckSTR = "<img src=\"/images/rename/{$deckinfoarr[1]}.gif\"> ";
		$deckSTR .= "<a href=\"deck.php?mode=view&id={$finalist['deck']}\">";
		$deckSTR .= "{$deckinfoarr[0]}</a>";
		if($deckinfoarr[2] < 60) {$deckSTR .= $redstar;}
		if($deckinfoarr[2] < 6)  {$deckSTR .= $redstar;}
		$playerSTR = "by <a href=\"profile.php?player={$finalist['player']}\">";
		$playerSTR .= "{$finalist['player']}</a>";
		echo "<tr><td>$medalSTR</td>\n<td>$deckSTR</td>\n";
		echo "<td align=\"right\">$playerSTR</td></tr>\n";
	}
	echo "</table>";
}

function metastats($event) {
	$archcnt = initArchetypeCount();
	$colorcnt = array("w" => 0, "g" => 0, "u" => 0, "r" => 0, "b" => 0);
  $decks = $event->getDecks(); 
  $ndecks = count($decks);
  foreach ($decks as $deck) { 
		$deckarr = deckInfo($deck);
		if($deckarr[1] != "blackout") {
			$archcnt[$deckarr[3]]++;
			for($ndx = 0; $ndx < strlen($deckarr); $ndx++) {
				$colorcnt[$deckarr[1]{$ndx}]++;
			}
		}
		else {$ndecks--;}
  }

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
  $decks = $event->getDecks();
  $players = array();
  foreach ($decks as $deck) { 
		$info = array("player" => $deck->playername, "deckname" => $deck->name,
			"archetype" => $deck->archetype, "medal" => $deck->medal,
      "id" => $deck->id);
		$arr = deckInfo($deck);
		$info["colors"] = $arr[1]; 
		if($info['medal'] == "dot") {$info['medal'] = "z";}
		$players[] = $info;
  }
  $db = Database::getConnection();
	$succ = $db->query("CREATE TEMPORARY TABLE meta(
		player VARCHAR(40), deckname VARCHAR(40), archetype VARCHAR(20),
		colors VARCHAR(10), medal VARCHAR(10), id BIGINT UNSIGNED,
    srtordr TINYINT UNSIGNED DEFAULT 0)");
  $succ or die($db->error); 
   
  $stmt = $db->prepare("INSERT INTO meta(player, deckname, archetype,	colors, medal, id)
    VALUES(?, ?, ?, ?, ?, ?)"); 
  for($ndx = 0; $ndx < sizeof($players); $ndx++) {
    $stmt->bind_param("sssssd", $players[$ndx]['player'], 
			$players[$ndx]['deckname'], $players[$ndx]['archetype'],
      $players[$ndx]['colors'], $players[$ndx]['medal'], $players[$ndx]['id']);
    $stmt->execute() or die($stmt->error);
  }
  $stmt->close();
  $result = $db->query("SELECT colors, COUNT(player) AS cnt FROM meta GROUP BY(colors)");
  $stmt = $db->prepare("UPDATE meta SET srtordr = ? WHERE colors = ?");
  while($row = $result->fetch_assoc()) {
    $stmt->bind_param("ds", $row['cnt'], $row['colors']); 
    $stmt->execute() or die($stmt->error); 
  } 
  $stmt->close(); 
  $result->close();
  $result = $db->query("SELECT player, deckname, archetype, colors, medal, id, srtordr
		FROM meta ORDER BY srtordr DESC, colors, medal, player");
	$color = "orange";
	echo "<table style=\"border-width: 0px;\" align=\"center\">";
	$hg = headerColor();
	echo "<tr style=\"background-color: $hg\">";
	echo "<td colspan=5 align=\"center\"><b>Metagame Breakdown</td></tr>\n";
	while($row = $result->fetch_assoc()) {
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
  $result->close();
	echo "</table>\n";
}

function deckInfo($deck) {
	$ret = array("No Deck Submitted", "blackout", 0, "Rogue");
  if(!is_null($deck)) {
    $colorstr = "";
    $row = $deck->getColorCounts();
		if($row['w'] > 0) {$colorstr .= 'w';}
		if($row['g'] > 0) {$colorstr .= 'g';}
		if($row['u'] > 0) {$colorstr .= 'u';}
		if($row['r'] > 0) {$colorstr .= 'r';}
    if($row['b'] > 0) {$colorstr .= 'b';}
    $row['cnt'] = array_sum($deck->maindeck_cards); 
		if($colorstr == "") {$colorstr = "blackout";}
		$ret = array($deck->name, $colorstr, $row['cnt'], $deck->archetype);
	}
	return $ret;
}

# Returns the number of finalists for an event based on the timing=2 subevent
function nfinalists($event) {
  foreach ($event->getSubevents() as $subevent) { 
    if ($subevent->timing == 2) { 
      return pow(2, $subevent->rounds); 
    } 
  } 
}

function initArchetypeCount() {
	$ret = array();
	$db = Database::getConnection();
	$result = $db->query("SELECT name FROM archetypes ORDER BY priority DESC");
	while($row = $result->fetch_assoc()) {
		$ret[$row['name']] = 0;
  }
  $result->close();
	return $ret;
}

function imageCell($event) {
	echo "<img width=150 height=150 src=\"displaySeries.php?series=$event->series\">";
}

function infoCell($event) {
	if(!is_null($event->threadurl)) {
    echo "<a href=\"{$event->threadurl}\">{$event->name}</a><br>\n";
  } else {
    echo "{$event->name}<br />\n";
  }
	$date = date('j F Y', strtotime($event->start));
	echo "$date<br />\n";
	echo "{$event->format}<br />\n";
  echo "{$event->getPlayerCount()} Players<br>\n";
  foreach ($event->getSubevents() as $subevent) { 
    if ($subevent->type != "Single Elimination") { 
			echo "{$subevent->rounds} rounds {$subevent->type}<br />\n";
		} else {
			$finalists = pow(2, $subevent->rounds);
			echo "Top $finalists playoff<br />\n";
		}
	}
	echo "Hosted by <a href=\"profile.php?player={$event->host}\">";
	echo "{$event->host}</a><br>\n";
	if (!is_null($event->reporturl)) {
    echo "<a href=\"{$event->reporturl}\">Event Report</a><br>\n";
  }
}

function trophyCell($event) {
  if ($event->hastrophy) { 
    echo "<img src=\"displayTrophy?event={$event->name}\"><br />\n";
  } else { 
    echo "No trophy available yet! <br />\n";
  } 
  $deck = $event->getPlaceDeck('1st');
	echo "<a href=\"profile.php?player={$deck->playername}\">";
	echo "{$deck->playername}</a>, ";
	$info = deckInfo($deck);
	echo "<img src=\"/images/rename/{$info[1]}.gif\"> ";
	echo "<a href=\"deck.php?mode=view&id={$deck->name}\">";
	echo "{$info[0]}</a><br>\n";
}
?>
