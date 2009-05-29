<?php session_start();?>
<?php include 'lib.php'?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Ratings</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Ratings</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>Ratings</h1></td>
</tr><tr><td bgcolor=white><br>

<?php content();?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php include '../footer.ssi';?>

<?php

function content() {
	$format = "Composite";
	if(isset($_POST['format'])) {$format = $_POST['format'];}
	ratingsForm($format);
	$min = 20;
	if($format=="XPDC Season 1") {$min=10;}
	if($format=="Modern") {$min=10;}
	echo "<br><center>"; currentThrough($format); echo "</center><br>\n";
	echo "<center>"; bestEver($format); echo "</center><br>\n";
	ratingsTable($format, $min);
	echo "<br>";
}

function ratingsForm($format) {
	echo "<form action=\"ratings.php\" method=\"post\">\n";
	echo "<table align=\"center\" style=\"border-width: 0px\">";
	echo "<tr><td>Select a rating to display: ";
	formatDropMenuR($format);
	echo "&nbsp;";
	echo "<input type=\"submit\" name=\"mode\" value=\"Display Ratings\">";
	echo "</td></tr>\n";
	echo "</table></form>\n";
}

function formatDropMenuR($format) {
	$names = array("Composite", "Standard", "Extended", "Classic", "Other Formats");
	echo "<select name=\"format\">";
	for($ndx = 0; $ndx < sizeof($names); $ndx++) {
		$sel = (strcmp($names[$ndx], $format) == 0) ? "selected" : "";
		echo "<option value=\"{$names[$ndx]}\" $sel>{$names[$ndx]}</option>";
	}
	echo "</select>";
}

function ratingsTable($format, $min=20) {
	$db = dbcon();
	$query = "SELECT p.name AS player, r.rating, r.wins, r.losses
		FROM ratings AS r, players AS p,
		(SELECT qr.player AS qplayer, MAX(qr.updated) AS qmax
		 FROM ratings AS qr
		 WHERE qr.format=\"$format\"
		 GROUP BY qr.player) AS q
		WHERE r.format=\"$format\"
		AND p.name=r.player
		AND q.qplayer=r.player
		AND q.qmax=r.updated
		AND q.qmax > DATE_SUB(NOW(), INTERVAL 90 DAY)
		AND r.wins + r.losses >= $min
		ORDER BY r.rating DESC";
	$result = mysql_query($query) or die(mysql_error());
	$rank = 0;

	echo "<table align=\"center\" style=\"border-width: 0px;\" ";
	echo "width=\"500px\">\n";
	echo "<tr><td colspan=6 align=\"center\">";
	echo "<i>Only players with $min or more matches and active within the last 90 days are listed.";
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td align=\"center\"><b>Rank</td>";
	echo "<td><b>Player</td><td align=\"center\">";
	echo "<b>Rating</td>";
	echo "<td align=\"center\" colspan=\"3\"><b>Record</td></tr>\n";
	while($row = mysql_fetch_assoc($result)) {
		$rank++;
		echo "<tr><td align=\"center\">$rank</td><td>";
		echo "<a href=\"profile.php?player={$row['player']}\">";
		echo "{$row['player']}</a></td>\n";
		echo "<td align=\"center\">{$row['rating']}</td>\n";
		echo "<td align=\"right\" width=35>{$row['wins']}&nbsp;</td>\n";
		echo "<td align=\"center\">-</td><td width=35 align=\"left\">&nbsp;{$row['losses']}</td></tr>";
	}	
	echo "</table>";
	mysql_free_result($result);
	mysql_close($db);
}

function bestEver($format) {
	$db = dbcon();
	$query = "SELECT p.name AS player, r.rating, UNIX_TIMESTAMP(r.updated) AS t
		FROM ratings AS r, players AS p,
		(SELECT MAX(qr.rating) AS qmax
		 FROM ratings AS qr
		 WHERE qr.format=\"$format\") AS q
		WHERE format=\"$format\"
		AND p.name=r.player
		AND q.qmax=r.rating";
	$result = mysql_query($query) or die(mysql_error());

	$row = mysql_fetch_assoc($result);
	printf("The highest $format rating ever achieved is <b>%d</b>, obtained by <b>%s</b> on %s",
		$row['rating'], $row['player'], date("l, F j, Y", $row['t']));
	mysql_free_result($result);
	mysql_close($db);
}

function currentThrough($format) {
	$db = dbcon();
	$query = "SELECT MAX(updated) AS m FROM ratings WHERE format='$format'";
	$result = mysql_query($query, $db);
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$start = $row['m'];
	$query = "SELECT name FROM events WHERE start='$start'";
	$result = mysql_query($query, $db);
	$row = mysql_fetch_assoc($result);
	printf("<b>Ratings current through <span style=\"color: #440088\">%s</span></b>", $row['name']);
	mysql_free_result($result);
	mysql_close($db);
}

?>
