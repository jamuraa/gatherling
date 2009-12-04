<?php

require_once 'bootstrap.php';

$HC = "#DDDDDD";
$R1 = "#EEEEEE";
$R2 = "#FFFFFF";
$CC = $r1;

function print_header($title) { 
  echo "<html><head><meta http-equiv=\"X-UA-Compatible\" content=\"IE=8\" />";
  echo "<title>{$title}</title>";
  echo <<<EOT
    <link rel="stylesheet" type="text/css" media="all" href="/css/reset.css" />
    <link rel="stylesheet" type="text/css" media="all" href="/css/text.css" />
    <link rel="stylesheet" type="text/css" media="all" href="/css/960.css" />
    <link rel="stylesheet" type="text/css" media="all" href="/css/pdcmagic.css" />
  </head>
  <body>
    <div id="maincontainer" class="container_12">
      <div id="headerimage" class="grid_12">
        <img src="http://pdcmagic.com/img/zen_header2.jpg" />
      </div>
      <div id="mainmenu_submenu" class="grid_12">
        <ul>
          <li><a href="http://pdcmagic.com/index.html">Home</a></li>
          <li><a href="http://forums.pdcmagic.com">Forums</a></li>
          <li><a href="/wordpress/">Articles</a></li>
          <li><a href="/events/index.php">Events</a></li>
          <li class="current">
            <a href="index.php">
            Gatherling
            </a>
          </li>
          <li><a href="/gatherling/ratings.php">Ratings</a></li>
          <li class="last"><a href="http://community.wizards.com/pauperonline/wiki/">Wiki</a></li>
        </ul>
      </div>
EOT;

  $player = Player::getSessionPlayer(); 

  if ($player != NULL) { 
    $host = $player->isHost();
    $super = $player->isSuper();
  } 

  $tabs = 5;
  if ($super) { 
    $tabs = 7;
  } else if ($host) { 
    $tabs = 6;
  }

  echo <<<EOT
<div id="submenu" class="grid_12 tabs_$tabs">
  <ul> 
    <li><a href="profile.php">Profile</a></li>
    <li><a href="player.php">Player CP</a></li>
    <li><a href="eventreport.php">Metagame</a></li>
    <li><a href="decksearch.php">Decks</a></li>
EOT;
  if ($host || $super) { 
    echo "<li><a href=\"event.php\">Host CP</a></li>\n";
  } 

  if ($super) { 
    echo "<li><a href=\"index.php\">Series CP*</a></li>\n";
  } 

  if ($player == NULL) { 
    echo "<li class=\"last\"><a href=\"login.php\">Login</a></li>\n"; 
  } else { 
    echo "<li class=\"last\"><a href=\"logout.php\">Logout [{$player->name}]</a></li>\n"; 
  } 

  echo "</ul> </div>\n";
} 

function print_footer() { 
  echo "<div class=\"grid_10 prefix_1 suffix_1\"> <div id=\"gatherling_footer\" class=\"box\">"; 
  version_tagline(); 
  echo "</div> </div>";
  echo "<div class=\"clear\"></div>\n"; 
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
  print "Gatherling version 1.9.5 (\"The grade that you receive will be your last, WE SWEAR!\")";
  # print "Gatherling version 1.9.4 (\"We're gonna need some more FBI guys, I guess.\")";
  # print "Gatherling version 1.9.3 (\"This is the Ocean, silly, we're not the only two in here.\")";
  # print "Gatherling version 1.9.2 (\"So now you're the boss. You're the King of Bob.\")";
  # print "Gatherling version 1.9.1 (\"It's the United States of Don't Touch That Thing Right in Front of You.\")";
  # print "Gatherling version 1.9 (\"It's funny 'cause the squirrel got dead\")";
} 
