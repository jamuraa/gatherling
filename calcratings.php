<?php
include 'lib.php';

$db = dbcon();
$query = "DELETE FROM ratings";
mysql_query($query, $db) or die(mysql_error());
mysql_close($db);

$compQuery = "SELECT name, start FROM events ORDER BY start";
$futexQuery = "SELECT name, start FROM events WHERE format=\"Future Extended\" ORDER BY start";
$classicQuery = "SELECT name, start FROM events WHERE format=\"Classic\" ORDER BY start";
$stdQuery= "SELECT name, start FROM events WHERE format=\"Standard\" ORDER BY start";
$otherQuery= "SELECT name, start FROM events WHERE format!=\"Standard\" AND format!=\"Future Extended\" AND format!=\"Classic\" ORDER BY start";
$modernQuery = "SELECT name, start FROM events WHERE START >='2007-10-29' ORDER BY start";
$xpdcQuery= "SELECT name, start FROM events WHERE season=1 and series=\"XPDC\" ORDER BY start";

calcRating("Composite", $compQuery);
calcRating("Classic", $classicQuery);
calcRating("Future Extended", $futexQuery);
calcRating("Standard", $stdQuery);
calcRating("Other Formats", $otherQuery);
calcRating("Modern", $modernQuery);
calcRating("XPDC Season 1", $xpdcQuery);

#getEntryRatings("UPDC 0.12", "Composite");

function calcRating($format, $query) {
	$db = dbcon();
	$result = mysql_query($query, $db) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)) {
		$event = $row['name'];
		$players = calcPostEventRatings($event, $format);
		insertRatings($players, $format, $row['start']);
	}
	mysql_free_result($result);
}

function insertRatings($players, $format, $date) {
	$db = dbcon();
	foreach($players as $player=>$data) {
		$rating = $data['rating'];
		$wins = $data['wins']; $losses = $data['losses'];
		$query = "INSERT INTO ratings VALUES(\"$player\", $rating,
			\"$format\", \"$date\", $wins, $losses)";
		mysql_query($query) or die($query);;
#		echo "foo";
	}
	mysql_close($db);
}

function calcPostEventRatings($event, $format) {
	$players = getEntryRatings($event, $format);	
	$matches = getMatches($event);
	for($ndx = 0; $ndx < sizeof($matches); $ndx++) {
		$aPts = 0.5; $bPts = 0.5;
		if(strcmp($matches[$ndx]['result'], 'A') == 0) {
			$aPts = 1.0; $bPts = 0.0;
			$players[$matches[$ndx]['playera']]['wins']++;	
			$players[$matches[$ndx]['playerb']]['losses']++;	
		}
		elseif(strcmp($matches[$ndx]['result'], 'B') == 0) {
			$aPts = 0.0; $bPts = 1.0;
			$players[$matches[$ndx]['playerb']]['wins']++;	
			$players[$matches[$ndx]['playera']]['losses']++;	
		}
		$newA = newRating($players[$matches[$ndx]['playera']]['rating'],
			$players[$matches[$ndx]['playerb']]['rating'], 
			$aPts, $matches[$ndx]['kvalue']);
		$newB = newRating($players[$matches[$ndx]['playerb']]['rating'],
			$players[$matches[$ndx]['playera']]['rating'], 
			$bPts, $matches[$ndx]['kvalue']);

#		if(strcmp($format, "Other Formats") == 0) {
#		if(strcasecmp($matches[$ndx]['playera'], 'kingritz') == 0) {
#			printf("%s\n", $event);
#			printf("%d -> %d\n", $players[$matches[$ndx]['playera']]['rating'], $newA);}
#		if(strcasecmp($matches[$ndx]['playerb'], 'kingritz') == 0) {
#			printf("%s\n", $event);
#			printf("%d -> %d\n", $players[$matches[$ndx]['playerb']]['rating'], $newB);}
#		}

		$players[$matches[$ndx]['playera']]['rating'] = $newA;
		$players[$matches[$ndx]['playerb']]['rating'] = $newB;
	}
	return $players;
}

function newRating($old, $opp, $pts, $k) {
	$new = $old + ($k * ($pts - winProb($old, $opp)));
	if($old < $new) {$new = ceil($new);}
	elseif($old > $new) {$new = floor($new);}
	return $new;
}

function winProb($rating, $oppRating) {
	return 1/(pow(10, ($oppRating - $rating)/400) + 1);
}

function getMatches($event) {
	$db = dbcon();
	$query = "SELECT LCASE(m.playera) AS playera, LCASE(m.playerb) AS playerb, m.result, e.kvalue
		FROM matches AS m, subevents AS s, events AS e
		WHERE m.subevent=s.id AND s.parent=e.name AND e.name=\"$event\"
		ORDER BY s.timing, m.round";
	$result = mysql_query($query) or die(mysql_error());
	$data = array();
	while($row = mysql_fetch_assoc($result)) {$data[] = $row;}
	mysql_free_result($result);
	mysql_close($db);
	return $data;
}

function getEntryRatings($event, $format) {
	$db = dbcon();
	$query = "SELECT LCASE(n.player) AS player, r.rating, q.qmax, r.wins, r.losses
		FROM entries AS n 
		LEFT OUTER JOIN ratings AS r ON r.player=n.player
		LEFT OUTER JOIN 
		(SELECT qr.player AS qplayer, MAX(qr.updated) AS qmax
		 FROM ratings AS qr, events AS qe
		 WHERE qr.updated<qe.start AND qe.name=\"$event\"
		 AND qr.format=\"$format\"
		 GROUP BY qr.player) AS q
		ON q.qplayer=r.player
		WHERE n.event=\"$event\"
		AND ((q.qmax=r.updated AND q.qplayer=r.player AND r.format=\"$format\") 
		     OR q.qmax IS NULL)
		GROUP BY n.player
		ORDER BY n.player";
	#if($event=="Alt PDC 1.02" && $format=="Other Formats"){print $query;}
	$result = mysql_query($query) or die($query);
	$data = array();
	while($row = mysql_fetch_assoc($result)) {
		$datum = array();
		if(!is_null($row['qmax'])) {
			$datum['rating'] = $row['rating'];
			$datum['wins'] = $row['wins'];
			$datum['losses'] = $row['losses'];
		}
        else {
			$datum['rating'] = 1600;
			$datum['wins'] = 0;
			$datum['losses'] = 0;
		}
		$data[$row['player']] = $datum;
	}	
	mysql_free_result($result);
	mysql_close($db);
	return $data;
}
?>
