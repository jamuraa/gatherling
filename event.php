<?php session_start();
include 'lib.php';

print_header("PDCMagic.com | Gatherling | Host Control Panel");
?> 
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Host Control Panel </div>

<?php 
if(isset($_SESSION['username'])) {content();}
else {linkToLogin();}
?>

<div class="clear"></div> 
</div> </div> 

<?php print_footer(); ?>

<?php 
function content() {
  $event = NULL;
  if(isset($_GET['name'])) {
    $event = new Event($_GET['name']);
		eventForm($event);
	} elseif (strcmp($_POST['mode'], "Create New Event") == 0) {
		if(Player::getSessionPlayer()->isHost()) {
			if(isset($_POST['insert'])) {
				insertEvent();
				eventList();
      } else {
        eventForm();
      }	
    } else {
      authFailed();
    }
	} elseif (strcmp($_GET['mode'], "create") == 0) {
    eventForm();
  } elseif (strcmp($_POST['mode'], "Create Next Event") == 0) { 
    $oldevent = new Event($_POST['name']); 
    $newevent = new Event("");
    $newevent->season = $oldevent->season;
    $newevent->number = $oldevent->number + 1;
    $newevent->format = $oldevent->format;
    //$newevent->start = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
    $newevent->start = strftime("%Y-%m-%d %H:00:00", strtotime($oldevent->start) + (86400 * 7));
    $newevent->kvalue = $oldevent->kvalue;
    $newevent->finalized = 0;
    
    $newevent->series = $oldevent->series; 
    $newevent->host = $oldevent->host; 
    $newevent->cohost = $oldevent->cohost; 

    $newevent->mainrounds = $oldevent->mainrounds; 
    $newevent->mainstruct = $oldevent->mainstruct;
    $newevent->finalrounds = $oldevent->finalrounds; 
    $newevent->finalstruct = $oldevent->finalstruct; 

		$newevent->name = sprintf("%s %d.%02d",$newevent->series, $newevent->season, $newevent->number);

    eventForm($newevent, true); 
  } elseif (isset($_POST['name'])) { 
    $event = new Event($_POST['name']); 
    if (!$event->authCheck($_SESSION['username'])) { 
      authFailed(); 
    } else { 
      if (strcmp($_POST['mode'], "Parse DCI Files") == 0) {
        dciInput();
      } elseif (strcmp($_POST['mode'], "Auto-Input Event Data") == 0) {
        autoInput();
      } elseif(strcmp($_POST['mode'], "Update Registration") == 0) { 
        updateReg(); 
      } elseif(strcmp($_POST['mode'], "Update Match Listing") == 0) {
        updateMatches(); 
      } elseif(strcmp($_POST['mode'], "Update Medals") == 0) {
        updateMedals(); 
      } elseif(strcmp($_POST['mode'], "Upload Trophy") == 0) {
        if (insertTrophy()) {
          $event->hastrophy = 1;
        } 
      } elseif(strcmp($_POST['mode'], "Update Event Info") == 0) {
        $event = updateEvent(); 
      } 
      eventForm($event);
    }
	} else {
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
	$db = Database::getConnection(); 
	$query = "SELECT e.name AS name, e.format AS format,
		COUNT(DISTINCT n.player) AS players, e.host AS host, e.start AS start,
		e.finalized, e.cohost
		FROM events e
		LEFT OUTER JOIN entries AS n ON n.event = e.name 
		WHERE 1=1";
	if(isset($_POST['format']) && strcmp($_POST['format'], "") != 0) {
		$query = $query . " AND e.format=\"{$db->escape_string($_POST['format'])}\" ";
	}
	if(isset($_POST['series']) && strcmp($_POST['series'], "") != 0) {
		$query = $query . " AND e.series=\"{$db->escape_string($_POST['series'])}\" ";
	}
	if(isset($_POST['season']) && strcmp($_POST['season'], "") != 0) {
		$query = $query . " AND e.season=\"{$db->escape_string($_POST['season'])}\" ";
	}
	$query = $query . " GROUP BY e.name ORDER BY e.start DESC LIMIT 100";
	$result = $db->query($query);

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
	
	while($thisEvent = $result->fetch_assoc()) {
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

	if($result->num_rows == 100) {
		echo "<tr><td colspan=\"5\" width=\"500\">&nbsp;</td></tr>";
		echo "<tr><td colspan=\"5\" align=\"center\">";
		echo "<i>This list only shows the 100 most recent results. ";
		echo "Please use the filters at the top of this page to find older ";
		echo "results.</i></td></tr>";
	}
	$result->close(); 
	
	echo "<tr><td colspan=\"5\" width=\"500\">&nbsp;</td></tr>";
#	echo "<tr><td colspan=\"5\" align=\"center\">";
#	echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
#	echo "</td></tr>";
	echo "</table></form>";
}

function eventForm($event = NULL, $forcenew = false) {
  if ($forcenew) { 
    $edit = 0;
  } else { 
    $edit = ($event != NULL);
  } 
  if (is_null($event)) {
    $event = new Event("");
  }
	echo "<form action=\"event.php\" method=\"post\" ";
	echo "enctype=\"multipart/form-data\">";
	echo "<table style=\"border-width: 0px\" align=\"center\">";
	if ($event->start != NULL) {
		$date = $event->start;
		preg_match('/([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):.*/', $date, $datearr);
		$year = $datearr[1];
		$month = $datearr[2];
		$day = $datearr[3];
		$hour = $datearr[4];
		echo "<tr><td><b>Currently Editing</td>";
		echo "<td><i>{$event->name}</td>";
		echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
		echo "</tr><tr><td>&nbsp;</td></tr>";
	}
	else {
		echo "<tr><td valign=\"top\"><b>Event Name</td>";
		echo "<td><input type=\"radio\" name=\"naming\" value=\"auto\" checked>";
		echo "Automatically name this event based on Series, Season, and Number.";
		echo "<br><input type=\"radio\" name=\"naming\" value=\"custom\">";
		echo "Use a custom name: ";
		echo "<input type=\"text\" name=\"name\" value=\"{$event->name}\" ";
		echo "size=\"40\">";
    echo "</td></tr>";
    $year = strftime('Y', time());
	}
	echo "<tr><td><b>Date & Time</td><td>";
	numDropMenu("year", "- Year -", 2010, $year, 2005);
	monthDropMenu($month);
	numDropMenu("day", "- Day- ", 31, $day, 1);
	hourDropMenu($hour);
	echo "</td></tr>";
	echo "<tr><td><b>Series</td><td>";
	seriesDropMenu($event->series);
	echo "</td></tr>";
	echo "<tr><td><b>Season</td><td>";
	seasonDropMenu($event->season);
	echo "</td></tr>";
	echo "<tr><td><b>Number</td><td>";
	numDropMenu("number", "- Event Number -", 20, $event->number, 0, "Custom");
	echo "</td><tr>";
	echo "<tr><td><b>Format</td><td>";
	formatDropMenu($event->format);
	echo "</td></tr>";
	echo "<tr><td><b>K-Value:</td><td>";
	kValueDropMenu($event->kvalue);
	echo "</td></tr>";
	echo "<tr><td><b>Host/Cohost</td><td>";
	stringField("host", $event->host, 20);
	echo "&nbsp;/&nbsp;";
	stringField("cohost", $event->cohost, 20);
	echo "</td></tr>";
	echo "<tr><td><b>Event Thread URL</td><td>";
	stringField("threadurl", $event->threadurl, 60);
	echo "</td></tr>";
	echo "<tr><td><b>Metagame URL</td><td>";
	stringField("metaurl", $event->metaurl, 60);
	echo "</td></tr>";
	echo "<tr><td><b>Report URL</td><td>";
	stringField("reporturl", $event->reporturl, 60);
	echo "</td></tr>";
	echo "<tr><td><b>Main Event Structure</td><td>";
	numDropMenu("mainrounds", "- No. of Rounds -", 10, $event->mainrounds, 1);
	echo " rounds of ";
	structDropMenu("mainstruct", $event->mainstruct);
	echo "</td></tr>";
	echo "<tr><td><b>Finals Structure</td><td>";
	numDropMenu("finalrounds", "- No. of Rounds -", 10, $event->finalrounds, 0);
	echo " rounds of ";
	structDropMenu("finalstruct", $event->finalstruct);
	echo "</td></tr>";
	if($edit == 0) {
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
		echo "<input type=\"hidden\" name=\"insert\" value=\"1\">";
		echo "</td></tr>";
	} else {
		echo "<tr><td><b>Finalize Event</td>";
		echo "<td><input type=\"checkbox\" name=\"finalize\" value=\"1\" ";
		if($event->finalized == 1) {echo "checked";}
		echo "></td></tr>";
		trophyField($event);	
		echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td colspan=\"2\" align=\"center\">";
    echo " <input type=\"submit\" name=\"mode\" value=\"Update Event Info\" />";
    $nexteventname = sprintf("%s %d.%02d",$event->series, $event->season, $event->number + 1);
    if (!Event::exists($nexteventname)) { 
      echo " <input type=\"submit\" name=\"mode\" value=\"Create Next Event\" />";
    } 
		echo "<input type=\"hidden\" name=\"update\" value=\"1\">";
		echo "</td></tr>";
		$view = "reg";
		$view = isset($_GET['view']) ? $_GET['view'] : $view;
		$view = isset($_POST['view']) ? $_POST['view'] : $view;
		echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
		controlPanel($event, $view);
		echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
		if (strcmp($view, "reg") == 0) {
			playerList($event);
		} elseif (strcmp($view, "match") == 0) {
			matchList($event);
		} elseif (strcmp($view, "medal") == 0) {
			medalList($event);
		} elseif (strcmp($view, "autoinput") == 0) {
			autoInputForm($event);
		} elseif (strcmp($view, "fileinput") == 0) {
			fileInputForm($event);
		}
	}
	echo "</table></form>";
}

function playerList($event) {
  $entries = $event->getEntries();

	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	echo "<tr><td colspan=\"4\" align=\"center\">";
	echo "<b>Registered Players</td></tr>";
	echo "<tr><td>&nbsp;</td><tr>";
	echo "<input type=\"hidden\" name=\"view\" value=\"reg\">";
	if(count($entries) > 0) {
		echo "<tr><td><b>Player</td><td align=\"center\"><b>Medal</td>";
		echo "<td><b>Deck</td><td align=\"center\"><b>Delete</td></tr>";
	} else {
		echo "<tr><td align=\"center\" colspan=\"4\"><i>";
		echo "No players are currently registered for this event.</td></tr>";
  }
  foreach ($entries as $entry) { 
		echo "<tr><td>{$entry->player->name}</td>";
		if(strcmp("", $entry->medal) != 0) {
			$img = "<img src=\"/images/{$entry->medal}.gif\">";
		}
		echo "<td align=\"center\">$img</td>";
		$decklink = "<a style=\"color: #D28950\" href=\"deck.php?player={$entry->player->name}&event={$event->name}&mode=create\">Create Deck</a>";
    if($entry->deck) {
      $deckname = $entry->deck->name;
			if ($deckname == "") {$deckname = "* NO NAME *";}
			$decklink = "<a href=\"deck.php?id={$entry->deck->id}&mode=view\">{$deckname}</a>";
		}
		$rstar = "<font color=\"#FF0000\">*</font>";
		if($entry->deck->cardcount < 60) {$decklink .= $rstar;}
		if($entry->deck->cardcount < 6) {$decklink .= $rstar;}
		echo "<td>$decklink</td>";
		echo "<td align=\"center\">";
		echo "<input type=\"checkbox\" name=\"delentries[]\" ";
		echo "value=\"{$entry->player->name}\"></td></tr>";
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
}

function matchList($event) {
  $matches = $event->getMatches();

	echo "<tr><td align=\"center\" colspan=\"2\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<b>Match List</td></tr>";
	echo "<tr><td align=\"center\" colspan=\"5\">";
	echo "<i>* denotes a playoff/finals match.</td></tr>";
	echo "<input type=\"hidden\" name=\"view\" value=\"match\">";
	echo "<tr><td>&nbsp;</td></tr>";
	if(count($matches) > 0) {
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
  foreach ($matches as $match) { 
		if( $first && $match->timing == 1) {
			$rndadd = $match->rounds;
		}
		$first = 0;
		$printrnd = ($match->timing == 2) ? 
			$match->round + $rndadd : $match->round;
    $printplr = $match->getWinner();
    if (is_null($printplr)) { 
      $printplr = "* Draw *"; 
    } 
    
    $star = ($match->timing > 1) ? "*" : "";
		echo "<tr><td align=\"center\">$printrnd$star</td>";
		echo "<td>{$match->playera}</td>";
		echo "<td>{$match->playerb}</td><td>$printplr</td>";
		echo "<td align=\"center\">";
		echo "<input type=\"checkbox\" name=\"matchdelete[]\" ";		
		echo "value=\"{$match->id}\"></td></tr>";
	}
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

  $finalists = $event->getFinalists(); 

  $t4used = 0; 
  $t8used = 0;
  foreach ($finalists as $finalist) { 
    if ($finalist['medal'] == '1st') { 
      $def1 = $finalist['player']; 
    } elseif ($finalist['medal'] == '2nd') { 
      $def2 = $finalist['player'];
    } elseif ($finalist['medal'] == 't4') { 
      $def4[$t4used++] = $finalist['player']; 
    } elseif ($finalist['medal'] == 't8') { 
      $def8[$t8used++] = $finalist['player']; 
    } 
  } 

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
	echo "<option value=\"\">- Structure -</option>";
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
  $event = new Event("");
  $event->start = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
	if (strcmp($_POST['naming'], "auto") == 0) {
		$event->name = sprintf("%s %d.%02d",$_POST['series'], $_POST['season'], 
			$_POST['number']);
  } else {
    $event->name = $_POST['name'];
  }
  $event->format = $_POST['format']; 
  $event->host = $_POST['host'];
  $event->cohost = $_POST['cohost']; 
  $event->kvalue = $_POST['kvalue']; 
  $event->series = $_POST['series']; 
  $event->season = $_POST['season']; 
  $event->number = $_POST['number']; 
  $event->threadurl = $_POST['threadurl']; 
  $event->metaurl = $_POST['metaurl']; 
  $event->reporturl = $_POST['reporturl']; 
  
  if($_POST['mainrounds'] == "") {$_POST['mainrounds'] = 3;}
	if($_POST['mainstruct'] == "") {$_POST['mainstruct'] = "Swiss";}
  $event->mainrounds = $_POST['mainrounds'];
  $event->mainstruct = $_POST['mainstruct'];
	if($_POST['finalrounds'] == "") {$_POST['finalrounds'] = 3;}
  if($_POST['finalstruct'] == "") {$_POST['finalstruct'] = "Single Elimination";}
  $event->finalrounds = $_POST['finalrounds'];
  $event->finalstruct = $_POST['finalstruct'];

  $event->save(); 

	if(strcmp($_POST['host'], $_SESSION['username']) != 0) {
    $event->addSteward($_SESSION['username']);
  }

  return $event;
}

function updateEvent() {
  $event = new Event($_POST['name']); 
  $event->start = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
  $event->finalize = $_POST['finalize'] == 1 ? 1 : 0;
   
  $event->format = $_POST['format']; 
  $event->host = $_POST['host'];
  $event->cohost = $_POST['cohost']; 
  $event->kvalue = $_POST['kvalue']; 
  $event->series = $_POST['series']; 
  $event->season = $_POST['season']; 
  $event->number = $_POST['number']; 
  $event->threadurl = $_POST['threadurl']; 
  $event->metaurl = $_POST['metaurl']; 
  $event->reporturl = $_POST['reporturl']; 
  
  if($_POST['mainrounds'] == "") {$_POST['mainrounds'] = 3;}
	if($_POST['mainstruct'] == "") {$_POST['mainstruct'] = "Swiss";}
  $event->mainrounds = $_POST['mainrounds'];
  $event->mainstruct = $_POST['mainstruct'];
	if($_POST['finalrounds'] == "") {$_POST['finalrounds'] = 3;}
  if($_POST['finalstruct'] == "") {$_POST['finalstruct'] = "Single Elimination";}
  $event->finalrounds = $_POST['finalrounds'];
  $event->finalstruct = $_POST['finalstruct'];

  $event->save(); 
  return $event;
}

function trophyField($event) {
	if($event->hastrophy) {
		echo "<tr><td>&nbsp;</td></tr>";
		echo "<tr><td colspan=\"2\" align=\"center\">";
		echo "<img src=\"displayTrophy.php?event={$event->name}\"></td></tr>";
	}
  echo "<tr><td valign=\"top\"><b>Trophy Image</td><td>";
  echo "<input type=\"file\" id=\"trophy\" name=\"trophy\">&nbsp";
  echo "<input type=\"submit\" name=\"mode\" value=\"Upload Trophy\">";
  echo "</tr>";
}

function insertTrophy() {
	if($_FILES['trophy']['size'] > 0) {
		$file = $_FILES['trophy'];
		$event = $_POST['name'];
		
		$name = $file['name'];
    $tmp = $file['tmp_name'];
    $size = $file['size'];
    $type = $file['type'];

		$f = fopen($tmp, 'rb');
    $content = fread($f, filesize($tmp));
    fclose($f);

    $db = Database::getConnection(); 
    $stmt = $db->prepare("DELETE FROM trophies WHERE event = ?");
    $stmt->bind_param("s", $event);
    $stmt->execute() or die($stmt->error);
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO trophies(event, size, type, image) 
      VALUES(?, ?, ?, ?)");
    $null = NULL;
    $stmt->bind_param("sdsb", $event, $size, $type, $null); 
    $stmt->send_long_data(3, $content);
    $stmt->execute() or die($stmt->error); 
    $stmt->close(); 
    return true;
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
  $playernames = $event->getPlayers();
	echo "<select name=\"newmatchplayer$letter\">";
	if(strcmp("\n", $def) == 0) {
		echo "<option value=\"\">- Player $letter -</option>";
	} else {
		echo "<option value=\"\">- None -</option>";
  }
  foreach ($playernames as $player) { 
		$selstr = (strcmp($player, $def) == 0) ? "selected" : "";
		echo "<option value=\"{$player}\" $selstr>";
		echo "{$player}</option>";
	}
	echo "</select>";
}

function roundDropMenu($event) {
	echo "<select name=\"newmatchround\">";
	echo "<option value=\"\">- Round -</option>";
	for($r = 1; $r <= ($event->mainrounds + $event->finalrounds); $r++) {
		$star = ($r > $event->mainrounds) ? "*" : "";
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
  $name = $event->name;
	echo "<tr><td colspan=\"2\" align=\"center\">";
	echo "<a href=\"event.php?name=$name&view=reg\">Registration</a>";
	echo " | <a href=\"event.php?name=$name&view=match\">Match Listing</a>";
	echo " | <a href=\"event.php?name=$name&view=medal\">Medals</a>";
	echo " | <a href=\"event.php?name=$name&view=autoinput\">Auto-Input</a>";
	echo " | <a href=\"event.php?name=$name&view=fileinput\">DCI-R File Input";
	echo "</a></td></tr>";
}

function updateReg() {
	$event = new Event($_POST['name']);
  for($ndx = 0; $ndx < sizeof($_POST['delentries']); $ndx++) {
    $event->removeEntry($_POST['delentries'][$ndx]);
	}		

	$new = chop($_POST['newentry']);
  if (strcmp($new, "") != 0) {
    $player = Player::findByName($new); 
    if (!$player) { 
      $player = Player::createByName($new); 
    } 
    $event->addPlayer($player->name);
	}
}

function updateMatches() {
  for($ndx = 0; $ndx < sizeof($_POST['matchdelete']); $ndx++) {
    Match::destroy($_POST['matchdelete'][$ndx]);
	}

	$pA = $_POST['newmatchplayerA'];
	$pB = $_POST['newmatchplayerB'];
	$res = $_POST['newmatchresult'];
	$rnd = $_POST['newmatchround'];

	if(strcmp($pA, "") != 0 && strcmp("$pB", "") != 0
		&& strcmp($res, "") != 0 && strcmp($rnd, "") != 0) {
    
    $event = new Event($_POST['name']); 
    $event->addMatch($pA, $pB, $rnd, $res);
	}
}

function updateMedals() {
  $name = $_POST['name'];
  $event = new Event($_POST['name']); 

  $winner = $_POST['newmatchplayer1'];
  $second = $_POST['newmatchplayer2']; 
  $t4 = array($_POST['newmatchplayer3'], $_POST['newmatchplayer4']);
  $t8 = array($_POST['newmatchplayer5'],  $_POST['newmatchplayer6'],  $_POST['newmatchplayer7'],  $_POST['newmatchplayer8']);  

  $event->setFinalists($winner, $second, $t4, $t8); 
}

function autoInputForm($event) {
	echo "<tr><td colspan=\"2 align=\"center\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">";
  $totalrnds = 0;
  foreach ($event->getSubevents() as $subevent) { 
		if(strcmp($subevent->type, "Single Elimination") == 0) {
			for($rnd = 1; $rnd <= $subevent->rounds; $rnd++) {
				$rem = pow(2, $subevent->rounds - $rnd + 1);	
				echo "<tr><td colspan=\"2\" align=\"center\"><b>";
				echo "Round of $rem Pairings</td></tr>";
				echo "<tr><td colspan=\"2\" align=\"center\">";
				echo "<textarea name=\"finals[]\" rows=\"10\" cols=\"60\">";
				echo "</textarea></td></tr>";
				echo "<tr><td>&nbsp;</td></tr>";
			}
		} else {
			for($rnd = 1; $rnd <= $subevent->rounds; $rnd++) {
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
			$totalrnds += $subevent->rounds;
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
	$pairings = array();
	$standings = array();
	for($rnd = 0; $rnd < sizeof($_POST['pairings']); $rnd++) {
		$pairings[$rnd] = extractPairings($_POST['pairings'][$rnd]);
		if($rnd == 0) {
			$standings[$rnd] = standFromPairs($_POST['pairings'][$rnd + 1]);
		} else {
			$testStr = chop($_POST['standings'][$rnd - 1]);
			if(strcmp($testStr, "") == 0) {
				$standings[$rnd] = standFromPairs($_POST['pairings'][$rnd + 1]);
			}
			else {
				$standings[$rnd] = extractStandings($_POST['standings'][$rnd - 1]);
			}
		}
	}
  $event = new Event($_POST['name']);
	$sid = $event->mainid;
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
      $objplayera = Player::findOrCreateByName($playerA);
      $objplayerb = Player::findOrCreateByName($playerB);

      $event->addPlayer($playerA); 
      $event->addPlayer($playerB);

      $event->addMatch($playerA, $playerB, $rnd+1, $winner); 
		}
	}
	$finals = array();
	for($ndx = 0; $ndx < sizeof($_POST['finals']); $ndx++) {
		$finals[$ndx] = extractFinals($_POST['finals'][$ndx]);
	}
  $fid = $event->finalid;
  $win = ""; 
  $sec = ""; 
  $t4 = array(); 
  $t8 = array();
	for($ndx = 0; $ndx < sizeof($finals); $ndx++) {
		for($match = 0; $match < sizeof($finals[$ndx]); $match+=2) {
			$playerA = $finals[$ndx][$match];
      $playerB = $finals[$ndx][$match + 1];
      $event->addPlayer($playerA);
      $event->addPlayer($playerB);
			if($ndx < sizeof($finals) - 1) {
				$winner = detwinner($playerA, $playerB, $finals[$ndx + 1]);
      } else {
        $winner = $_POST['champion'];
      }
			$res = "D";
			if(strcmp($winner, $playerA) == 0) {$res = "A";}
      if(strcmp($winner, $playerB) == 0) {$res = "B";}
      $event->addMatch($playerA, $playerB, $ndx + 1 + $event->mainrounds, $res);
			$loser = (strcmp($winner, $playerA) == 0) ? $playerB : $playerA;
      if ($ndx == sizeof($finals) - 1) {
        $win = $winner;
        $sec = $loser;
      } elseif ($ndx == sizeof($finals) - 2) {
        $t4[] = $loser;
      } elseif($ndx == sizeof($finals) - 3) {
        $t8[] = $loser;
			}
		}			
  }
  $event->setFinalists($win, $sec, $t4, $t8);  
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

function authFailed() {
	echo "You are not permitted to make that change. Please contact the ";
	echo "event host to modify this event. If you <b>are</b> the event host, ";
	echo "or feel that you should have privilege to modify this event, you ";
	echo "should contact jamuraa via the forums.<br><br>";
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
  $event = new Event($_POST['name']);
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
	$ret = array();
	for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
		$tokens = split(",", $lines[$ndx]);
    if(preg_match("/\"(.*)\"/", $tokens[3], $matches)) {
      Player::findOrCreateByName($matches[1]);
      $event->addPlayer($matches[1]);
			$ret[] = $matches[1];
		}
	}
	return $ret;
}

function dciinputmatches($reg, $data) {
  $event = new Event($_POST['name']);
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
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
        $event->addMatch($pa, $pb, $rnd, $res);    
			}
		}
	}
}

function dciinputplayoffs($reg, $data) {
  $event = new Event($_POST['name']);
	$data = preg_replace("/\n/", "\n", $data);
	$lines = split("\n", $data);
	$ntables = $lines[0];
	$nrounds = log($ntables, 2);
	for($rnd = 1; $rnd <= $nrounds; $rnd++) {
		$ngames = pow(2, $nrounds - $rnd);
		for($game = 0; $game < $ngames; $game++) {
			$offset = 2 + $game*24;
			$playera = $lines[$offset + ($rnd-1)*3];
			$pbl = $offset + ($rnd-1)*3 + 12;
			$playerb = $lines[$pbl];
			$winner  = $lines[(($pbl+1)+ 3*$rnd - 6)/2 - 1];
			$pa = $reg[$playera - 1];
			$pb = $reg[$playerb - 1];
			$res = 'D';
			if($winner == $playera) {$res = 'A';}
      if($winner == $playerb) {$res = 'B';}			
      $event->addMatch($pa, $pb, $rnd + $event->mainrounds, $res);
		}
	}
}
?>
