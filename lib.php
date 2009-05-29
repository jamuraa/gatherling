<?php
$HC = "#DDDDDD";
$R1 = "#EEEEEE";
$R2 = "#FFFFFF";
$CC = $r1;

function dbcon() {
	$user = "pdcmagic";
	$pass = "pdcm4g1crul3s";
	$db   = "pdcmagic_gath";
#	$host = "64.14.74.93";
#	$host = "192.168.1.12";
	$host = 'localhost';
	$dblink = mysql_connect($host, $user, $pass);
	@mysql_select_db($db, $dblink);
	return $dblink;
}

function headerColor() {
	global $HC, $CC, $R1;
	$CC = $R2;
	return $HC;
}

function rowColor() {
	global $CC, $R1, $R2;
	if(strcmp($CC, $R1) == 0) {$CC = $R2;}
	else {$CC = $R1;}
	return $CC;
}

function linkToLogin() {
	echo "<center>\n";
    echo "Please <a href=\"login.php\">Click Here</a> to log in.\n";
	echo "</center>\n";
}

function hostCheck() {
    $host = 0;
    $db = dbcon();
    $query = "SELECT host, super FROM players 
        WHERE name=\"{$_SESSION['username']}\"";
    $result = mysql_query($query, $db) or die(mysql_error());
    if(mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        if($row['host'] == 1 || $row['super'] == 1) {$host = 1;}
    }
    mysql_free_result($result);
    mysql_close($db);
    return $host;
}

function noHost() {
	echo "<center>\n";
	echo "Only hosts and admins may access that page.</center>\n";
}

function recordString($id) {
    $db = dbcon();
    $query = "SELECT m.playera, m.playerb, m.result, n.player
        FROM matches AS m, events AS e, subevents AS s, decks AS d, entries AS n
        WHERE e.name=n.event
        AND m.subevent=s.id
        AND s.parent=e.name
        AND n.deck=d.id
        AND d.id=$id
        AND (m.playera=n.player OR m.playerb=n.player)
        ORDER BY timing, round";
    $result = mysql_query($query, $db) or die(mysql_error());
    $w = 0; $l = 0;
    while($row = mysql_fetch_assoc($result)) {
        if($row['player'] == $row['playera'] && $row['result'] == "A" ||
           $row['player'] == $row['playerb'] && $row['result'] == "B") {
            $w++;}
        if($row['player'] == $row['playera'] && $row['result'] == "B" ||
           $row['player'] == $row['playerb'] && $row['result'] == "A") {
            $l++;}
    }
    $str = $w . "-" . $l;
    return $str;
}

function playerRecord($player, $event) {
	$db = dbcon();
	$query = "SELECT m.playera, m.playerb, m.result
		FROM matches AS m, events AS e, subevents AS s
		WHERE e.name=s.parent AND s.id=m.subevent AND e.name=\"$event\"
		AND (m.playera=\"$player\" OR m.playerb=\"$player\")";
	$result = mysql_query($query, $db) or die(mysql_error());
	$w = 0; $l = 0;
    while($row = mysql_fetch_assoc($result)) {
        if(strcasecmp($player, $row['playera']) == 0 && $row['result'] == "A" ||
           strcasecmp($player, $row['playerb']) == 0 && $row['result'] == "B") {
            $w++;}
        if(strcasecmp($player, $row['playera']) == 0 && $row['result'] == "B" ||
           strcasecmp($player, $row['playerb']) == 0 && $row['result'] == "A") {
            $l++;}
    }
    $str = $w . "-" . $l;
    return $str;
}

function medalImgStr($medal) {
	$ret = "<img style=\"border-width: 0px\" ";
	$ret = $ret . "src=\"/images/" . $medal . ".gif\">";
	return $ret;
}

function getColorImages($id) {
    $db = dbcon();
    $query = "SELECT sum(isw*d.qty) AS w, sum(isg*d.qty) AS g, 
        sum(isu*d.qty) AS u, sum(isr*d.qty) AS r, sum(isb*d.qty) AS b
    FROM cards AS c, deckcontents AS d
    WHERE d.deck=$id AND c.id=d.card AND d.issideboard != 1";
    $result = mysql_query($query, $db) or die(mysql_error());
    $row = mysql_fetch_assoc($result);
    asort($row);
    $row = array_reverse($row, true);
    $str = "";
    foreach($row as $color => $n) {
        if($n > 0) {
            $str = $str . "<img src=\"/images/mana$color.gif\">";
        }
    }
    mysql_free_result($result);
    mysql_close($db);
    return $str;
}

function seriesDropMenu($series, $useall = 0) {
    $db = dbcon();
    $query = "SELECT name FROM series ORDER BY isactive DESC, name";
    $result = mysql_query($query, $db) or die(mysql_error());
    echo "<select name=\"series\">";
    $title = ($useall == 0) ? "- Series -" : "All";
    echo "<option value=\"\">$title</option>";
    while($thisSeries = mysql_fetch_assoc($result)) {
        $name = $thisSeries['name'];
        $selStr = (strcmp($series, $name) == 0) ? "selected" : "";
        echo "<option value=\"$name\" $selStr>$name</option>";
    }
    echo "</select>";
    mysql_free_result($result);
    mysql_close($db);
}

function seasonDropMenu($season, $useall = 0) {
    $db = dbcon();
    $query = "SELECT MAX(season) AS m FROM events";
    $result = mysql_query($query, $db) or die(mysql_error());
    $maxarr = mysql_fetch_assoc($result);
    $max = $maxarr['m'];
    $title = ($useall == 0) ? "- Season - " : "All";
    numDropMenu("season", $title, max(10, $max + 1), $season);
}

function formatDropMenu($format, $useAll = 0) {
    $db = dbcon();
    $query = "SELECT name FROM formats ORDER BY priority desc, name";
    $result = mysql_query($query, $db) or die(mysql_error());
    echo "<select name=\"format\">";
    $title = ($useAll == 0) ? "- Format -" : "All";
    echo "<option value=\"\">$title</option>";
    while($thisFormat = mysql_fetch_assoc($result)) {
        $name = $thisFormat['name'];
        $selStr = (strcmp($name, $format) == 0) ? "selected" : "";
        echo "<option value=\"$name\" $selStr>$name</option>";
    }
    echo "</select>";
    mysql_free_result($result);
    mysql_close($db);
}

function numDropMenu($field, $title, $max, $def, $min = 0, $special="") {
    if(strcmp($def, "") == 0) {$def = -1;}
    echo "<select name=\"$field\">";
    echo "<option value=\"\">$title</option>";
    if(strcmp($special, "") != 0) {
        $sel = ($def == 128) ? "selected" : "";
        echo "<option value=\"128\" $sel>$special</option>";
    }
    for($n = $min; $n <= $max; $n++) {
        $selStr = ($n == $def) ? "selected" : "";
        echo "<option value=\"$n\" $selStr>$n</option>";
    }
    echo "</select>";
}


?>
