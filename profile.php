<?php session_start();?>
<?php include 'lib.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>PDCMagic.com | Gatherling | Player Profile</title>
<?php print_header();?>
<?php include 'gathnav.php';?>
<div id="breadcrummer"><div class="innertube"><p class="breadcrumb"><a href="/">PDCMagic.com</a><a href="index.php">Gatherling</a>Profile</p></div></div>
<div id="contentwrapper">
<div id="contentcolumn"><br>
<div class="articles">
<table width=95% align=center border=1 bordercolor=black 
cellspacing=0 cellpadding=5>
<tr><td class=articles bgcolor=#B8E0FE align=center cellpadding=5>
<h1>PLAYER PROFILE</h1></td></tr>
<tr><td bgcolor=white><br>

<?php content(); ?>

<br></td></tr>
<tr><td align=center bgcolor=#DDDDDD cellpadding=15>
<h3><?php version_tagline(); ?></h3>
</td></tr></table></div>
<br /><br /></div></div>
<?php print_footer();?>

<?php
function content() {
	$playername = "";
	if(isset($_SESSION['username'])) {$playername = $_SESSION['username'];}
	if(isset($_GET['player'])) {$playername = $_GET['player'];}
	if(isset($_POST['player'])) {$playername = $_POST['player'];}
  if(chop($playername) != "") {
    $player = Player::findByName($playername); 
    if (!is_null($player)) { 
      profileTable($player);  
    } else { 
			echo "<center>\n";
			echo "$playername could not be found in the database. Please check";
			echo " your spelling and try again.\n";
			echo "</center>\n";
		}
	} else {
		echo "<center>\n";
        echo "Please <a href=\"login.php\">log in</a> to see";
        echo " your profile. You may also use the search below without";
        echo " logging in.\n";
        echo "</center>\n";
    }
	echo "<br><br>\n";
	searchForm($playername);
}

function profileTable($player) {
	echo "<table style=\"border-width: 0px\" align=\"center\" width=600>\n";
    echo "<tr><td valign=\"top\">";
    infoTable($player);
    echo "</td><td align=\"right\" valign=\"top\">";
    medalTable($player);
    echo "</td></tr>";
    echo "<tr><td>&nbsp</td></tr>";
    echo "<tr><td valign=\"top\">";
    bestDecksTable($player);
    echo "</td><td align=\"right\">";
    trophyTable($player);
    echo "</td></tr>";
    echo "</table>";
}

function infoTable($player) {
	$ndx = 0; $sum = 0; $favF = "";
  foreach ($player->getFormatsPlayedStats() as $tmprow) { 
		$sum += $tmprow['cnt'];
		if ($ndx == 0) {
			$max = $tmprow['cnt'];
			$favF = $tmprow['format'];
		}
		$ndx++;
	}
	$pcgF = 0;
	if($sum > 0) {$pcgF = round(($max/$sum)*100);}

  $ndx = 0; $sum = 0; $favS = "";
  foreach ($player->getSeriesPlayedStats() as $tmprow) { 
    $sum += $tmprow['cnt'];
    if ($ndx == 0) {
      $max = $tmprow['cnt'];
      $favS = $tmprow['series'];
    }
    $ndx++;
  }
	$pcgS = 0;
  if($sum > 0) {$pcgS = round(($max/$sum)*100);}

	$line1 = strtoupper($player->name);

  $matches = $player->getAllMatches(); 
  $nummatches = count($matches);

  $rating = $player->getRating(); 
  $hosted = $player->getHostedEventsCount();
  $lastevent = $player->getLastEventPlayed();

	echo "<table style=\"border-width: 0px;\" width=250>\n";
	echo "<tr><td align=\"left\" colspan=2 style=\"font-size: 10pt;\">";
	echo "<b>$line1</td></tr>\n";
	echo "<tr><td>Rating:</td>\n";
	echo "<td align=\"right\">{$rating}</td></tr>\n";
	echo "<tr><td>Matches Played:</td>\n";
	echo "<td align=\"right\">$nummatches</td></tr>\n";
	echo "<tr><td>Record:</td>\n";
	echo "<td align=\"right\">{$player->getRecord()}<td>";
	echo "</tr>\n";	
	if($hosted > 0) {
		echo "<tr><td>Events Hosted:</td>\n";
		echo "<td align=\"right\">$hosted</td></tr>\n";
	}
	echo "<tr><td>Favorite Format:</td>\n";
	echo "<td align=\"right\">$favF ($pcgF%)</td></tr>\n";
	echo "<tr><td>Favorite Series:</td>\n";
	echo "<td align=\"right\">$favS ($pcgS%)</td></tr>\n";
  echo "<tr><td>Last Active:</td>\n";
  if (!is_null($lastevent)) { 
    $lastActive = date("F j, Y", strtotime($lastevent->start));
    echo "<td align=\"right\">$lastActive ({$lastevent->name})</td></tr>\n";
  } else { 
    echo "<td align=\"right\">Never</td></tr>\n";
  }
	echo "</table>";
}

