<?php require_once('../lib.php'); ?>

<?php
if(isset($_POST['mode'])) {
	$db = dbcon();
	$query = "SELECT SUM(dc.qty) AS q, d.id, d.name, n.player, n.event, n.medal 
		FROM decks d, entries n, deckcontents dc 
		WHERE d.name LIKE \"%{$_POST['deck']}%\" AND n.deck=d.id 
		AND dc.deck=d.id AND dc.issideboard=0
		GROUP BY dc.deck
		HAVING q>=60";
	$result = mysql_query($query, $db) or die(mysql_error());
	echo "<table>";
	while($row = mysql_fetch_assoc($result)) {
		echo "<tr><td><a href=\"../deck.php?mode=view&id={$row['id']}\">";
		echo "{$row['name']}</a></td><td> {$row['player']}</td>";
		echo "<td>{$row['event']}";
		echo "</td></tr>\n";
	}
	echo "</table>";
}
else {
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">";
	echo "OK, <b>Greg</b> here's your effin' deck search: <br>";
	echo "<input type=\"text\" name=\"deck\">";
	echo "<input type=\"submit\" name=\"mode\" value=\"Gimme some decks!\">";
	echo "</form>";
}

?>
