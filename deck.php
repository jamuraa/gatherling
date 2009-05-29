<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Deck Database</title>
<?php include '../header2.ssi';?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Decks</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>DECK DATABASE</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content();?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3>Updated by <b>WoCoNation</b> on 2007-12-27</td></tr></table></div>
<br><br></div></div>
<?php #include 'gatherlingnav.php';?>
<?php include '../footer.ssi';?>


<?php
function content() {
	if(strcmp($_POST['mode'], "Create Deck") == 0) {
		$resarr = insertDeck();
		deckProfile($resarr[0]);
	}
	elseif(strcmp($_POST['mode'], "Update Deck") == 0) {
		if(authCheck($_POST['id'])) {
			$resarr = updateDeck();
			deckProfile($resarr[0]);
		}
		else {authFailed();}
	}
	elseif(strcmp($_POST['mode'], "Edit Deck") == 0) {
		$auth = authCheck($_POST['id']);
		if($auth) {
			deckForm($_POST['id'], $auth);
		}
		else{authFailed();}
	}
	elseif(strcmp($_GET['mode'], "create") == 0) {
		deckForm();
	}
	elseif(strcmp($_GET['mode'], "view") == 0) {
		if(isset($_GET['event'])) {
			$db = dbcon();
			$query = "SELECT deck FROM entries WHERE event=\"{$_GET['event']}\"
				AND medal=\"1st\"";
			$result = mysql_query($query, $db) or die($query);
			$row = mysql_fetch_assoc($result);
			mysql_free_result($result);
			mysql_close($db);
			$_GET['id'] = $row['deck'];
		}
		deckProfile($_GET['id']);
	}
}

function viewDeck($id) {
	$db = dbcon();
	$query = "SELECT e.player, e.event, e.medal, d.name, d.notes
		FROM entries e, decks d WHERE e.deck=$id AND d.id=$id";
	$pResult = mysql_query($query, $db) or die($query);
	$query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
		WHERE c.id=dc.card AND dc.deck=$id AND c.type LIKE '%Creature%'
		AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
	$cResult = mysql_query($query, $db) or die(mysql_error());
	$query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
		WHERE c.id=dc.card AND dc.deck=$id AND c.type NOT LIKE '%Land%'
		AND c.type NOT LIKE '%Creature%'
		AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
	$oResult = mysql_query($query, $db) or die(mysql_error());
	$query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
		WHERE c.id=dc.card AND dc.deck=$id AND c.type LIKE '%Land%'
		AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
	$lResult = mysql_query($query, $db) or die(mysql_error());
	$query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
		WHERE c.id=dc.card AND dc.deck=$id 
		AND dc.issideboard=1 ORDER by dc.qty DESC, c.name";
	$sResult = mysql_query($query, $db) or die(mysql_error());

	$info = mysql_fetch_assoc($pResult);
	$banner = 0;
	if(strcmp($info['medal'], "1st") == 0) {
		$query = "SELECT COUNT(*) FROM trophies 
			WHERE event=\"{$info['event']}\"";
		$tmpResult = mysql_query($query) or die(mysql_error());
		$tmpArr = mysql_fetch_row($tmpResult);
		if($tmpArr[0] == 1) {$banner = 1;}
		mysql_free_result($tmpResult);
	}

	echo "<form action=\"deck.php\" method=\"post\">";
	echo "<table align=\"center\" style=\"border-width: 0px;\">\n";
	if($banner) {
		echo "<tr><td align=\"center\">";
		echo "<a href=\"event.php?name={$info['event']}\">";
		echo "<img style=\"border-width: 0px;\" ";
		echo "src=\"displayTrophy.php?event={$info['event']}\"></a>";
		echo "</td></tr>\n";
	}
	else {
		echo "<tr><td align=\"center\"><b>{$info['name']}</td></tr>\n";
		echo "<tr><td align=\"center\"><i>";
		echo "{$info['player']} - ";
		echo "<a href=\"event.php?name={$info['event']}\">{$info['event']}</a>";
		printPlaceString($info['medal']);
		echo "</td></tr>\n";
	}
	echo "</table><br>\n";

	echo "<table align=\"center\" style=\"border-width: 0px;\" ";
	echo "cellpadding=\"3\">\n";
	echo "<tr><td valign=\"top\"><b>Creatures</td>\n<td>";
	printCardResults($cResult);
	echo "</td></tr>\n<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Spells</td>\n<td>";
	printCardResults($oResult);
	echo "</td></tr>\n<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Lands</td>\n<td>";
	printCardResults($lResult);
	echo "</td></tr>\n<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Sideboard</td>\n<td>";
	printCardResults($sResult);
	echo "</td></tr>\n<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	matchupTable($id);
	echo "</td></tr>";
	
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"Edit Deck\">\n";
	echo "</td></tr>\n</table></form>\n";

	mysql_free_result($pResult);
	mysql_free_result($cResult);
	mysql_free_result($oResult);
	mysql_free_result($lResult);
	mysql_free_result($sResult);
	mysql_close($db);
}