function medalTable($player) {

  $medalcount = $player->getMedalStats();

	echo "<table style=\"border-width: 0px\" width=260>\n";
	echo "<tr><td align=\"center\" colspan=4><b>MEDALS EARNED</td></tr>\n";
	if(count($medalcount) == 0) {
		echo "<tr><td align=\"center\" colspan=2>";
		echo "<i>{$player->name} has not earned any medals.</td></tr>\n";
	}
	else {
		medalCell("1st", $medalcount['1st']);
		medalCell("2nd", $medalcount['2nd']);
		medalCell("t4", $medalcount['t4']);
		medalCell("t8", $medalcount['t8']);
	}
	echo "</table>\n";
}

function trophyTable($player) {
  $events = $player->getEventsWithTrophies();
	echo "<table style=\"border-width: 0px;\" width=260>\n";
	echo "<tr><td align=\"center\"><b>TROPHIES EARNED</td></tr>\n";
	if(count($events) == 0) {
		echo "<tr><td align=\"center\"><i>{$player->name} has not earned any trophies.</td></tr>\n";
	}
  else {
    foreach ($events as $eventname) { 
			echo "<tr><td align=\"center\">";
			echo "<a href=\"deck.php?mode=view&event=$eventname\">";
			echo "<img style=\"border-width: 0px;\" ";
			echo "src=\"displayTrophy.php?event=$eventname\">";
			echo "</a></td></tr>";
		}
	}
	echo "</table>\n";
}

function bestDecksTable($player) {
	echo "<table style=\"border-width: 0px\" width=250>\n";
	echo "<tr><td align=\"left\" colspan=3><b>MEDAL WINNING DECKS</td></tr>\n";
  $printed = 0;
  foreach ($player->getBestDeckStats() as $row) { 
    if($row['score'] > 0) {
			$record = deckRecordString($row['name'], $player);
			if(chop($row['name']) == "") {$row['name'] = "* NO NAME *";}
			echo "<tr><td>";
			echo "<a href=\"deck.php?mode=view&id={$row['id']}\">";
			echo "{$row['name']}</a></td>\n";
			echo "<td align=\"center\" width=50>$record</td>";
			echo "<td align=\"right\">";
			for($i = 0; $i < $row['1st']; $i++) {inlineMedal('1st');}
			for($i = 0; $i < $row['2nd']; $i++) {inlineMedal('2nd');}
			for($i = 0; $i < $row['t4']; $i++) {inlineMedal('t4');}
			for($i = 0; $i < $row['t8']; $i++) {inlineMedal('t8');}
			echo "</td></tr>\n";
			$printed++;
		}
	}
	if($printed == 0) {
		echo "<tr><td colspan=3><i>{$player->name} has no medal winning decks.";
		echo "</td></tr>\n";
	}
	echo "</table>\n";
}

function medalCell($medal, $n) {
	if(is_null($n)) {$n = 0;}
	echo "<tr><td align=\"right\" width=130>";
	echo "<img src=\"/images/$medal.gif\">&nbsp;</td>\n";
	echo  "<td>$n</td></tr>\n";
}

function inlineMedal($medal) {
	echo "<img src=\"/images/$medal.gif\">&nbsp;";
}

function deckRecordString($deckname, $player) {
  $matches = $player->getMatchesByDeckName($deckname); 
  $w = 0; $l = 0; $d = 0;
  foreach ($matches as $match) { 
    if($match->playerWon($player->name)) {
      $w++; 
    } elseif ($match->playerLost($player->name)) { 
      $l++; 
    } else {
      $d++; 
    }    
  }
  $str = $w . "-" . $l;
  if ($d > 0) { 
    $str .= "-" . $d;
  } 
  return $str;
}

function searchForm($name) {
	echo "<form action=\"profile.php\" mode=\"post\">\n";
	echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
	echo "<tr><td colspan=2 align=\"center\"><b>Player Lookup</td></tr>\n";
	echo "<tr><td>&nbsp;</td></tr>\n";
	echo "<tr><td><input type=\"text\" name=\"player\" value=\"$name\">";
	echo "</td><td><input type=\"submit\" name=\"mode\"";
	echo " value=\"Lookup Profile\"></td></tr></table>\n";
	echo "</form>\n";
}
?>
