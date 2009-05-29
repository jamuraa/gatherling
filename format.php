<?php include 'lib.php';?>
<?php session_start();?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Format Management</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Formats</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>FORMAT MANAGEMENT</h1></td></tr>
<tr><td bgcolor=white><br>

<?php 
if(isset($_SESSION['username'])) {
	if(hostCheck()) {content();}
	else {noHost();}
}
else {linkToLogin();}
?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php #include 'gatherlingnav.php';?>
<?php include '../footer.ssi';?>

<?
function content() {
	if(strcmp($_GET['mode'], "edit") == 0) {
		formatForm($_GET['name']);
	}
	elseif(strcmp($_POST['mode'], "Create New Format") == 0) {
		if(strcmp($_POST['insert'], "yes") == 0) {
			insertFormat();
			formatList();
		}
		else {formatForm();}
	}
	elseif(strcmp($_POST['mode'], "Save Changes") == 0) {
		updateFormat();
		formatForm($_POST['name']);
	}
	else {
		formatList();
	}
}

function formatList() {
	$db = dbcon();
	$query = "SELECT f.name AS name, COUNT(e.format) AS cnt
		FROM formats f
		LEFT OUTER JOIN events AS e ON e.format=f.name
		GROUP BY f.name
		ORDER BY cnt DESC, f.priority DESC, f.name";
	$result = mysql_query($query) or die(mysql_error());
	mysql_close($db);
	echo "<form action=\"format.php\" method=\"post\">";
	echo "<table style=\"border-width: 0px;\" align=\"center\">";
	$color = headerColor();
	echo "<tr bgcolor=$color><td><b>Format</td><td><b>No. Events</td></tr>";
	while($thisRow = mysql_fetch_assoc($result)) {
		$color = rowColor();
		echo "<tr bgcolor=$color><td>";
		echo "<a href=\"format.php?mode=edit&name={$thisRow['name']}\">";
		echo "{$thisRow['name']}</a></td>";
		echo "<td align=\"center\">{$thisRow['cnt']}</td></tr>";
	}
	echo "<tr><td>&nbsp</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<input type=\"submit\" name=\"mode\" value=\"Create New Format\">";
	echo "</td></tr></table></form>";
	mysql_free_result($result);
}
	

function formatForm($format = "") {
	$edit = (strcmp($format, "") == 0) ? 0 : 1;
	$vals = array("name" => $format);
	if($edit) {
		$db = dbcon();
		$query = "SELECT name, description FROM formats WHERE name=\"$format\"";
		$result = mysql_query($query, $db) or die(mysql_error());
		if(mysql_num_rows($result) == 0) {die(noFormat($format));}
		$vals = mysql_fetch_assoc($result);
		mysql_free_result($result);
		mysql_close($db);
	}
	echo "<form action=\"format.php\" method=\"post\">";
	echo "<table style=\"border-width: 0px;\" align=\"center\">";
	if($edit) {
		echo "<tr><td><b>Currently Editing</td><td align=\"center\">";
		echo "<i>$format</td></tr>";
		echo "<input type=\"hidden\" name=\"oldname\" value=\"$format\">";
		echo "<tr><td>&nbsp</td></tr>";
	}
	echo "<tr><td><b>Format Name</td>";
	echo "<td><input type=\"text\" name=\"name\" value=\"{$vals['name']}\" ";
	echo "size=\"40\">";
	echo "</td></tr>";
	echo "<tr><td valign=\"top\"><b>Description</td>";
	echo "<td><textarea name=\"desc\" rows=\"10\" cols=\"40\">";
	echo "{$vals['description']}</textarea></td></tr>";
	echo "<tr><td valign=\"top\"><b>Legal Sets</td><td>";
	dispSetSelect($format);
	echo "</td></tr><tr><td>&nbsp</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">";
	if($edit) {
		dispBanList($format);
	}
	else {
		echo "<i>Format must be created before cards can be banned or ";
		echo "restricted.</i>";
	}
	echo "</td></tr><tr><td>&nbsp</td></tr>";
	if($edit) {
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"Save Changes\">";
		echo "</td></tr>";
	}
	else {
		echo "<input type=\"hidden\" name=\"insert\" value=\"yes\">";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<input type=\"submit\" name=\"mode\" ";
		echo "value=\"Create New Format\"></td></tr>";
	}
	echo "</table></form>";
}

function dispSetSelect($format) {
	$db = dbcon();
	$query = "SELECT name FROM cardsets ORDER BY type, released";
	$result = mysql_query($query, $db) or die(mysql_error());
	$sets = array();
	for($ndx = 0; $ndx < mysql_num_rows($result); $ndx++) {
		$thisRow = mysql_fetch_assoc($result);
		$sets[$ndx] = $thisRow['name'];
	}
	mysql_free_result($result);	
	
	$query = "SELECT cardset FROM setlegality WHERE format=\"$format\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$selectedSets = array();
	for($ndx = 0; $ndx < mysql_num_rows($result); $ndx++) {
		$thisRow = mysql_fetch_assoc($result);
		$selectedSets[$thisRow['cardset']] = 1;
	}
	mysql_free_result($result);
	mysql_close($db);

	echo "<select name=\"sets[]\" multiple>";
	for($ndx = 0; $ndx < sizeof($sets); $ndx++) {
		$selStr = isset($selectedSets[$sets[$ndx]]) ? "selected" : "";
		echo "<option value=\"{$sets[$ndx]}\" $selStr>{$sets[$ndx]}</option>";
	}
	echo "</select>";
}

