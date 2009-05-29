<html>

<?php
require_once('lib.php');
$db = dbcon();


if(isset($_GET['format'])) {
	$format = $_GET['format'];
	$preq = "SELECT cardset FROM setlegality WHERE format='$format'";
	$result = mysql_query($preq);
	$n = 0;
	$setfilter = "(";
	while($row = mysql_fetch_assoc($result)) {
		$thisset = $row['cardset'];
		if($n > 0) {$setfilter .= " OR ";}
		$setfilter .= "cardset='$thisset'";
		$n++;
	}
	$setfilter .= ")";
	mysql_free_result($result);
}
else {
	$setfilter = " cardset = '" . $_GET['set'] . "' ";
}

$query = "SELECT id , (isw + isg + isu + isr + isb) AS n, 
	isw, isg, isu, isb, isr
	FROM cards WHERE " . $setfilter . 
	" ORDER BY n , isw desc, isg desc, isu desc, isr desc, isb desc, name";
$result = mysql_query($query) or die(mysql_error());
?>

<body bgcolor=\"#404040\">

<?php
$n = 0;
$w = $g = $u = $r = $b = 0;
while($row = mysql_fetch_assoc($result)) {
	if( $row['isw'] != $w || 
	$row['isg'] != $g || 
	$row['isu'] != $u || 
	$row['isr'] != $r || 
	$row['isb'] != $b) {
		echo "<br><br>";
		$n = 0;
	}
	/*elseif($n == 6) {
		echo "<br>\n";
		$n = 0;
	}*/
	printf("<img src=\"/cards/%d.jpg\">\n", $row['id']);	
	$w = $row['isw'];
	$g = $row['isg'];
	$u = $row['isu'];
	$r = $row['isr'];
	$b = $row['isb'];
	$n++;
}
mysql_free_result($result);
mysql_close($db);
?>

</body>
</html>