function deckForm($id = "", $auth=0) {
	$edit = (strcmp($id, "") == 0) ? 0 : 1;
	$mode = ($edit) ? "Update Deck" : "Create Deck";
	$player = (isset($_POST['player'])) ? $_POST['player'] : $_GET['player'];
  $event = (isset($_POST['player'])) ? $_POST['event'] : $_GET['event'];

  // It comes through as escaped. 
  $event_noslash = stripslashes($event);
  $player_noslash = stripslashes($player);

	if($_SESSION['username'] == $player) {$auth = 1;}
	if(!$auth) {
		if(!evAuthCheck($id, $event)) {authFailed();}
		else {$auth = 1;}
	}

	if($auth == 1) {
	$vals = array();
	if($edit) {
		$db = dbcon();
		$query = "SELECT c.name, d.qty, d.issideboard
			FROM cards c, deckcontents d
			WHERE c.id=d.card AND d.deck=$id
			ORDER BY d.issideboard, c.name";
		$result = mysql_query($query, $db) or die(mysql_error());
		while($row = mysql_fetch_assoc($result)) {
			$line = $row['qty'] . " " . $row['name'] . "\n";
			if($row['issideboard']) {
				$vals['sideboard'] = $vals['sideboard'] . $line;}
			else {$vals['contents'] = $vals['contents'] . $line;}
		}
		mysql_free_result($result);
		$query = "SELECT name, notes, archetype FROM decks WHERE id=$id";
		$result = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($result);
		mysql_free_result($result);
		mysql_close($db);
		$vals['desc'] = $row['notes'];
		$vals['archetype'] = $row['archetype'];
		$vals['name'] = $row['name'];
	}

	echo "<form action=\"deck.php\" method=\"post\">\n";
	echo "<table align=\"center\" style=\"border-width: 0px;\">\n";
	echo "<tr><td valign=\"top\"><b>Directions:</td>\n";
	echo "<td style=\"color: #000000\">To enter your deck, please give it ";
	echo "a name and select an archetype from the drop-down menu below. If ";
	echo "you do not specify and archetype, your deck will be labeled as ";
	echo "\"rogue.\" To enter cards, save your deck a a .txt file using the ";
	echo "official MTGO client, and then copy and paste the maindeck and ";
	echo "sideboard into the appropriate text boxes. ";
	echo "<font color=\"#FF0000\">Do not use a format such as \"1x Card\". ";
	echo "The parser will not accept this structure. The correct pattern is ";
	echo "\"1 Card\".</font></td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td><b>Name</td>\n<td>";
	echo "<input type=\"text\" name=\"name\" value=\"{$vals['name']}\" ";
	echo "size=\"40\"></td></tr>\n";
	if($edit) {echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";}
	echo "<tr><td><b>Archetype</td>\n<td>";
	archetypeDropMenu($vals['archetype']);
	echo "</td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Main Deck</td>\n<td>";
	echo "<textarea rows=\"20\" cols=\"60\" name=\"contents\">";
	echo "{$vals['contents']}</textarea></td></tr>\n";
	echo "<tr><td valign=\"top\"><b>Sideboard</td>\n<td>";
	echo "<textarea rows=\"10\" cols=\"60\" name=\"sideboard\">";
	echo "{$vals['sideboard']}</textarea></td></tr>\n";
	//echo "<tr><td valign=\"top\"><b>Comments</td>\n<td>";
	//echo "<textarea rows=\"10\" cols=\"60\" name=\"notes\">";
	//echo "{$vals['desc']}</textarea></td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=\"2\" align=\"center\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"$mode\">\n";
	echo "<input type=\"hidden\" name=\"player\" value=\"$player_noslash\">";
	echo "<input type=\"hidden\" name=\"event\" value=\"$event_noslash\">";
	echo "</td></tr></table></form>\n";
	}
}

function archetypeDropMenu($def) {
	$db = dbcon();
	$query = "SELECT name FROM archetypes WHERE priority > 0
		ORDER BY priority DESC, name";
	$result = mysql_query($query, $db) or die(mysql_error());
	echo "<select name=\"archetype\">\n";
	echo "<option value=\"Rogue\">- Archetype -</option>\n";
	while($arch = mysql_fetch_assoc($result)) {
		$name = $arch['name'];
		$sel = (strcmp($name, $def) == 0) ? "selected" : "";
		echo "<option value=\"$name\" $sel>$name</option>\n";
	}
	mysql_free_result($result);
	mysql_close($db);
}

function insertDeck() {
	$db = dbcon();
	$query = "INSERT INTO decks(archetype, name, notes) VALUES(
		\"{$_POST['archetype']}\", \"{$_POST['name']}\", 
		\"{$_POST['notes']}\")";
	mysql_query($query, $db) or die(mysql_error());

	$query = "SELECT LAST_INSERT_ID()";
	$result = mysql_query($query) or die(mysql_error());
	$arr = mysql_fetch_row($result);
	$deckid = $arr[0];
	mysql_free_result($result);

  $event_esc = $_POST['event'];
	$query = "UPDATE entries SET deck=$deckid 
		WHERE player=\"{$_POST['player']}\" AND event=\"{$event_esc}\"";
  if (!mysql_query($query, $db)) {
    echo mysql_error();
    die(mysql_error());
  }

	$badcards = insertCards($_POST['contents'], $deckid, $db);
	array_push($badcards, insertCards($_POST['sideboard'], $deckid, $db, 1));
	mysql_close($db);
	return array($deckid, $badcards);
}

function updateDeck() {
	$db = dbcon();
	$id = $_POST['id'];
	$query = "UPDATE decks SET
		archetype=\"{$_POST['archetype']}\",
		name=\"{$_POST['name']}\",
		notes=\"{$_POST['notes']}\"
		WHERE id=$id";
	mysql_query($query, $db) or die(mysql_error());

	$query = "DELETE FROM deckcontents WHERE deck=$id";
	mysql_query($query, $db) or die(mysql_error());
	$badcards = insertCards($_POST['contents'], $id, $db);
	array_push($badcards, insertCards($_POST['sideboard'], $id, $db, 1));
	mysql_close($db);
	return array($id, $badcards);
}

function insertCards($text, $deckid, $db, $sideboard=0) {
	$lines = split("\n", $text);
    $badcards = array();
    $cardarr = array();
    for($ndx = 0; $ndx < sizeof($lines); $ndx++) {
        $chopped = chop($lines[$ndx]);
        if(preg_match("/[ \t]*([0-9]+)[ \t]+(.*)/i", $chopped, $m)) {
            $qty = $m[1];
            $card = chop($m[2]);
			$card = preg_replace("/\306/", "AE", $card);
			$card = strtolower($card);
	    	if(isset($cardarr[$card])) {$cardarr[$card] += $qty;}
	    	else {$cardarr[$card] = $qty;}
        }
    }
    foreach($cardarr as $cardname => $qty) {
            $query = "INSERT INTO deckcontents(deck, qty, issideboard, card) 
                SELECT $deckid, $qty, $sideboard, 
				id FROM cards WHERE name=\"$cardname\"
                LIMIT 1";
            mysql_query($query, $db) or die(mysql_error());
            if(mysql_affected_rows($db) == 0) {
                array_push($badcards, $card);
            }
    }
    return $badcards;
}

function printCardResults($result) {
	if(mysql_num_rows($result) == 0) {
		echo "<i>This deck has no cards in this category.</i>\n";}
	else {
		$firstcard = mysql_fetch_assoc($result);
		echo "{$firstcard['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$firstcard['name']}\" target=\"_blank\">";
		echo "{$firstcard['name']}</a>";
	}
	while($card = mysql_fetch_assoc($result)) {
		echo "<br>\n";
		echo "{$card['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$card['name']}\" target=\"_blank\">{$card['name']}</a>";
	}
}

function printPlaceString($medal) {
	$str = "";
	if(strcmp($medal, "t8") == 0) {$str = " - Top 8";}
	if(strcmp($medal, "t4") == 0) {$str = " - Top 4";}
	if(strcmp($medal, "2nd") == 0) {$str = " - 2nd Place";}
	if(strcmp($medal, "1st") == 0) {$str = " - 1st Place";}
	echo "$str";
}

function deckProfile($id) {
	echo "<center><form action=\"deckdl.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=$id>\n";
    echo "<input type=\"submit\" name=\"mode\" ";
    echo "value=\"Download deck as .txt file\"></form></center><br>\n";
	echo "<table align=\"center\" style=\"border-width: 0px;\" width=600>\n";
	echo "<tr><td width=225>";
	deckInfoCell($id);
	echo "</td>\n<td valign=\"top\" align=\"right\">";
	trophyCell($id);
	echo "</td></tr>";
	echo "<tr><td>";
	maindeckTable($id);
	echo "</td><td valign=\"top\" align=\"right\">";
	echo "<table style=\"border-width: 0px;\" align=\"right\" width=350>";
	echo "<tr><td colspan=3 align=\"right\">";
	matchupTable($id);
	echo "</td></tr>";
	echo "<tr><td width=50></td><td valign=\"top\" align=\"left\" width=150>";
	symbolTable($id);
	echo "</td><td align=\"right\" width=150>";
	ccTable($id);
	echo "</td></tr></table>";
	echo "</td>\n";
	echo "<tr><td>";
	sideboardTable($id);
	echo "</td></tr>\n";
	echo "<tr><td colspan=2>";
	commentsTable($id);
	echo "</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2 align=\"center\">\n";
	echo "<form action=\"deck.php\" method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
	echo "<input type=\"submit\" name=\"mode\" value=\"Edit Deck\">\n";
	echo "</form></td></tr>\n";
	echo "</table>\n";
}

function commentsTable($id) {
	$db = dbcon();
	$query = "SELECT notes FROM decks WHERE id=$id";
	$result = mysql_query($query, $db);
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	mysql_close($db);
	if($row['notes'] == "" || is_null($row['notes'])) {
		$row['notes'] = "<i>No comments have been recorded for this deck.</i>";}
	$row['notes'] = preg_replace("/\n/", "<br>", $row['notes']);
	$row['notes'] = preg_replace("/\[b\]/", "<b>", $row['notes']);
	$row['notes'] = preg_replace("/\[\/b\]/", "</b>", $row['notes']);
	echo "<table style=\"border-width: 0px;\" cellpadding=1>";
	echo "<tr><td><b>COMMENTS</td></tr>";
	echo "<tr><td>{$row['notes']}</td></tr>";
	echo "</table>";
}

function deckInfoCell($id) {
	$db = dbcon();
	$query = "SELECT n.player, n.event, n.medal, d.archetype, d.name, 
		UNIX_TIMESTAMP(e.start) AS s
		FROM decks d, entries n LEFT OUTER JOIN events AS e ON n.event=e.name
		WHERE n.deck=d.id AND d.id=$id";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$query = "SELECT SUM(qty) FROM deckcontents WHERE deck=$id";
	$result = mysql_query($query, $db) or die(mysql_error());
	$tmp = mysql_fetch_row($result);
	mysql_free_result($result);
	$ncards = $tmp[0];
	mysql_close($db); 
	$mstr = "";
	$line3 = "<a href=\"profile.php?player={$row['player']}\">";
	$line3 = $line3 . "{$row['player']}</a>\n";
	$line4 = "<i>{$row['event']}";
	$line5 = date("F j, Y", $row['s']);
	if($row['medal'] == '1st') {
		$mstr = "<img src=\"/images/1st.gif\">&nbsp;";
		$line4 = $line4 . ", 1st Place";
	}
	if($row['medal'] == '2nd') {
		$mstr = "<img src=\"/images/2nd.gif\">&nbsp;";
		$line4 = $line4 . ", 2nd Place";
	}
	if($row['medal'] == 't4') {
		$mstr = "<img src=\"/images/t4.gif\">&nbsp;";
		$line4 = $line4 . ", Top 4";
	}
	if($row['medal'] == 't8') {
		$mstr = "<img src=\"/images/t8.gif\">&nbsp;";
		$line4 = $line4 . ", Top 8";
	}
	$rstar = "<font color=\"#FF0000\">*</font>";
	$line1 = $mstr . "<b>" . strtoupper($row['name']) . "</b>";
	if($ncards < 6) {$line1 .= $rstar;}
	if($ncards < 60) {$line1 .= $rstar;}
	$line2 = getColorImages($id) . " " . $row['archetype'];
	$line4 = $line4 . " (" . recordString($id) . ")</i>";

	echo "<table style=\"border-width: 0px\">\n";
	echo "<tr><td style=\"font-size: 10pt;\">$line1</td></tr>\n";
	echo "<tr><td>$line2</td></tr>\n";
	echo "<tr><td>$line3</td></tr>\n";
	echo "<tr><td>$line4</td></tr>\n";
	echo "<tr><td>$line5</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "</table>\n";

}

function trophyCell($id) {
	$db = dbcon();
	$query = "SELECT n.event, medal, count(t.event) 
		FROM entries n, trophies t 
		WHERE deck=$id and n.event=t.event 
		GROUP BY t.event;";
	$result = mysql_query($query, $db) or die(mysql_error());
	$row = mysql_fetch_assoc($result);
	if(mysql_num_rows($result) && $row['medal'] == '1st') {
		echo "<img src=\"displayTrophy.php?event={$row['event']}\">";
		echo "<br><br>";
	}
	mysql_free_result($result);
	mysql_close($db);
}

function sideboardTable($id) {
	$db = dbcon();
	$query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
        WHERE c.id=dc.card AND dc.deck=$id 
        AND dc.issideboard=1 ORDER by dc.qty DESC, c.name";
    $sResult = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px\" cellpadding=1>\n";
    echo "<tr><td colspan=1><b>SIDEBOARD</td></tr>\n";
	while($row = mysql_fetch_assoc($sResult)) {
        echo "<tr><td>{$row['qty']} ";
        echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
        echo "{$row['name']}\" target=\"_blank\">{$row['name']}</a></td></tr>\n";
    }
	echo "<tr><td>&nbsp;</td></tr>";
	echo "</table>\n";
	mysql_free_result($sResult);
	mysql_close($db);
}

function matchupTable($id) {
	$db = dbcon();
	$query = "SELECT s.timing, m.round, m.playera, m.playerb, m.result, q.qname,
		s.rounds, n.player, s.type, q.qdeck
		FROM matches AS m, events AS e, subevents AS s, 
		decks AS d, entries AS n,
		(SELECT qn.player AS qplayer, qn.deck AS qdeck, qd.name AS qname
 		 FROM entries qn
 		 LEFT OUTER JOIN decks AS qd ON qn.deck=qd.id
 		 WHERE qn.event=(SELECT event FROM entries WHERE deck=$id)
		) AS q
		WHERE e.name=n.event
		AND m.subevent=s.id
		AND s.parent=e.name
		AND n.deck=d.id
		AND d.id=$id
		AND (m.playera=n.player OR m.playerb=n.player)
		AND (q.qplayer=m.playera OR q.qplayer=m.playerb)
		AND (q.qdeck IS NULL OR q.qdeck != d.id)
		ORDER BY timing, round";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px\" cellpadding=1 align=\"right\">\n";
	echo "<tr><td colspan=4 align=\"left\"><b>MATCHUPS</td></tr>\n";
#	echo "<tr><td><b>Round</td><td><b>Result</td><td><b>Opponent</td>";
#	echo "<td><b>Deck</td></tr>\n";
#	echo "<tr><td><b>MATCHUPS</td></tr>\n";
	if(mysql_num_rows($result) == 0) {
		echo "<tr><td colspan=4><i>No matches were found for this deck</td></tr>";
	}
	while($row = mysql_fetch_assoc($result)) {
		$rnd = 'R' . $row['round'];
		if($row['timing'] > 1 && $row['type'] == 'Single Elimination') {
			$rnd = 'T' . pow(2, $row['rounds'] - $row['round'] + 1);}
		$color = "#FF9900";
		$res = "Draw";
		if((strcasecmp($row['player'], $row['playera']) == 0 && $row['result'] == 'A') ||
		   (strcasecmp($row['player'], $row['playerb']) == 0 && $row['result'] == 'B')) {
			$color = "#009900";
			$res = "Win";
		}
		if((strcasecmp($row['player'], $row['playera']) == 0 && $row['result'] == 'B') ||
		   (strcasecmp($row['player'], $row['playerb']) == 0 && $row['result'] == 'A')) {
			$color = "#FF0000";
			$res = "Loss";
		}
		$resStr = "<b><font color=\"$color\">$res</font></b>";
		$opp = $row['playera'];
		if($row['player'] == $row['playera']) {$opp = $row['playerb'];}
		$deckcell = "No Deck Found";
		if(!is_null($row['qdeck'])) {
			$oppdeckid = $row['qdeck'];
			$oppdeckname= $row['qname'];
			$deckcell = "<a href=\"deck.php?id=$oppdeckid&mode=view\">" . 
				$oppdeckname . "</a>";
		}

#		echo "<tr><td align=\"center\">$rnd</td>\n";
#		echo "<td><b><font color=\"$color\">$res</font></td>\n";
#		echo "<td>$opp</td>\n";
#		echo "<td>$deckcell</td></tr>\n";

#		echo "<tr><td>{$rnd}: $resStr vs. $opp, $deckcell</td></tr>";

		echo "<tr><td align=\"right\">$rnd:&nbsp;</td>\n";
		echo "<td align=\"left\"><b><font color=\"$color\">$res</font>&nbsp;</td>\n";
		echo "<td>vs.&nbsp;</td>\n";
		echo "<td align=\"left\"><a href=\"profile.php?player=$opp\">$opp</a>&nbsp;</td>\n";
		echo "<td align=\"right\">$deckcell&nbsp;</td></tr>\n";
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "</table>\n";
}

function maindeckTable($id) {
	$db = dbcon();
    $query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
        WHERE c.id=dc.card AND dc.deck=$id AND c.type LIKE '%Creature%'
        AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
    $cResult = mysql_query($query, $db) or die(mysql_error());
    $query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
        WHERE c.id=dc.card AND dc.deck=$id AND c.type NOT LIKE '%Land%'
        AND c.type NOT LIKE '%Creature%'
        AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
    $oResult = mysql_query($query, $db) or die(mysql_error());
    $query = "SELECT dc.qty, c.name FROM deckcontents dc, cards c
        WHERE c.id=dc.card AND dc.deck=$id AND c.type LIKE '%Land%'
        AND dc.issideboard=0 ORDER by dc.qty DESC, c.name";
    $lResult = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px\" cellpadding=1>\n";
	echo "<tr><td colspan=1><b>MAINDECK</td></tr>\n";
	echo "<tr><td colspan=2><i>Creatures</td></tr>\n";
	while($row = mysql_fetch_assoc($cResult)) {
		echo "<tr><td>{$row['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$row['name']}\" target=\"_blank\">{$row['name']}</a></td></tr>\n";
	}
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td colspan=2><i>Spells</td></tr>\n";
	while($row = mysql_fetch_assoc($oResult)) {
		echo "<tr><td>{$row['qty']} ";
		echo "<a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$row['name']}\" target=\"_blank\">{$row['name']}</a></td></tr>\n";
	}
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=2><i>Lands</td></tr>\n";
	while($row = mysql_fetch_assoc($lResult)) {
		echo "<tr><td>{$row['qty']} ";
		echo " <a href=\"http://www.magiccards.info/autocard.php?card=";
		echo "{$row['name']}\" target=\"_blank\">{$row['name']}</a></td></tr>\n";
	}
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "</table>\n";
	mysql_free_result($cResult);
	mysql_free_result($oResult);
	mysql_free_result($lResult);
	mysql_close($db);
}

function ccTable($id) {
	$db = dbcon();
	$query = "SELECT convertedcost AS cc, sum(qty) AS s
		FROM cards c, deckcontents d
		WHERE d.deck=$id
		AND c.id=d.card AND d.issideboard=0
		GROUP BY c.convertedcost
		HAVING cc>0";
	$result = mysql_query($query, $db) or die(mysql_error());

	echo "<table style=\"border-width: 0px;\">\n";
	echo "<tr><td colspan=2 align=\"center\" width=150><b>CASTING COSTS</td></tr>";
	$total = 0; $cards = 0;
	while($row = mysql_fetch_assoc($result)) {
		echo "<tr><td align=\"right\" width=75>";
		echo "<img src=\"/images/mana{$row['cc']}.gif\">";
		echo " &nbsp;</td>\n";
		echo "<td width=75 align=\"left\">{$row['s']}</td></tr>\n";
		$total += $row['cc'] * $row['s'];
		$cards += $row['s'];
	}
	mysql_free_result($result);
	if($cards == 0) {$cards = 1;}
	$avg = $total/$cards;
	echo "<tr><td align=\"right\"><i>Avg CMC:&nbsp;</td><td align=\"left\"><i>";
	printf("%1.2f", $avg);
	echo "</td></tr>\n";
	echo "</table>";
	mysql_close($db);
}

function symbolTable($id) {
	$db = dbcon();
	$query = "SELECT d.qty, c.cost, c.name
		FROM cards c, deckcontents d
		WHERE d.deck=$id
		AND c.id=d.card
		AND d.issideboard=0
		AND c.cost != \"\"";
	$result = mysql_query($query, $db);

	echo "<table style=\"border-width: 0px\">\n";
	echo "<tr><td align=\"center\" colspan=2 width=150><b>MANA SYMBOLS";
	echo "</td></tr>\n";
	$cnts = array("w" => 0, "g" => 0, "u" => 0, "r" => 0, "b" => 0);
	while($row = mysql_fetch_assoc($result)) {
		$arr = count_chars($row['cost'], 1);
		$cnts['w'] += $arr[ord('W')] * $row['qty'];
		$cnts['g'] += $arr[ord('G')] * $row['qty'];
		$cnts['u'] += $arr[ord('U')] * $row['qty'];
		$cnts['r'] += $arr[ord('R')] * $row['qty'];
		$cnts['b'] += $arr[ord('B')] * $row['qty'];
	}
	mysql_free_result($result);
	asort($cnts);
	$cnts = array_reverse($cnts, true);
	$sum = 0;
	foreach($cnts as $color => $num) {
		if($num > 0) {
		echo "<tr><td align=\"right\" width=75>";
		echo "<img src=\"/images/mana{$color}.gif\">";
		echo "&nbsp;</td>\n";
		echo "<td align=\"left\">$num</td></tr>\n";
		$sum += $num;
		}
	}
	echo "<tr><td align=\"right\"><i>Total:&nbsp;</td>\n";
	echo "<td align=\"left\"><i>$sum</td></tr>\n";
	echo "</table>\n";
	mysql_close($db);
}

function authCheck($id) {
    $auth = 0;
    $db = dbcon();
    $query = "SELECT host, super FROM players 
        WHERE name=\"{$_SESSION['username']}\"";
    $result = mysql_query($query, $db) or die(mysql_error());
    if(mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        if($row['super'] == 1) {$auth = 1;}
        elseif($row['host'] == 1) {
            $query = "SELECT host FROM events e, entries n
				WHERE e.name=n.event
				AND n.deck=$id
                AND (e.host=\"{$_SESSION['username']}\" 
                     OR e.cohost=\"{$_SESSION['username']}\")";
            $eResult = mysql_query($query, $db) or die(mysql_error());
            if(mysql_num_rows($eResult) > 0) {$auth = 1;}
            mysql_free_result($eResult);
        }
    }
    mysql_free_result($result);
    if(!$auth) {
        $query = "SELECT s.player FROM stewards s, entries n
			WHERE s.event=n.event AND n.deck=$id
            AND s.player=\"{$_SESSION['username']}\"";
        $result = mysql_query($query, $db) or die(mysql_error());
        if(mysql_num_rows($result) > 0) {$auth = 1;}
        mysql_free_result($result);
    }
	if(!$auth) {
		$query = "SELECT player FROM entries 
			WHERE deck=$id AND player=\"{$_SESSION['username']}\"";
		$result = mysql_query($query, $db) or die(mysql_error());
		if(mysql_num_rows($result) > 0) {$auth = 1;}
		mysql_free_result($result);
	}
    mysql_close($db);
    return $auth;
}

function authFailed() {
    echo "You are not permitted to make that change. Please contact the ";
    echo "event host or deck owner to modify this deck. If you <b>are</b> the event host ";
    echo "or feel that you should have privilege to modify this deck, you ";
    echo "should contact WoCoNation via the forums.<br><br>";
}

function evAuthCheck($id, $event="") {
	if(chop($id) == "") {$id = 0;}
    $auth = 0;
    $db = dbcon();
    $query = "SELECT host, super FROM players 
        WHERE name=\"{$_SESSION['username']}\"";
    $result = mysql_query($query, $db) or die(mysql_error());
    if(mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        if($row['super'] == 1) {$auth = 1;}
        elseif($row['host'] == 1) {
            $query = "SELECT host FROM events e, entries n
				WHERE e.name=n.event AND (n.deck=$id OR n.event=\"$event\")
                AND (e.host=\"{$_SESSION['username']}\" 
                     OR e.cohost=\"{$_SESSION['username']}\")";
            $eResult = mysql_query($query, $db) or die(mysql_error());
            if(mysql_num_rows($eResult) > 0) {$auth = 1;}
            mysql_free_result($eResult);
        }
    }
    mysql_free_result($result);
    if(!$auth) {
        $query = "SELECT s.player FROM stewards s, entries n
			WHERE ((s.event=n.event AND n.event=\"$event\")
			OR (n.deck=$id AND n.event=s.event)) AND
            s.player=\"{$_SESSION['username']}\"";
        $result = mysql_query($query, $db) or die(mysql_error());
        if(mysql_num_rows($result) > 0) {$auth = 1;}
        mysql_free_result($result);
    }
    mysql_close($db);
    return $auth;
}
?>
