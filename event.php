<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Host Control Panel</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Events</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>Host Control Panel</h1></td>
</tr><tr><td bgcolor=white><br>

<?php 
if(isset($_SESSION['username'])) {content();}
else {linkToLogin();}
?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php include '../footer.ssi';?>

<?php 
function content() {
	if(isset($_GET['name'])) {
		eventForm($_GET['name']);
	}
	elseif(strcmp($_POST['mode'], "Parse DCI Files") == 0) {
		if(authCheck($_POST['name'])) {
			dciInput();
            eventForm($_POST['name']);
        }
        else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Auto-Input Event Data") == 0) {
		if(authCheck($_POST['name'])) {
			autoInput();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Update Registration") == 0) {
		if(authCheck($_POST['name'])) {
			updateReg();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}	
	elseif(strcmp($_POST['mode'], "Update Match Listing") == 0) {
		if(authCheck($_POST['name'])) {
			updateMatches();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Update Medals") == 0) {
		if(authCheck($_POST['name'])) {
			updateMedals();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Upload Trophy") == 0) {
		if(authCheck($_POST['name'])) {
			insertTrophy();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Update Event Info") == 0) {
		if(authCheck($_POST['name'])) {
			updateEvent();
			eventForm($_POST['name']);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Create New Event") == 0) {
		if(hostCheck()) {
			if(isset($_POST['insert'])) {
				insertEvent();
				eventList();
			}
			else {eventForm();}	
		}
		else {authFailed();}
	}
	elseif(strcmp($_GET['mode'], "create") == 0) {
		eventForm();
	}
	else {
		echo "<table style=\"border-width: 0px;\" align=\"center\">";
		echo "<tr><td>";
		echo "<form action=\"event.php\" method=\"post\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
		echo "</form></td><td>";
		echo "<form action=\"format.php\" method=\"post\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"View/Add Formats\">";
		echo "</form></td></tr>";
		echo "</table><br><br>";
		eventList($_POST['series'], $_POST['season']);
	}
}

function eventList($series = "", $season = "") {
	$db = dbcon();
	$query = "SELECT e.name AS name, e.format AS format,
		COUNT(DISTINCT n.player) AS players, e.host AS host, e.start AS start,
		e.finalized, e.cohost
		FROM events e
		LEFT OUTER JOIN entries AS n ON n.event = e.name 
		WHERE 1=1";
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

	echo "<form action=\"event.php\" method=\"post\">";
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
	echo "<td><b>Host(s)</td>";
	#echo "<td><b>Date</td>";
	echo "<td align=\"center\"><b>Finalized</td></tr>";
	
	while($thisEvent = mysql_fetch_assoc($result)) {
		$dateStr = $thisEvent['start'];
		$dateArr = split(" ", $dateStr);
		$date = $dateArr[0];
		echo "<tr><td>";
		echo "<a href=\"event.php?name={$thisEvent['name']}\">";
		echo "{$thisEvent['name']}</a></td>";
		echo "<td>{$thisEvent['format']}</td>";
		echo "<td align=\"center\">{$thisEvent['players']}</td>";
		echo "<td>{$thisEvent['host']}";
		$ch = $thisEvent['cohost'];
		if(!is_null($ch) && strcmp($ch, "") != 0) {echo "/$ch";}
		echo "</td>";
		#echo "<td>$date</td>";
		echo "<td align=\"center\"><input type=\"checkbox\" ";
		if($thisEvent['finalized'] == 1) {echo "checked";}
		echo "></td>";
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
#	echo "<tr><td colspan=\"5\" align=\"center\">";
#	echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
#	echo "</td></tr>";
	echo "</table></form>";
}

function eventForm($event = "") {
	$edit = (strcmp($event, "") == 0) ? 0 : 1;
	$vals = array("name" => $event);
	if($edit) {
		$db = dbcon();
		$query = "SELECT * FROM events WHERE name=\"$event\"";
		$result = mysql_query($query, $db) or die(mysql_error());
		if(mysql_num_rows($result) == 0) {die(noEvent($event));}
		$vals = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$query = "SELECT rounds, type, id FROM subevents
			WHERE parent=\"{$vals['name']}\" ORDER BY timing ASC";
		$result = mysql_query($query, $db) or die(mysql_error());
		$arr = mysql_fetch_assoc($result);
		$mainrounds = $arr['rounds'];
		$mainstruct = $arr['type'];
		$mainid = $arr['id'];
		$arr = mysql_fetch_assoc($result);
		$finalrounds = $arr['rounds'];
		$finalstruct = $arr['type'];
		$finalid = $arr['id'];
		mysql_close($db);
	}
	echo "<form action=\"event.php\" method=\"post\" ";
	echo "enctype=\"multipart/form-data\">";
	echo "<table style=\"border-width: 0px\" align=\"center\">";
	if($edit) {
		$date = $vals['start'];
		preg_match('/([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):.*/', $date, $datearr);
		$year = $datearr[1];
		$month = $datearr[2];
		$day = $datearr[3];
		$hour = $datearr[4];
		echo "<tr><td><b>Currently Editing</td>";
		echo "<td><i>{$vals['name']}</td>";
		echo "<input type=\"hidden\" name=\"name\" value=\"{$vals['name']}\">";
		echo "</tr><tr><td>&nbsp;</td></tr>";
	}
	else {
		echo "<tr><td valign=\"top\"><b>Event Name</td>";
		echo "<td><input type=\"radio\" name=\"naming\" value=\"auto\" checked>";
		echo "Automatically name this event based on Series, Season, and Number.";
		echo "<br><input type=\"radio\" name=\"naming\" value=\"custom\">";
		echo "Use a custom name: ";
		echo "<input type=\"text\" name=\"name\" value=\"{$vals['name']}\" ";
		echo "size=\"40\">";
		echo "</td></tr>";
	}
	echo "<tr><td><b>Date & Time</td><td>";
	numDropMenu("year", "- Year -", 2010, $year, 2005);
	monthDropMenu($month);
	numDropMenu("day", "- Day- ", 31, $day, 1);
	hourDropMenu($hour);
	echo "</td></tr>";
	echo "<tr><td><b>Series</td><td>";
	seriesDropMenu($vals['series']);
	echo "</td></tr>";
	echo "<tr><td><b>Season</td><td>";
	seasonDropMenu($vals['season']);
	echo "</td></tr>";
	echo "<tr><td><b>Number</td><td>";
	numDropMenu("number", "- Event Number -", 20, $vals['number'], 0, "Custom");
	echo "</td><tr>";
	echo "<tr><td><b>Format</td><td>";
	formatDropMenu($vals['format']);
	echo "</td></tr>";
	echo "<tr><td><b>K-Value:</td><td>";
	kValueDropMenu($vals['kvalue']);
	echo "</td></tr>";
	echo "<tr><td><b>Host/Cohost</td><td>";
	stringField("host", $vals['host'], 20);
	echo "&nbsp;/&nbsp;";
	stringField("cohost", $vals['cohost'], 20);
	echo "</td></tr>";
	echo "<tr><td><b>Event Thread URL</td><td>";
	stringField("threadurl", $vals['threadurl'], 60);
	echo "</td></tr>";
	echo "<tr><td><b>Metagame URL</td><td>";
	stringField("metaurl", $vals['metaurl'], 60);
	echo "</td></tr>";
	echo "<tr><td><b>Report URL</td><td>";
	stringField("reporturl", $vals['reporturl'], 60);
	echo "</td></tr>";
	echo "<tr><td><b>Main Event Structure</td><td>";
	numDropMenu("mainrounds", "- No. of Rounds -", 10, $mainrounds, 0);
	echo " rounds of ";
	structDropMenu("mainstruct", $mainstruct);
	echo "</td></tr>";
	echo "<tr><td><b>Finals Structure</td><td>";
	numDropMenu("finalrounds", "- No. of Rounds -", 10, $finalrounds, 0);
	echo " rounds of ";
	structDropMenu("finalstruct", $finalstruct);
	echo "</td></tr>";
	if($edit == 0) {
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
		echo "<input type=\"hidden\" name=\"insert\" value=\"1\">";
		echo "</td></tr>";
	}
	else {
		echo "<tr><td><b>Finalize Event</td>";
		echo "<td><input type=\"checkbox\" name=\"finalize\" value=\"1\" ";
		if($vals['finalized'] == 1) {echo "checked";}
		echo "></td></tr>";
		trophyField($vals['name']);	
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"Update Event Info\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"1\">";
		echo "</td></tr>";
		$view = "reg";
		$view = isset($_GET['view']) ? $_GET['view'] : $view;
		$view = isset($_POST['view']) ? $_POST['view'] : $view;
		echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
		controlPanel($vals['name'], $view);
		echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
		if(strcmp($view, "reg") == 0) {
			playerList($vals['name']);
		}
		elseif(strcmp($view, "match") == 0) {
			matchList($vals['name']);
		}
		elseif(strcmp($view, "medal") == 0) {
			medalList($vals['name']);
		}
		elseif(strcmp($view, "autoinput") == 0) {
			autoInputForm($vals['name']);
		}
		elseif(strcmp($view, "fileinput") == 0) {
			fileInputForm($vals['name']);
		}
	}
	echo "</table></form>";
}

function playerList($event) {
	$db = dbcon();
	$query = "SELECT e.deck, e.medal, e.player, d.name, SUM(dc.qty) AS cnt
		FROM entries e
		LEFT OUTER JOIN decks AS d ON d.id=e.deck
		LEFT OUTER JOIN deckcontents AS dc ON dc.deck=d.id
		WHERE event=\"$event\" 
		GROUP BY e.player
		ORDER BY medal, player";
	$result = mysql_query($query, $db) or die(mysql_error());
	
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	echo "<tr><td colspan=\"4\" align=\"center\">";
	echo "<b>Registered Players</td></tr>";
	echo "<tr><td>&nbsp;</td><tr>";
	echo "<input type=\"hidden\" name=\"view\" value=\"reg\">";
	if(mysql_num_rows($result) > 0) {
		echo "<tr><td><b>Player</td><td align=\"center\"><b>Medal</td>";
		echo "<td><b>Deck</td><td align=\"center\"><b>Delete</td></tr>";
	}
	else {
		echo "<tr><td align=\"center\" colspan=\"4\"><i>";
		echo "No players are currently registered for this event.</td></tr>";
	}
	while($thisPlayer = mysql_fetch_assoc($result)) {
		echo "<tr><td>{$thisPlayer['player']}</td>";
		if(strcmp("", $thisPlayer['medal']) != 0) {
			$img = "<img src=\"/images/{$thisPlayer['medal']}.gif\">";
		}
		echo "<td align=\"center\">$img</td>";
		$decklink = "<a style=\"color: #D28950\" href=\"deck.php?player={$thisPlayer['player']}&event=$event&mode=create\">Create Deck</a>";
		if($thisPlayer['deck'] > 0) {
			if($thisPlayer['name'] == "") {$thisPlayer['name'] = "* NO NAME *";}
			$decklink = "<a href=\"deck.php?id={$thisPlayer['deck']}&mode=view\">{$thisPlayer['name']}</a>";
		}
		$rstar = "<font color=\"#FF0000\">*</font>";
		if($thisPlayer['cnt'] < 60) {$decklink .= $rstar;}
		if($thisPlayer['cnt'] < 6) {$decklink .= $rstar;}
		echo "<td>$decklink</td>";
		echo "<td align=\"center\">";
		echo "<input type=\"checkbox\" name=\"delentries[]\" ";
		echo "value=\"{$thisPlayer['player']}\"></td></tr>";
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"4\" align=\"center\">";
	echo "<b>Add a Player</td></tr>";
	echo "<tr><td colspan=\"4\" align=\"center\">";
	stringField("newentry", "", 20);
	echo "</td></tr>";
	echo "<tr><td colspan=\"4\" width=\"400\">&nbsp;</td></tr>";
	echo "<tr><td align=\"center\" colspan=\"4\">";
	#medalDropMenu();
	#echo "</td><td>";
	echo "<input type=\"submit\" name=\"mode\" value=\"Update Registration\">";
	echo "</td></tr>";
	echo "</table>";
	echo "</td></tr>";
	mysql_free_result($result);
	mysql_close($db);
}

function matchList($event) {
	$db = dbcon();
	$query = "SELECT m.round, m.playera, m.playerb, m.result, s.timing, 
		s.rounds, m.id
		FROM matches m
		LEFT OUTER JOIN subevents AS s ON s.id=m.subevent
		WHERE s.parent=\"$event\"
		ORDER BY s.timing, m.round";
	$result = mysql_query($query, $db) or die(mysql_error());
	mysql_close($db);

	echo "<tr><td align=\"center\" colspan=\"2\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<b>Match List</td></tr>";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<i>* denotes a playoff/finals match.</td></tr>";
	echo "<input type=\"hidden\" name=\"view\" value=\"match\">";
	echo "<tr><td>&nbsp;</td></tr>";
	if(mysql_num_rows($result) > 0) {
		echo "<tr><td align=\"center\"><b>Round</td><td><b>Player A</td>";
		echo "<td><b>Player B</td>";
		echo "<td><b>Winner</td><td align=\"center\"><b>Delete</td></tr>";
	}
	else {
		echo "<tr><td align=\"center\" colspan=\"5\"><i>";
		echo "There are no matches listed for this event.</td></tr>";
	}
	$first = 1;
	$rndadd = 0;
	while($thisMatch = mysql_fetch_assoc($result)) {
		if($first && $thisMatch['timing'] == 1) {
			$rndadd = $thisMatch['rounds'];
		}
		$first = 0;
		$printrnd = ($thisMatch['timing'] == 2) ? 
			$thisMatch['round'] + $rndadd : $thisMatch['round'];
		$printplr = "Draw";
		if(strcmp($thisMatch['result'], "A") == 0) {
			$printplr = $thisMatch['playera'];
		}
		if(strcmp($thisMatch['result'], "B") == 0) {
			$printplr = $thisMatch['playerb'];
		}
		$star = ($printrnd > $rndadd) ? "*" : "";
		echo "<tr><td align=\"center\">$printrnd$star</td>";
		echo "<td>{$thisMatch['playera']}</td>";
		echo "<td>{$thisMatch['playerb']}</td><td>$printplr</td>";
		echo "<td align=\"center\">";
		echo "<input type=\"checkbox\" name=\"matchdelete[]\" ";		
		echo "value=\"{$thisMatch['id']}\"></td></tr>";
	}
	mysql_free_result($result);
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<b>Add a Match</b></td></tr>";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	roundDropMenu($event);
	playerDropMenu($event, "A");
	playerDropMenu($event, "B");
	resultDropMenu();
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<input type=\"submit\" name=\"mode\" ";
	echo "value=\"Update Match Listing\"></td></tr>";
	echo "</table>";
	echo "</td></tr>";
}

function medalList($event) {
	$def1 = "";
	$def2 = "";
	$def4 = array("", "");
	$def8 = array("", "", "", "");

	$db = dbcon();
	$query = "SELECT player FROM entries WHERE event=\"$event\"
		AND medal=\"1st\" LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {
		$tp = mysql_fetch_assoc($result);
		$def1 = $tp['player'];
	}
	mysql_free_result($result);
	$query = "SELECT player FROM entries WHERE event=\"$event\"
		AND medal=\"2nd\" LIMIT 1";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {
		$tp = mysql_fetch_assoc($result);
		$def2 = $tp['player'];
	}
	mysql_free_result($result);
	$query = "SELECT player FROM entries WHERE event=\"$event\"
		AND medal=\"t4\" LIMIT 2";
	$result = mysql_query($query, $db) or die(mysql_error());
	for($i = 0; $i < 2; $i++) {
		if(mysql_num_rows($result) > $i) {
			$tp = mysql_fetch_assoc($result);
			$def4[$i] = $tp['player'];	
		}
	}
	mysql_free_result($result);
	$query = "SELECT player FROM entries WHERE event=\"$event\"
		AND medal=\"t8\" LIMIT 4";
	$result = mysql_query($query, $db) or die(mysql_error());
	for($i = 0; $i < 4; $i++) {
		if(mysql_num_rows($result) > $i) {
			$tp = mysql_fetch_assoc($result);
			$def8[$i] = $tp['player'];	
		}
	}
	mysql_free_result($result);
	mysql_close($db);

	echo "<tr><td colspan=\"2\">";
	echo "<input type=\"hidden\" name=\"view\" value=\"reg\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	echo "<tr><td align=\"center\" colspan=\"2\"><b>Medals</td></tr>";
	echo "<tr><td colspan=\"2\" width=200>&nbsp;</td></tr>";
	echo "<tr><td align=\"center\"><b>Medal</td>";
	echo "<td align=\"center\"><b>Player</td></tr>";
	echo "<tr><td align=\"center\">";
	echo "<img src=\"/images/1st.gif\"></td>";
	echo "<td align=\"center\">";
	playerDropMenu($event, "1", $def1);
	echo "</td></tr>";
	echo "<tr><td align=\"center\">";
	echo "<img src=\"/images/2nd.gif\"></td>";
	echo "<td align=\"center\">";
	playerDropMenu($event, "2", $def2);
	echo "</td></tr>";
	for($i = 3; $i < 5; $i++) {
		echo "<tr><td align=\"center\">";
		echo "<img src=\"/images/t4.gif\"></td>";
		echo "<td align=\"center\">";
		playerDropMenu($event, "$i", $def4[$i-3]);
		echo "</td></tr>";
	}
	for($i = 5; $i < 9; $i++) {
		echo "<tr><td align=\"center\">";
		echo "<img src=\"/images/t8.gif\"></td>";
		echo "<td align=\"center\">";
		playerDropMenu($event, "$i", $def8[$i-5]);
		echo "</td></tr>";
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<input type=\"submit\" name=\"mode\" value=\"Update Medals\">";
	echo "</td></tr>";
	echo "</table>";
	echo "</td></tr>";
}

function kValueDropMenu($kvalue) {	
	if(strcmp($kvalue, "") == 0) {$kvalue = -1;}
	$names = array(8 => "Casual", 16 => "Regular (less than 24 players)", 
		24 => "Regular (24 or more players)", 32 => "Championship");
	echo "<select name=\"kvalue\">";
	echo "<option value=\"\">- K-Value -</option>";
	for($k = 8; $k <= 32; $k+=8) {
		$selStr = ($kvalue == $k) ? "selected" : "";
		echo "<option value=\"$k\" $selStr>{$names[$k]}</option>";
	}
	echo "</select>";
}

function stringField($field, $def, $len) {
	echo "<input type=\"text\" name=\"$field\" value=\"$def\" size=\"$len\">";
}

function hourDropMenu($hour) {
	if(strcmp($hour, "") == 0) {$hour = -1;}
	echo "<select name=\"hour\">";
	echo "<option value=\"\">- Hour -</option>";
	for($h = 0; $h < 24; $h++) {
		$selStr = ($hour == $h) ? "selected" : "";
		$hstring = $h . " AM";
		if($h == 0) {$hstring = "Midnight";}
		elseif($h == 12) {$hstring = "Noon";}
		elseif($h > 12) {$hstring = ($h - 12) . " PM";}
		echo "<option value=\"$h\" $selStr>$hstring</option>";
	}
	echo "</select>";
}

function monthDropMenu($month) {
	if(strcmp($month, "") == 0) {$month = -1;}
	$names = array("January", "February", "March", "April", "May", "June", 
		"July", "August", "September", "October", "November", "December");
	echo "<select name=\"month\">";
	echo "<option value=\"\">- Month -</option>";
	for($m = 1; $m <= 12; $m++) {
		$selStr = ($month == $m) ? "selected" : "";
		echo "<option value=\"$m\" $selStr>{$names[$m - 1]}</option>";
	}
	echo "</select>";
}	

function structDropMenu($field, $def) {
	$names = array("Swiss", "Single Elimination", "Round Robin", "League");
	echo "<select name=\"$field\">";
	echo "<option value=\"Swiss\">- Structure -</option>";
	for($i = 0; $i < sizeof($names); $i++) {
		$selStr = (strcmp($def, $names[$i]) == 0) ? "selected" : "";
		echo "<option value=\"{$names[$i]}\" $selStr>{$names[$i]}</option>";
	}
	echo "</select>";
}

function noEvent($event) {
	return "The requested event \"$event\" could not be found.";
}

function insertEvent() {
	$date = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
	if(strcmp($_POST['naming'], "auto") == 0) {
		$name = sprintf("%s %d.%02d",$_POST['series'], $_POST['season'], 
			$_POST['number']);
	}
	else $name = $_POST['name'];
	$db = dbcon();
	$query = "INSERT INTO events VALUES(\"$date\", \"{$_POST['format']}\", 
		\"{$_POST['host']}\", {$_POST['kvalue']}, \"{$_POST['metaurl']}\",
		\"$name\", {$_POST['number']}, \"{$_POST['season']}\",
		\"{$_POST['series']}\", \"{$_POST['threadurl']}\", 
		\"{$_POST['reporturl']}\", 0, \"{$_POST['cohost']}\")";
	mysql_query($query, $db) or die(mysql_error());
	if($_POST['mainrounds'] == "") {$_POST['mainrounds'] = 3;}
	if($_POST['mainstruct'] == "") {$_POST['mainstruct'] = "Swiss";}
	$query = "INSERT INTO subevents(parent, rounds, timing, type)
		 VALUES(\"$name\",
		{$_POST['mainrounds']}, 1, \"{$_POST['mainstruct']}\")";
	mysql_query($query, $db) or die(mysql_error());
	if($_POST['finalrounds'] == "") {$_POST['finalrounds'] = 3;}
	if($_POST['finalstruct'] == "") {$_POST['finalstruct'] = "Single Elimination";}
	$query = "INSERT INTO subevents(parent, rounds, timing, type)
		 VALUES(\"$name\",
		{$_POST['finalrounds']}, 2, \"{$_POST['finalstruct']}\")";
	mysql_query($query, $db) or die(mysql_error());
	if(strcmp($_POST['host'], $_SESSION['username']) != 0) {
		$query = "INSERT INTO stewards(event, player) VALUES(
			\"$name\", \"{$_SESSION['username']}\")";
		mysql_query($query, $db) or die(mysql_error());
	}
	mysql_close($db);
}

function updateEvent() {
    $date = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
	if($_POST['finalize'] != 1) {$_POST['finalize'] = 0;}
    $name = $_POST['name'];
    $db = dbcon();

	$query = "UPDATE events SET
		start=\"$date\",
		format=\"{$_POST['format']}\",
		host=\"{$_POST['host']}\",
		kvalue={$_POST['kvalue']},
		metaurl=\"{$_POST['metaurl']}\",
		number={$_POST['number']},
		season={$_POST['season']},
		series=\"{$_POST['series']}\",
		threadurl=\"{$_POST['threadurl']}\",
		reporturl=\"{$_POST['reporturl']}\",
		finalized={$_POST['finalize']},
		cohost=\"{$_POST['cohost']}\"
		WHERE name=\"$name\"";
    mysql_query($query, $db) or die(mysql_error());

	$query = "UPDATE subevents SET
		rounds={$_POST['mainrounds']},
		type=\"{$_POST['mainstruct']}\"
		WHERE timing=1 AND parent=\"$name\"";
    mysql_query($query, $db) or die(mysql_error());
	$query = "UPDATE subevents SET
		rounds={$_POST['finalrounds']},
		type=\"{$_POST['finalstruct']}\"
		WHERE timing=2 AND parent=\"$name\"";
    mysql_query($query, $db) or die(mysql_error());
}

function trophyField($event) {
	$db = dbcon();
	$query = "SELECT COUNT(*) AS cnt FROM trophies WHERE event=\"$event\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$arr = mysql_fetch_assoc($result);
	mysql_free_result($result);
	if($arr['cnt'] > 0) {
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<img src=\"displayTrophy.php?event=$event\"></td></tr>";
	}
	else {
		echo "<tr><td valign=\"top\"><b>Trophy Image</td><td>";
		echo "<input type=\"file\" id=\"trophy\" name=\"trophy\">&nbsp";
		echo "<input type=\"submit\" name=\"mode\" value=\"Upload Trophy\">";
		echo "</tr>";
	}
}

function insertTrophy() {
	if($_FILES['trophy']['size'] > 0) {
		$file = $_FILES['trophy'];
		$event = $_POST['name'];
		
		$name = $file['name'];
    	$tmp = $file['tmp_name'];
    	$size = $file['size'];
    	$type = $file['type'];

		$f = fopen($tmp, 'r');
    	$content = fread($f, filesize($tmp));
    	$content = addslashes($content);
    	fclose($f);

    	$query = "INSERT INTO trophies(event, size, type, image) VALUES(
        	'$event', $size, '$type', '$content')";
    	$db = dbcon();
    	mysql_query($query, $db) or die(mysql_error());
    	mysql_close($db);
	}
}

function medalDropMenu() {
	echo "<select name=\"medal\">";
	echo "<option value=\"dot\">- Medal -</option>";
	echo "<option value=\"dot\">No Medal</option>";
	echo "<option value=\"1st\">1st Place</option>";
	echo "<option value=\"2nd\">2nd Place</option>";
	echo "<option value=\"t4\">Top 4</option>";
	echo "<option value=\"t8\">Top 8</option>";
	echo "</select>";
}

function playerDropMenu($event, $letter, $def="\n") {
	$db = dbcon();
	$query = "SELECT player FROM entries WHERE event=\"$event\"
		ORDER BY player";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<select name=\"newmatchplayer$letter\">";
	if(strcmp("\n", $def) == 0) {
		echo "<option value=\"\">- Player $letter -</option>";
	}
	else {
		echo "<option value=\"\">- None -</option>";
	}
	while($thisPlayer = mysql_fetch_assoc($result)) {
		$selstr = (strcmp($thisPlayer['player'], $def) == 0) ? "selected" : "";
		echo "<option value=\"{$thisPlayer['player']}\" $selstr>";
		echo "{$thisPlayer['player']}</option>";
	}
	echo "</select>";
	mysql_free_result($result);
	mysql_close($db);
}

function roundDropMenu($event) {
	$db = dbcon();
	$query = "SELECT rounds, timing  FROM subevents WHERE parent=\"$event\"
		ORDER BY timing ASC";
	$result = mysql_query($query, $db) or die(mysql_error());
	$maxrnd = 0;
	while($thissub = mysql_fetch_assoc($result)){
		$maxrnd += $thissub['rounds'];
		if($thissub['timing'] == 1) {$main = $thissub['rounds'];}
	}
	mysql_free_result($result);
	mysql_close($db);
	
	echo "<select name=\"newmatchround\">";
	echo "<option value=\"\">- Round -</option>";
	for($r = 1; $r <= $maxrnd; $r++) {
		$star = ($r > $main) ? "*" : "";
		echo "<option value=\"$r\">$r$star</option>";
	}	
	echo "</select>";
}

function resultDropMenu() {
	echo "<select name=\"newmatchresult\">";
	echo "<option value=\"\">- Winner -</option>";
	echo "<option value=\"A\">Player A</option>";
	echo "<option value=\"B\">Player B</option>";
	echo "<option value=\"D\">Draw</option>";
	echo "</select>";
}

function controlPanel($event, $cur = "") {
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<a href=\"event.php?name=$event&view=reg\">Registration</a>";
	echo " | <a href=\"event.php?name=$event&view=match\">Match Listing</a>";
	echo " | <a href=\"event.php?name=$event&view=medal\">Medals</a>";
	echo " | <a href=\"event.php?name=$event&view=autoinput\">Auto-Input</a>";
	echo " | <a href=\"event.php?name=$event&view=fileinput\">DCI-R File Input";
	echo "</a></td></tr>";
}

function updateReg() {
	$event = $_POST['name'];
	$db = dbcon();
	for($ndx = 0; $ndx < sizeof($_POST['delentries']); $ndx++) {
		$query = "DELETE FROM entries WHERE event=\"$event\"
			AND player=\"{$_POST['delentries'][$ndx]}\"";
		mysql_query($query, $db) or die(mysql_error());
	}		

	$new = chop($_POST['newentry']);
	if(strcmp($new, "") != 0) {
		$query = "SELECT name FROM players WHERE name=\"$new\"";
		$result = mysql_query($query, $db) or die(mysql_error());
		if(mysql_num_rows($result) == 0) {
			$query = "INSERT INTO players(name) VALUES(\"$new\")";
			mysql_query($query, $db) or die(mysql_error());
		}
		mysql_free_result($result);
		$query = "INSERT INTO entries(event, player) VALUES(\"$event\", 
			\"$new\")";
		mysql_query($query) or die(mysql_error());
	}
	mysql_close($db);
}

function updateMatches() {
	$db = dbcon();
	for($ndx = 0; $ndx < sizeof($_POST['matchdelete']); $ndx++) {
		$query = "DELETE FROM matches WHERE id={$_POST['matchdelete'][$ndx]}";
		mysql_query($query, $db) or die(mysql_error());
	}

	$pA = $_POST['newmatchplayerA'];
	$pB = $_POST['newmatchplayerB'];
	$res = $_POST['newmatchresult'];
	$rnd = $_POST['newmatchround'];

	if(strcmp($pA, "") != 0 && strcmp("$pB", "") != 0
		&& strcmp($res, "") != 0 && strcmp($rnd, "") != 0) {
	
		$event = $_POST['name'];
		$query = "SELECT rounds, id FROM subevents 
			WHERE parent=\"$event\" ORDER BY timing ASC";
		$result = mysql_query($query) or die(mysql_error());
		if(mysql_num_rows($result) != 2) {
			die("Malformed Event Data in Database.");
		}
		$subarr = mysql_fetch_assoc($result);
		$mainrnds = $subarr['rounds'];
		$mainid = $subarr['id'];
		$subarr = mysql_fetch_assoc($result);
		$finalid = $subarr['id'];
		mysql_free_result($result);		 

		$id = $mainid;
		if($rnd > $mainrnds) {
			$rnd -= $mainrnds;
			$id = $finalid;
		}
		$query = "INSERT INTO matches(playera, playerb, round, subevent, result)
			VALUES(\"$pA\", \"$pB\", $rnd, $id, \"$res\")";
		mysql_query($query, $db) or die(mysql_error());
	}

	mysql_close($db);
}

function updateMedals() {
	$name = $_POST['name'];
	$db = dbcon();
	$query = "UPDATE entries SET medal=\"dot\" WHERE event=\"$name\"";
	mysql_query($query) or die(mysql_error());

	$query = "UPDATE entries SET medal=\"1st\" WHERE event=\"$name\"
		AND player=\"{$_POST['newmatchplayer1']}\"";
	mysql_query($query) or die(mysql_error());
	$query = "UPDATE entries SET medal=\"2nd\" WHERE event=\"$name\"
		AND player=\"{$_POST['newmatchplayer2']}\"";
	mysql_query($query) or die(mysql_error());

	for($i = 3; $i < 5; $i++) {
		$query = "UPDATE entries SET medal=\"t4\" WHERE event=\"$name\"
			AND player=\"{$_POST['newmatchplayer' . $i]}\"";
		mysql_query($query) or die(mysql_error());
	}		
	for($i = 5; $i < 9; $i++) {
		$query = "UPDATE entries SET medal=\"t8\" WHERE event=\"$name\"
			AND player=\"{$_POST['newmatchplayer' . $i]}\"";
		mysql_query($query) or die(mysql_error());
	}		
	mysql_close($db);
}

function autoInputForm($event) {
	$db = dbcon();
	$query = "SELECT rounds, type FROM subevents WHERE parent=\"$event\"
		ORDER BY timing ASC LIMIT 2";
	$result = mysql_query($query) or die(mysql_error());
	
	echo "<tr><td colspan=\"2 align=\"center\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	$totalrnds;
	while($thissub = mysql_fetch_assoc($result)) {
		if(strcmp($thissub['type'], "Single Elimination") == 0) {
			for($rnd = 1; $rnd <= $thissub['rounds']; $rnd++) {
				$rem = pow(2, $thissub['rounds'] - $rnd + 1);	
				echo "<tr><td colspan=\"2\" align=\"center\"><b>";
				echo "Round of $rem Pairings</td></tr>";
				echo "<tr><td colspan=\"2\" align=\"center\">";
				echo "<textarea name=\"finals[]\" rows=\"10\" cols=\"60\">";
				echo "</textarea></td></tr>";
				echo "<tr><td>&nbsp;</td></tr>";
			}
		}
		else {
			for($rnd = 1; $rnd <= $thissub['rounds']; $rnd++) {
				$printrnd = $rnd + $totalrnds;
				$pairfield = $printrnd . "p";
				$standfield = $printrnd . "s";
				echo "<tr><td colspan=\"2\" align=\"center\"><b>";
				echo "Round $printrnd Pairings</td></tr>";
				echo "<tr><td colspan=\"2\" align=\"center\">";
				echo "<textarea name=\"pairings[]\" rows=\"10\" cols=\"60\">";
				echo "</textarea></td></tr>";
				echo "<tr><td>&nbsp;</td></tr>";
				if($rnd > 1) {
					echo "<tr><td colspan=\"2\" align=\"center\"><b>";
					echo "Round $printrnd Standings</td></tr>";
					echo "<tr><td colspan=\"2\" align=\"center\">";
					echo "<textarea name=\"standings[]\" ";
					echo "rows=\"10\" cols=\"60\">";
					echo "</textarea></td></tr>";
					echo "<tr><td>&nbsp;</td></tr>";
				}
			}
			$totalrnds += $thissub['rounds'];
		}				
	}
	echo "<tr><td colspan=\"2\" align=\"center\"><b>";
	echo "Event Champion</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">";
	stringField("champion", "", 20);
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<input type=\"hidden\" name=\"view\" value=\"match\">";
	echo "<input type=\"submit\" name=\"mode\" ";
	echo "value=\"Auto-Input Event Data\">";
	echo "</td></tr>";
	echo "</table>";
	echo "</td></tr>";
}

function autoInput() {
	$db = dbcon();
	$pairings = array();
	$standings = array();
	for($rnd = 0; $rnd < sizeof($_POST['pairings']); $rnd++) {
		$pairings[$rnd] = extractPairings($_POST['pairings'][$rnd]);
		if($rnd == 0) {
			$standings[$rnd] = standFromPairs($_POST['pairings'][$rnd + 1]);
		}
		else {
			$testStr = chop($_POST['standings'][$rnd - 1]);
			if(strcmp($testStr, "") == 0) {
				$standings[$rnd] = standFromPairs($_POST['pairings'][$rnd + 1]);
			}
			else {
				$standings[$rnd] = extractStandings($_POST['standings'][$rnd - 1]);
			}
		}
	}
	$query = "SELECT id FROM subevents WHERE timing=1 
		AND parent=\"{$_POST['name']}\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$arr = mysql_fetch_assoc($result);
	$sid = $arr['id'];
	mysql_free_result($result);
	for($rnd = 0; $rnd < sizeof($pairings); $rnd++) {
		for($pair = 0; $pair < sizeof($pairings[$rnd]); $pair++) {
			$printrnd = $rnd + 1;
			$playerA = $pairings[$rnd][$pair][0];
			$playerB = $pairings[$rnd][$pair][1];
			$winner = "D";
			if($rnd == 0) {
				if(isset($standings[$rnd][$playerA]) && 
				$standings[$rnd][$playerA] > 1) {$winner = "A";}
				if(isset($standings[$rnd][$playerB]) &&
				$standings[$rnd][$playerB] > 1) {$winner = "B";}
			}
			else {
				if(isset($standings[$rnd][$playerA]) &&
				isset($standings[$rnd - 1][$playerA]) &&
				$standings[$rnd][$playerA] - $standings[$rnd - 1][$playerA]>1)
				{$winner = "A";}	
				if(isset($standings[$rnd][$playerB]) &&
				isset($standings[$rnd - 1][$playerB]) &&
				$standings[$rnd][$playerB] - $standings[$rnd - 1][$playerB]>1)
				{$winner = "B";}	
			}
			$query = "SELECT name FROM players WHERE name=\"$playerA\"";
			$result = mysql_query($query, $db) or die(mysql_error());
			if(mysql_num_rows($result) < 1) {
				$query = "INSERT INTO players(name) VALUES(\"$playerA\")";
				mysql_query($query, $db) or die(mysql_error());
			}
			mysql_free_result($result);
			$query = "SELECT name FROM players WHERE name=\"$playerB\"";
			$result = mysql_query($query, $db) or die(mysql_error());
			if(mysql_num_rows($result) < 1) {
				$query = "INSERT INTO players(name) VALUES(\"$playerB\")";
				mysql_query($query, $db) or die(mysql_error);
			}
			mysql_free_result($result);
			
			$query = "SELECT player FROM entries
				WHERE player=\"$playerA\" AND event=\"{$_POST['name']}\"";
			$result = mysql_query($query, $db) or die(mysql_error());
			if(mysql_num_rows($result) < 1) {
				$query = "INSERT INTO entries(player, event) 
				VALUES(\"$playerA\", \"{$_POST['name']}\")";
				mysql_query($query, $db) or die(mysql_error());
			}
			$query = "SELECT player FROM entries
				WHERE player=\"$playerB\" AND event=\"{$_POST['name']}\"";
			$result = mysql_query($query, $db) or die(mysql_error());
			if(mysql_num_rows($result) < 1) {
				$query = "INSERT INTO entries(player, event) 
				VALUES(\"$playerB\", \"{$_POST['name']}\")";
				mysql_query($query, $db) or die(mysql_error());
			}
			mysql_free_result($result);
			
			$query = "INSERT INTO 
				matches(subevent, playera, playerb, round, result)
				VALUES($sid, \"$playerA\", \"$playerB\", $rnd+1, \"$winner\")";
			mysql_query($query, $db) or die(mysql_error());
		}
	}
	$finals = array();
	for($ndx = 0; $ndx < sizeof($_POST['finals']); $ndx++) {
		$finals[$ndx] = extractFinals($_POST['finals'][$ndx]);
	}
	$query = "SELECT id FROM subevents WHERE timing=2 
		AND parent=\"{$_POST['name']}\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	$arr = mysql_fetch_assoc($result);
	$fid = $arr['id'];
	mysql_free_result($result);
	for($ndx = 0; $ndx < sizeof($finals); $ndx++) {
		for($match = 0; $match < sizeof($finals[$ndx]); $match+=2) {
			$playerA = $finals[$ndx][$match];
			$playerB = $finals[$ndx][$match + 1];
			checkPlayer($playerA, $_POST['name'], $db);
			checkPlayer($playerB, $_POST['name'], $db);
			if($ndx < sizeof($finals) - 1) {
				$winner = detwinner($playerA, $playerB, $finals[$ndx + 1]);
			}
			else {$winner = $_POST['champion'];}
			$res = "D";
			if(strcmp($winner, $playerA) == 0) {$res = "A";}
			if(strcmp($winner, $playerB) == 0) {$res = "B";}
			$query = "INSERT INTO 
				matches(subevent, playera, playerb, round, result)
				VALUES($fid, \"$playerA\", \"$playerB\", $ndx+1, \"$res\")";
			mysql_query($query, $db) or die(mysql_error());
			$loser = (strcmp($winner, $playerA) == 0) ? $playerB : $playerA;
			if($ndx == sizeof($finals) - 1) {
				$query = "UPDATE entries SET medal=\"1st\" WHERE
					player=\"$winner\" AND event=\"{$_POST['name']}\"";
				mysql_query($query, $db) or die(mysql_error());
				$query = "UPDATE entries SET medal=\"2nd\" WHERE
					player=\"$loser\" AND event=\"{$_POST['name']}\"";
				mysql_query($query, $db) or die(mysql_error());
			}
			elseif($ndx == sizeof($finals) - 2) {
				$query = "UPDATE entries SET medal=\"t4\" WHERE
					player=\"$loser\" AND event=\"{$_POST['name']}\"";
				mysql_query($query, $db) or die(mysql_error());
			}
			elseif($ndx == sizeof($finals) - 3) {
				$query = "UPDATE entries SET medal=\"t8\" WHERE
					player=\"$loser\" AND event=\"{$_POST['name']}\"";
				mysql_query($query, $db) or die(mysql_error());
			}
		}			
	}	
	mysql_close($db);
}

function checkPlayer($player, $event, $db) {
	$query = "SELECT player FROM entries 
		WHERE player=\"$player\" AND event=\"$event\"";
    $result = mysql_query($query, $db) or die(mysql_error());
    if(mysql_num_rows($result) < 1) {
        $query = "INSERT INTO entries(player, event) 
          	VALUES(\"$player\", \"$event\")";
        mysql_query($query, $db) or die(mysql_error());            
	}
    mysql_free_result($result);
}

function extractPairings($text) {
	$pairings = array();
	$lines = split("\n", $text);
	$loc = 0;
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		if(preg_match("/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+[0-9]+\s+([0-9a-z_.\- ]+),/i", 
			$lines[$ndx], $m)) {
			$pairings[$loc] = array($m[2], $m[3]);
			$loc++;
		}		
	}
	return $pairings;
}

function extractStandings($text) {
	$standings = array();
	$lines = split("\n", $text);
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		if(preg_match("/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+([0-9]+)\s+/i", 
		$lines[$ndx], $m)) {
			$standings[$m[2]] = $m[3];
		}
	}
	return $standings;
}

function standFromPairs($text) {
	$standings = array();
	$lines = split("\n", $text);
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		if(preg_match("/^\s*[0-9]+\s+([0-9]+\s+)?([0-9a-z_.\- ]+),.*\s+([0-9]+)-([0-9]+)\s+[0-9]+\s+([0-9a-z_.\- ]+),/i", $lines[$ndx], $m)) {
			$standings[$m[2]] = $m[3];
			$standings[$m[5]] = $m[4];
		}
	}
	return $standings;
}

function extractFinals($text) {
	$finals = array();
	$lines = split("\n", $text);
	$loc = 0;
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		if(preg_match("/[\t ]+([0-9a-z_.\- ]+),/i", $lines[$ndx], $m)) {
			$finals[$loc] = $m[1];
			$loc++;
		}
	}
	return $finals;
}

function detwinner($a, $b, $next) {
	$ret = "No Winner";
	for($ndx = 0; $ndx < sizeof($next); $ndx++) {
		if(strcmp($a, $next[$ndx]) == 0) {$ret = $a;}
		if(strcmp($b, $next[$ndx]) == 0) {$ret = $b;}
	}
	return $ret;
}

function authCheck($event) {
	$auth = 0;
	$db = dbcon();
	$query = "SELECT host, super FROM players 
		WHERE name=\"{$_SESSION['username']}\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		if($row['super'] == 1) {$auth = 1;}
		elseif($row['host'] == 1) {
			$query = "SELECT host FROM events WHERE name=\"$event\"
				AND (host=\"{$_SESSION['username']}\" 
					 OR cohost=\"{$_SESSION['username']}\")";
			$eResult = mysql_query($query, $db) or die(mysql_error());
			if(mysql_num_rows($eResult) > 0) {$auth = 1;}
			mysql_free_result($eResult);
		}
	}
	mysql_free_result($result);
	if(!$auth) {
		$query = "SELECT player FROM stewards WHERE event=\"$event\"
			AND player=\"{$_SESSION['username']}\"";
		print $query;
		$result = mysql_query($query, $db) or die(mysql_error());
		if(mysql_num_rows($result) > 0) {$auth = 1;}
		mysql_free_result($result);
	}
	mysql_close($db);
	return $auth;
}

function authFailed() {
	echo "You are not permitted to make that change. Please contact the ";
	echo "event host to modify this event. If you <b>are</b> the event host, ";
	echo "or feel that you should have privilege to modify this event, you ";
	echo "should contact WoCoNation via the forums.<br><br>";
}

function fileInputForm($event) {
	echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
	echo "<tr><td><b>*delt.dat</td><td>\n";
	echo "<input type=\"file\" name=\"delt\" id=\"delt\" size=40></td></tr>\n";
	echo "<tr><td><b>*kamp.dat&nbsp;</td><td>\n";
	echo "<input type=\"file\" name=\"kamp\" id=\"kamp\" size=40></td></tr>\n";
	echo "<tr><td><b>*elim.dat</td><td>\n";
	echo "<input type=\"file\" name=\"elim\" id=\"elim\" size=40></td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"Parse DCI Files\">\n";
	echo "</td></tr></table>\n";
}

function dciInput() {
	$reg = array();
	if($_FILES['delt']['size'] > 0) {
		$fileptr = fopen($_FILES['delt']['tmp_name'], 'r');
		$deltcontent = fread($fileptr, filesize($_FILES['delt']['tmp_name']));
		fclose($fileptr);
		$reg = dciregister($deltcontent);
	}
	if($_FILES['kamp']['size'] > 0 && sizeof($reg) > 0) {
		$fileptr = fopen($_FILES['kamp']['tmp_name'], 'r');
		$kampcontent = fread($fileptr, filesize($_FILES['kamp']['tmp_name']));
		fclose($fileptr);
		dciinputmatches($reg, $kampcontent);
	}
	if($_FILES['elim']['size'] > 0 && sizeof($reg) > 0) {
		$fileptr = fopen($_FILES['elim']['tmp_name'], 'r');
		$elimcontent = fread($fileptr, filesize($_FILES['elim']['tmp_name']));
		fclose($fileptr);
		dciinputplayoffs($reg, $elimcontent);
	}
}

function dciregister($data) {
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
	$ret = array();
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		$tokens = split(",", $lines[$ndx]);
		if(preg_match("/\"(.*)\"/", $tokens[3], $matches)) {
			dciRegPlayer($matches[1], $_POST['name']);
			$ret[] = $matches[1];
		}
	}
	return $ret;
}

function dciRegPlayer($player, $event) {
	$db = dbcon();
	$query = "SELECT name FROM players WHERE name=\"$player\"";
	$result = mysql_query($query, $db) or die(mysql_error());
	if(mysql_num_rows($result) == 0) {
		$query = "INSERT INTO players(name) VALUES(\"$player\")";
		mysql_query($query) or die(mysql_error());
	}
	mysql_free_result($result);
	$query = "INSERT INTO entries(event, player) 
		VALUES(\"$event\", \"$player\")";
	mysql_query($query); // or die(mysql_error());
	mysql_close($db);
}

function dciinputmatches($reg, $data) {
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
	$db = dbcon();
	for($table = 0; $table < sizeof($lines)/6; $table++) {
		$offset = $table * 6;
		$nos = split(",", $lines[$offset]);
		$pas = split(",", $lines[$offset + 1]);
		$pbs = split(",", $lines[$offset + 2]);
		$aws = split(",", $lines[$offset + 3]);
		$bws = split(",", $lines[$offset + 4]);
		for($rnd = 1; $rnd <= sizeof($nos); $rnd++) {
			if($nos[$rnd - 1] != 0) {
				$pa = $reg[$pas[$rnd - 1] - 1];
				$pb = $reg[$pbs[$rnd - 1] - 1];
				$res = 'D';
				if($aws[$rnd - 1] > $bws[$rnd - 1]) {$res = 'A';}
				if($bws[$rnd - 1] > $aws[$rnd - 1]) {$res = 'B';}
				$query = "INSERT INTO matches(round, playera, playerb,
					result, subevent) SELECT $rnd, \"$pa\", \"$pb\", \"$res\",
					id FROM subevents WHERE parent=\"{$_POST['name']}\"
					AND timing=1";
				mysql_query($query, $db) or die(mysql_error());
			}
		}
	}
	mysql_close($db);
}

function dciinputplayoffs($reg, $data) {
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
	$ntables = $lines[0];
	$nrounds = log($ntables, 2);
	#printf("# Rounds: %d<br>\n", $nrounds);
	$db = dbcon();
	for($rnd = 1; $rnd <= $nrounds; $rnd++) {
		$ngames = pow(2, $nrounds - $rnd);
		#printf("# Games: %d<br>\n", $ngames);
		for($game = 0; $game < $ngames; $game++) {
			$offset = 2 + $game*24;
			#printf("A: %d, B: %d, W: %d<br>", $offset+($rnd-1)*3, $offset+($rnd-1)*3+12, $offset+($rnd-1)*3+3);
			$playera = $lines[$offset + ($rnd-1)*3];
			$pbl = $offset + ($rnd-1)*3 + 12;
			$playerb = $lines[$pbl];
			$winner  = $lines[(($pbl+1)+ 3*$rnd - 6)/2 - 1];
			$pa = $reg[$playera - 1];
			$pb = $reg[$playerb - 1];
			$res = 'D';
			if($winner == $playera) {$res = 'A';}
			if($winner == $playerb) {$res = 'B';}			
			$query = "INSERT INTO matches(round, playera, playerb,
				result, subevent) SELECT $rnd, \"$pa\", \"$pb\", \"$res\",
				id FROM subevents WHERE parent=\"{$_POST['name']}\"
				AND timing=2";
			mysql_query($query, $db) or die(mysql_error());
		}
	}
	mysql_close($db);
}
?>
