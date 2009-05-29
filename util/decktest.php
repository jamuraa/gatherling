<?php
require_once('../lib.php');

content();

function content() {
	$db = dbcon();

	$query = "CREATE TEMPORARY TABLE scores(
		id BIGINT UNSIGNED, type VARCHAR(40), score BIGINT)";
	mysql_query($query, $db) or die(mysql_error());

	$query = "SELECT DISTINCT decktype FROM typeinfo";
	$result = mysql_query($query, $db) or die(mysql_error());
	$decktypes = array();
	while($row = mysql_fetch_assoc($result)) {$decktypes[] = $row['decktype'];}
	
	$query = "SELECT dc.deck, d.name, SUM(dc.qty) AS n 
		FROM deckcontents dc, decks d
		WHERE dc.issideboard=0 AND d.id=dc.deck GROUP BY dc.deck
		HAVING n > 40";
	$result = mysql_query($query, $db) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)) {
		$maxscore = -32000; $type = "Unclassified";
		for($i = 0; $i < sizeof($decktypes); $i++) {
			$score = scoredeck($row['deck'], $decktypes[$i], $db);
			if($score > $maxscore) {$maxscore = $score;}
			if($score > 31) {$type = $decktypes[$i];}
		}
		$query = "INSERT INTO scores(id, type, score) 
			VALUES({$row['deck']}, \"$type\", $maxscore)";
		mysql_query($query, $db) or die(mysql_error());
	}

	$query = "SELECT s.id, s.type, s.score, d.name FROM scores s, decks d WHERE  d.id=s.id ORDER BY score DESC";
	$result = mysql_query($query, $db);
	echo "<table>";
	while($row = mysql_fetch_assoc($result)) {
		echo "<tr><td><a href=\"/gatherling/deck.php?mode=view&id={$row['id']}\">";
		echo "{$row['name']}</a></td>";
		echo "<td>{$row['type']} ({$row['score']})</td></tr>\n";
	}
	echo "</table>";
}

function scoredeck($id, $type, $db) {
	$score = 0;
	$convarr = array("Weak" => 1,   "Moderate" => 2, 
					 "Strong" => 4, "Required" => 4);
	$query = "SELECT ti.strength, dc.qty
		FROM typeinfo ti
		LEFT OUTER JOIN deckcontents AS dc 
		ON dc.card=ti.card AND dc.deck=$id AND dc.issideboard=0
		WHERE ti.decktype=\"$type\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)) {
		if($row['strength'] == 'Required' && $row['qty'] == 0) {$score -= 1000;}
		else {$score += $row['qty'] * $convarr[$row['strength']];}
	}
	mysql_free_result($result);
	return $score;
}
