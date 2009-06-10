<?php

require_once 'bootstrap.php';

$HC = "#DDDDDD";
$R1 = "#EEEEEE";
$R2 = "#FFFFFF";
$CC = $r1;

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

function noHost() {
	echo "<center>\n";
	echo "Only hosts and admins may access that page.</center>\n";
}

function medalImgStr($medal) {
	$ret = "<img style=\"border-width: 0px\" ";
	$ret = $ret . "src=\"/images/" . $medal . ".gif\">";
	return $ret;
}

function seriesDropMenu($series, $useall = 0) {
    $db = Database::getConnection();
    $query = "SELECT name FROM series ORDER BY isactive DESC, name";
    $result = $db->query($query) or die($db->error);
    echo "<select name=\"series\">";
    $title = ($useall == 0) ? "- Series -" : "All";
    echo "<option value=\"\">$title</option>";
    while($thisSeries = $result->fetch_assoc()) {
        $name = $thisSeries['name'];
        $selStr = (strcmp($series, $name) == 0) ? "selected" : "";
        echo "<option value=\"$name\" $selStr>$name</option>";
    }
    echo "</select>";
    $result->close(); 
}

function seasonDropMenu($season, $useall = 0) {
    $db = Database::getConnection();
    $query = "SELECT MAX(season) AS m FROM events";
    $result = $db->query($query) or die($db->error);
    $maxarr = $result->fetch_assoc();
    $max = $maxarr['m'];
    $title = ($useall == 0) ? "- Season - " : "All";
    numDropMenu("season", $title, max(10, $max + 1), $season);
    $result->close();
}

function formatDropMenu($format, $useAll = 0) {
    $db = Database::getConnection();
    $query = "SELECT name FROM formats ORDER BY priority desc, name";
    $result = $db->query($query) or die($db->error);
    echo "<select name=\"format\">";
    $title = ($useAll == 0) ? "- Format -" : "All";
    echo "<option value=\"\">$title</option>";
    while($thisFormat = $result->fetch_assoc()) {
        $name = $thisFormat['name'];
        $selStr = (strcmp($name, $format) == 0) ? "selected" : "";
        echo "<option value=\"$name\" $selStr>$name</option>";
    }
    echo "</select>";
    $result->close();
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

function version_tagline() { 
  print "Gatherling version 1.9.1 (\"It's the United States of Don't Touch That Thing Right in Front of You.\")";
  # print "Gatherling version 1.9 (\"It's funny 'cause the squirrel got dead\")";
} 