function dispBanList($format) {
	$db = dbcon();
	$query = "SELECT c.name AS card, b.allowed AS allowed  FROM cards c, bans b
		WHERE c.id = b.card AND b.format=\"$format\"
		ORDER by allowed DESC, card";
	$result = mysql_query($query, $db) or die(mysql_error());
	mysql_close($db);
	
	echo "<table style=\"border-width: 0px;\" align=\"center\">";
	echo "<tr><td colspan=\"3\" align=\"center\">";
	echo "<b>Banned/Restricted List</td></tr>";
	echo "<tr><td>&nbsp</td></tr>";
	if(mysql_num_rows($result) > 0) {
		echo "<tr><td><b>Card</td><td><b>Status</td>";
		echo "<td align=\"center\"><b>Remove</td></tr>";
	}
	else {
		echo "<tr><td align=\"center\" colspan=\"3\">";
		echo "<i>There are currently no banned or restricted cards ";
		echo "in this format. </td></tr>";
	}	
	while($thisRow = mysql_fetch_assoc($result)) {
		$color = ($thisRow['allowed'] == 0) ? "FF0000" : "FF8000";
		$status = ($thisRow['allowed'] == 0) ? "Banned" : "Restricted";
		echo "<tr><td>{$thisRow['card']}</td>";
		#echo "<td><font color=\"$color\">$status</font</td>";
		echo "<td style=\"color: #$color;\">$status</td>";
		echo "<td align=\"center\">";
		echo "<input type=\"checkbox\" name=\"unban[]\" ";
		echo "value=\"{$thisRow['card']}\"></td></tr>";
	}		
	echo "<tr><td colspan=\"3\">&nbsp</td></tr><tr><td colspan=\"3\" align=\"center\">";
	echo "<b>Add to list</td></tr>";
	echo "<tr><td colspan=\"2\">";
	echo "<input type=\"text\" name=\"newban\" value=\"\" size=\"40\">";
	echo "</td><td><input type=\"radio\" name=\"bantype\" value=\"1\">";
	echo "<font color=\"#FF8000\">Restrict</font><br>";
	echo "<input type=\"radio\" name=\"bantype\" value=\"0\">";
	echo "<font color=\"#FF0000\">Ban</font></td></tr>";
	echo "</table>";
	mysql_free_result($result);
}

function insertFormat() {
	$db = dbcon();
	$query = "INSERT INTO formats(name, description) VALUES
		(\"{$_POST['name']}\", \"{$_POST['desc']}\")";
	mysql_query($query) or die(mysql_error());
	for($ndx = 0; $ndx < sizeof($_POST['sets']); $ndx++) {
		$query = "INSERT INTO setlegality(format, cardset) VALUES
			(\"{$_POST['name']}\", \"{$_POST['sets'][$ndx]}\")";
		mysql_query($query) or die(mysql_error());
	}
	mysql_close($db);
}	

function updateFormat() {
	$db = dbcon();
	$query = "UPDATE formats SET name=\"{$_POST['name']}\",
		description=\"{$_POST['desc']}\" WHERE name=\"{$_POST['oldname']}\"";
	mysql_query($query) or die(mysql_error());
	$query = "DELETE FROM setlegality WHERE format=\"{$_POST['name']}\"";
	mysql_query($query) or die(mysql_error());
	for($ndx = 0; $ndx < sizeof($_POST['sets']); $ndx++) {
		$query = "INSERT INTO setlegality(format, cardset) VALUES
			(\"{$_POST['name']}\", \"{$_POST['sets'][$ndx]}\")";
		mysql_query($query) or die(mysql_error());
	}
	$newban = chop($_POST['newban']);
	if(strcmp($newban, "") != 0) {
		if(strcmp($_POST['bantype'], "") == 0) {
			die("You must select to either ban or restrict $newban. 
				Please go back and try again.");	
		}
		$query = "INSERT INTO bans(format, allowed, card)
			SELECT \"{$_POST['name']}\", {$_POST['bantype']}, id FROM cards 
			WHERE name=\"{$_POST['newban']}\" LIMIT 1";
		mysql_query($query) or die(mysql_error());
		if(mysql_affected_rows($db) == 0) {
			die("There is no card named $newban. Please go
				back, check your spelling, and try again.");
		}
	}
	for($ndx = 0; $ndx < sizeof($_POST['unban']); $ndx++) {
		$query = "DELETE FROM bans WHERE format=\"{$_POST['name']}\"
			AND card=(SELECT id FROM cards
			WHERE name=\"{$_POST['unban'][$ndx]}\" LIMIT 1)";
		mysql_query($query) or die(mysql_error());		
	}
	mysql_close($db);
}

function noFormat($format) {
	return "The requested format \"$format\" could not be found.";
}

?>
