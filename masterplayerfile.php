<?php
header("Content-type: text/plain");
require_once('lib.php');
$db = dbcon();
$query = "SELECT name FROM players ORDER BY name";
$result = mysql_query($query, $db);
$n = 10000001;
while($row = mysql_fetch_assoc($result)) {
	if(chop($row['name']) != "") {
		printf("%08d\tx\t%s\tUS\n", $n, $row['name']);
		$n++;
	}
}
mysql_free_result($result);
mysql_close($db);
?>
