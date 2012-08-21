<?php
require_once('../lib.php');
echo "Pts do not include byes.<br /><br />";
$list = array();
$players = array();

$db = dbcon();

$pquery = "SELECT name , number FROM events WHERE series=\"XPDC\" and season=1 ORDER BY number";
$presult = mysql_query($pquery, $db) or die(mysql_error());
echo "<table><tr><td><b>Rank</td><td><b>Player</td>";
while($prow = mysql_fetch_assoc($presult)) {
	$ename = $prow['name'];
	echo "<td align=\"center\" width=30><b>1.{$prow['number']}</td>\n";	

$query = "SELECT m.playera, m.playerb, m.result, s.type FROM matches m, subevents s, events e
	WHERE e.name=\"$ename\" AND s.parent=e.name AND m.subevent=s.id";
$result = mysql_query($query, $db) or die(mysql_error());
$pts = array();
while($row = mysql_fetch_assoc($result)) {
	if($row['result'] == 'A') {
		$pts[$row['playera']] += 2;
		$players[$row['playera']] += 2;
	}
	if($row['result'] == 'B') {
		$pts[$row['playerb']] += 2;
		$players[$row['playerb']] += 2;
	}
	if($row['type'] == "Swiss") {	
		$pts[$row['playera']] += 1;
		$players[$row['playera']] += 1;
		$pts[$row['playerb']] += 1;
		$players[$row['playerb']] += 1;
	}
}
mysql_free_result($result);
$list[] = $pts;


}
echo "<td><b>Total</td></tr>";
mysql_free_result($presult);
mysql_close($db);

arsort($players);
$r = 1;
foreach($players as $pname => $p1) {
	echo "<tr><td>$r</td><td>$pname</td>";
	$sumtotal = 0;
	for($i = 0; $i < sizeof($list); $i++) {
		echo "<td align=\"center\">{$list[$i][$pname]}</td>";
		$sumtotal += $list[$i][$pname];
	}
	echo "<td>$sumtotal</td></tr>\n";
	$r++;
}
echo "</table>";

?>

