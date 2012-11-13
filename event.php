<?php session_start();
include 'lib.php';
include 'parsedci.php';

$verified_url = theme_file("images/verified.png");
$dot_url = theme_file("images/dot.png");
$drop_url = $CONFIG['base_url'] + 'event.php?action=undrop';

$js = <<<EOD

function updatePlayerCount() {
  var players = $('tr.entry_row').length;
  $('#player_count').html(players + ' Registered Players');
}

function addPlayerRow(data) {
  if (!data.success) { return false; }
  var html = '<tr class="entry_row" id="entry_row_' + data.player + '"><td>';
  if (data.event_running) {
    html += '<input type="checkbox" name="dropplayer[]" value="' + data.player + '" />';
  }
  html += '</td><td>' + data.player + '</td>';
  html += '<td>';
  if (data.verified) {
    html += '<img src="$verified_url" alt="Verified" />';
  }
  html += '</td>';
  html += '<td align="center"><img src="$dot_url" alt="dot" /></td>';
  html += '<td><a class="create_deck_link" href="deck.php?player=' + data.player + '&event=' + event_name + '&mode=create">[Create Deck]</a></td>';
  html += '<td align="center"><input type="checkbox" name="delentries[]" value="' + data.player + '" /></td></tr>';
  $('input[name=newentry]').val("");
  $('#row_new_entry').before(html);
  $('#entry_row_' + data.player).find('td').wrapInner('<div style="display: none;" />').parent().find('td > div').slideDown(500, function() { var set = $(this); set.replaceWith(set.contents()); });
  updatePlayerCount();
}

function delPlayerRow(data) {
  if (!data.success) { return false; }
  $('#entry_row_' + data.player).removeClass('entry_row').find('td').wrapInner('<div style="display: block;" />').parent().find('td > div').slideUp(500, function() { $(this).parent().parent().remove(); });
  updatePlayerCount();
}

function dropPlayer(data) {
  if (!data.success) { return false; }
  $('#entry_row_' + data.player).find('input[name=dropplayer[]]').parent().html('&#x2690; ' + data.round + ' <a href=\"{$CONFIG['base_url']}event.php?player=' + data.player + '&action=undrop&name=' + data.eventname + '\">(undo)</a>');
}

function updateRegistration() {
  event_name = $('input[name=name]').val();
  newentry_name = $('input[name=newentry]').val();
  if (newentry_name != "") {
    $.ajax({url: 'ajax.php?event=' + event_name
                       + '&addplayer=' + newentry_name,
                       success: addPlayerRow});
  }
  $('input[name=delentries[]]').each(function(x, e) {
    if (e.checked) {
      $.ajax({url: 'ajax.php?event=' + event_name + '&delplayer=' + e.value,
              success: delPlayerRow});
    }
  });
  $('input[name=dropplayer[]]').each(function(x, e) {
    if (e.checked) {
      $.ajax({url: 'ajax.php?event=' + event_name + '&dropplayer=' + e.value,
             success: dropPlayer});
    }
  });
  return false;
}

$(document).ready(function() {
  $('#update_reg').click(updateRegistration);
});
EOD;

print_header("Event Host Control Panel", $js);
?>
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Host Control Panel </div>

<?php
if (Player::isLoggedIn()) {
  content();
} else {
  linkToLogin();
}
?>

<div class="clear"></div>
</div> </div>

<?php print_footer(); ?>

<?php

function mode_is($str) {
  global $_GET, $_POST;
  $mode = NULL;
  if (isset($_GET['mode']) and $_GET['mode'] != '') { $mode = $_GET['mode']; }
  if (isset($_POST['mode']) and $_POST['mode'] != '') { $mode = $_POST['mode']; }

  return (bool)(strcmp($mode, $str) == 0);
}

function content() {
  $event = NULL;
  // Prevent surplufous warnings.   TODO: fix the code so we don't try to access these if unset.
  if (!isset($_GET['action'])) {$_GET['action'] = '';}
  if (!isset($_POST['mode'])) {$_POST['mode'] = '';}
  if (!isset($_GET['mode'])) {$_GET['mode'] = '';}
  if (!isset($_GET['series'])) {$_GET['series'] = '';}
  if (!isset($_GET['season'])) {$_GET['season'] = '';}

  $player = Player::getSessionPlayer();

  if ((isset($_GET['name']) || isset($_POST['name'])) && !mode_is("Create New Event")) {
    if (isset($_POST['name'])) {
      $eventname = $_POST['name'];
    } else {
      $eventname = $_GET['name'];
    }
    $event = new Event($eventname);
  }

  // if -- can create new events
  if (Player::getSessionPlayer()->isSteward()) {
    if (mode_is("Create New Event")) {
      if (isset($_POST['insert'])) {
        $event = insertEvent();
        eventForm($event);
      } else {
        eventForm();
      }
      return;
    } elseif (mode_is("Create Next Event")) {
      $oldevent = new Event($_POST['name']);
      $newevent = new Event("");
      $newevent->season = $oldevent->season;
      $newevent->number = $oldevent->number + 1;
      $newevent->format = $oldevent->format;
      $newevent->start = strftime("%Y-%m-%d %H:00:00", strtotime($oldevent->start) + (86400 * 7));
      $newevent->kvalue = $oldevent->kvalue;
      $newevent->finalized = 0;
      $newevent->player_editdecks = 1;
      $newevent->player_reportable = $oldevent->player_reportable;
      $newevent->prereg_allowed = $oldevent->prereg_allowed;

      $newevent->series = $oldevent->series;
      $newevent->host = $oldevent->host;
      $newevent->cohost = $oldevent->cohost;

      $newevent->mainrounds = $oldevent->mainrounds;
      $newevent->mainstruct = $oldevent->mainstruct;
      $newevent->finalrounds = $oldevent->finalrounds;
      $newevent->finalstruct = $oldevent->finalstruct;

      $newevent->name = sprintf("%s %d.%02d",$newevent->series, $newevent->season, $newevent->number);

      eventForm($newevent, true);
      return;
    } else if (!isset($event)) {
      if (!isset($_POST['series'])) { $_POST['series'] = ''; }
      if (!isset($_POST['season'])) { $_POST['season'] = ''; }
      eventList($_POST['series'], $_POST['season']);
    }
  }

  if ($event && $event->authCheck($player)) {
    if (strcmp($_GET['action'], "undrop") == 0) {
      $event->undropPlayer($_GET['player']);
    }

    if (mode_is("Start Event")) {
      $event->active = 1;
      $event->save();
      $entries = $event->getEntries();
      Standings::startEvent($entries, $event->name);
      $event->pairCurrentRound();
    }

    if (mode_is("Recalculate Standings")) {
      $structure = $event->mainstruct;
      $event->recalculateScores($structure);
      Standings::updateStandings($event->name, $event->mainid, 1);
    }

    if (mode_is("Reset Event")) {
      $event->resetEvent();
    }

    if (mode_is("Delete Matches and Re-Pair Round")) {
      $event->repairRound();
    }

    if (mode_is("Reactivate Event")) {
      $event->active = 1;
      $event->player_editdecks = 1;
      $event->finalized = 0;
      $event->save();
    }

    if (mode_is("Assign Medals")) {
      $event->assignMedals();
    }

    if (mode_is("Parse DCI Files")) {
      dciInput();
    } elseif (mode_is("Parse DCIv3 Files")) {
      dci3Input();
    } elseif (mode_is("Auto-Input Event Data")) {
      autoInput();
    } elseif (mode_is("Update Registration")) {
      updateReg();
    } elseif (mode_is("Update Match Listing")) {
      updateMatches();
    } elseif (mode_is("Update Medals")) {
      updateMedals();
    } elseif (mode_is("Update Adjustments")) {
      updateAdjustments();
    } elseif (mode_is("Upload Trophy")) {
      if (insertTrophy()) {
        $event->hastrophy = 1;
      }
    } elseif (mode_is("Update Event Info")) {
      $event = updateEvent();
    }
    eventForm($event);
  } else {
    authFailed();
  }
}

function eventList($series = "", $season = "") {
  $db = Database::getConnection();
  $player = Player::getSessionPlayer();
  $playerSeries = $player->stewardsSeries();
  $seriesEscaped = array();
  foreach ($playerSeries as $oneSeries) {
    $seriesEscaped[] = $db->escape_string($oneSeries);
  }
  $seriesString = '"' . implode('","', $seriesEscaped) . '"';
  $query = "SELECT e.name AS name, e.format AS format,
    COUNT(DISTINCT n.player) AS players, e.host AS host, e.start AS start,
    e.finalized, e.cohost, e.series
    FROM events e
    LEFT OUTER JOIN entries AS n ON n.event = e.name
    WHERE (e.host = \"{$db->escape_string($player->name)}\"
           OR e.cohost = \"{$db->escape_string($player->name)}\"
           OR e.series IN (" . $seriesString . "))";
  if(isset($_GET['format']) && strcmp($_GET['format'], "") != 0) {
    $query = $query . " AND e.format=\"{$db->escape_string($_GET['format'])}\" ";
  }
  if(isset($_GET['series']) && strcmp($_GET['series'], "") != 0) {
    $query = $query . " AND e.series=\"{$db->escape_string($_GET['series'])}\" ";
  }
  if(isset($_GET['season']) && strcmp($_GET['season'], "") != 0) {
    $query = $query . " AND e.season=\"{$db->escape_string($_GET['season'])}\" ";
  }
  $query = $query . " GROUP BY e.name ORDER BY e.start DESC LIMIT 100";
  $result = $db->query($query);

  $seriesShown = array();
  $results = array();
  while ($thisEvent = $result->fetch_assoc()) {
    $results[] = $thisEvent;
    $seriesShown[] = $thisEvent['series'];
  }

  if (isset($_GET['series'] ) && $_GET['series'] != "") {
    $seriesShown = $playerSeries;
  } else {
    $seriesShown = array_unique($seriesShown);
  }

  echo "<form action=\"event.php\" method=\"get\">";
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  echo "<tr><td colspan=\"2\" align=\"center\"><b>Filters</td></tr>";
  echo "<tr><td>&nbsp;</td></tr>";
  echo "<tr><th>Format</th><td>";
  if (!isset($_GET['format'])) { $_GET['format'] = ''; }
  formatDropMenu($_GET['format'], 1);
  echo "</td></tr>";
  echo "<tr><th>Series</th><td>";
  Series::dropMenu($_GET['series'], 1, $seriesShown);
  echo "</td></tr>";
  echo "<tr><th>Season</th><td>";
  seasonDropMenu($_GET['season'], 1);
  echo "</td></tr>";
  echo "<tr><td>&nbsp;</td></tr>";
  echo "<tr><td colspan=\"2\" class=\"buttons\">";
  if (count($playerSeries) > 0) {
    echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\" />\n";
  }
  echo "<input type=\"submit\" name=\"mode\" value=\"Filter Events\" />\n";
  echo "</td></tr></table>";
  echo "<table style=\"border-width: 0px\" align=\"center\" cellpadding=\"3\">";
  echo "<tr><td colspan=\"5\">&nbsp;</td></tr>";
  echo "<tr><td><b>Event</td><td><b>Format</td>";
  echo "<td align=\"center\"><b>Players</td>";
  echo "<td><b>Host(s)</td>";
  echo "<td align=\"center\"><b>Finalized</td></tr>";

  foreach ($results as $thisEvent) {
    $dateStr = $thisEvent['start'];
    $dateArr = explode(" ", $dateStr);
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
    echo "<td align=\"center\">";
    if($thisEvent['finalized'] == 1) {echo "&#x2714;";}
    echo "</td>";
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
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  if ($event->start != NULL) {
    $date = $event->start;
    preg_match('/([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):([0-9]+):.*/', $date, $datearr);
    $year = $datearr[1];
    $month = $datearr[2];
    $day = $datearr[3];
    $hour = $datearr[4];
    $minutes = $datearr[5];
    echo "<tr><th>Currently Editing</th>";
    echo "<td><i>{$event->name}</i>";
    echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
    echo "</td>";
    echo "</tr><tr><td>&nbsp;</td><td>";
    $prevevent = $event->findPrev();
    if ($prevevent) {
      echo $prevevent->makeLink("&laquo; Previous");
    }
    $nextevent = $event->findNext();
    if ($nextevent) {
      if ($prevevent) {
        echo " | ";
      }
      echo $nextevent->makeLink("Next &raquo;");
    }
    echo "</td></tr>";
  } else {
    echo "<tr><th>Event Name</th>";
    echo "<td><input type=\"radio\" name=\"naming\" value=\"auto\" checked>";
    echo "Automatically name this event based on Series, Season, and Number.";
    echo "<br /><input type=\"radio\" name=\"naming\" value=\"custom\">";
    echo "Use a custom name: ";
    echo "<input type=\"text\" name=\"name\" value=\"{$event->name}\" ";
    echo "size=\"40\">";
    echo "</td></tr>";
    $year = strftime('Y', time());
    $month = strftime('B', time());
    $day = strftime('Y', time());
    $hour = strftime('H', time());
    $minutes = strftime('M', time());
  }
  echo "<tr><th>Date & Time</th><td>";
  numDropMenu("year", "- Year -", 2012, $year, 2005);
  monthDropMenu($month);
  numDropMenu("day", "- Day- ", 31, $day, 1);
  timeDropMenu($hour, $minutes);
  echo "</td></tr>";
  echo "<tr><th>Series</th><td>";
  $seriesList = Player::getSessionPlayer()->stewardsSeries();
  $seriesList[] = "Other";
  Series::dropMenu($event->series, 0, $seriesList);
  echo "</td></tr>";
  echo "<tr><th>Season</th><td>";
  seasonDropMenu($event->season);
  echo "</td></tr>";
  echo "<tr><th>Number</th><td>";
  numDropMenu("number", "- Event Number -", Event::largestEventNum() + 5, $event->number, 0, "Custom");
  echo "</td><tr>";
  echo "<tr><th>Format</th><td>";
  formatDropMenu($event->format);
  echo "</td></tr>";
  echo "<tr><th>K-Value</th><td>";
  kValueDropMenu($event->kvalue);
  echo "</td></tr>";
  echo "<tr><th>Host/Cohost</th><td>";
  stringField("host", $event->host, 20);
  echo "&nbsp;/&nbsp;";
  stringField("cohost", $event->cohost, 20);
  echo "</td></tr>";
  echo "<tr><th>Event Thread URL</th><td>";
  stringField("threadurl", $event->threadurl, 60);
  echo "</td></tr>";
  echo "<tr><th>Metagame URL</th><td>";
  stringField("metaurl", $event->metaurl, 60);
  echo "</td></tr>";
  echo "<tr><th>Report URL</th><td>";
  stringField("reporturl", $event->reporturl, 60);
  echo "</td></tr>";
  echo "<tr><th>Main Event Structure</th><td>";
  numDropMenu("mainrounds", "- No. of Rounds -", 10, $event->mainrounds, 1);
  echo " rounds of ";
  structDropMenu("mainstruct", $event->mainstruct);
  echo "</td></tr>";
  echo "<tr><th>Finals Structure</th><td>";
  numDropMenu("finalrounds", "- No. of Rounds -", 10, $event->finalrounds, 0);
  echo " rounds of ";
  structDropMenu("finalstruct", $event->finalstruct);
  echo "</td></tr>";
  echo "<tr><th>Allow Pre-Registration</th>";
  echo "<td><input type=\"checkbox\" name=\"prereg_allowed\" value=\"1\" ";
  if ($event->prereg_allowed == 1) {echo "checked=\"yes\" ";}
  echo "/></td></tr>";

  echo "<tr><th>Players Can Report Results</th>";
  echo "<td><input type=\"checkbox\" name=\"player_reportable\" value=\"1\" ";
  if ($event->player_reportable == 1) {echo "checked=\"yes\" ";}
  echo "/></td></tr>";

  if($edit == 0) {
    echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td colspan=\"2\" class=\"buttons\">";
    echo "<input type=\"submit\" name=\"mode\" value=\"Create New Event\">";
    echo "<input type=\"hidden\" name=\"insert\" value=\"1\">";
    echo "</td></tr>";
  } else {
    echo "<tr><th>Players Can Update Decks</th>";
    echo "<td><input type=\"checkbox\" name=\"player_editdecks\" value=\"1\" ";
    if ($event->player_editdecks == 1) { echo "checked=\"yes\" "; }
    echo "/></td></tr>";
    echo "<tr><th>Finalize Event</th>";
    echo "<td><input type=\"checkbox\" name=\"finalized\" value=\"1\" ";
    if ($event->finalized == 1) { echo "checked=\"yes\" "; }
    echo "/></td></tr>";
    trophyField($event);
    echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td colspan=\"2\" class=\"buttons\">";
    echo " <input type=\"submit\" name=\"mode\" value=\"Update Event Info\" />";
    $nexteventname = sprintf("%s %d.%02d",$event->series, $event->season, $event->number + 1);
    if (!Event::exists($nexteventname)) {
      echo " <input type=\"submit\" name=\"mode\" value=\"Create Next Event\" />";
    }
    echo "<input type=\"hidden\" name=\"update\" value=\"1\" />";
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
    echo "<table style=\"border-width: 0px\" align=\"center\">";
    $view = "reg";
    $view = isset($_GET['view']) ? $_GET['view'] : $view;
    $view = isset($_POST['view']) ? $_POST['view'] : $view;
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    controlPanel($event, $view);
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "</table>";
    if (strcmp($view, "reg") == 0) {
      playerList($event);
    } elseif (strcmp($view, "match") == 0) {
      matchList($event);
    } elseif (strcmp($view,"standings") == 0) {
      standingsList($event);
    } elseif (strcmp($view, "medal") == 0) {
      medalList($event);
    } elseif (strcmp($view, "autoinput") == 0) {
      autoInputForm($event);
    } elseif (strcmp($view, "fileinput") == 0) {
      fileInputForm($event);
      file3InputForm($event);
    } elseif (strcmp($view, "points_adj") == 0) {
      pointsAdjustmentForm($event);
    }
  }
  echo "</table>";
}

function playerList($event) {
  $entries = $event->getEntries();
  $numentries = count($entries);
  echo "<form action=\"event.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\" />";
  echo "<table id=\"event_player_list\">";
  echo "<tr><th colspan=\"6\" align=\"center\" id=\"player_count\">";
  echo "{$numentries} Registered Players";
  if ($event->active) {
    $active = $event->getActivePlayers();
    $numactive = count($active);
    echo " - {$numactive} still playing";
  }
  echo "</th></tr><tr><td>&nbsp;</td><tr>";
  echo "<input type=\"hidden\" name=\"view\" value=\"reg\">";
  if ($numentries > 0) {
    echo "<tr>";
    echo "<th>";
    if ($event->active == 1) { echo "Drop"; }
    echo "</th>";
    echo "<th style=\"text-align: left\" colspan=\"2\">Player</th><th>Medal</th>";
    echo "<th style=\"text-align: center\">Deck</th><th>Delete</th></tr>";
  } else {
    echo "<tr><td align=\"center\" colspan=\"5\"><i>";
    echo "No players are currently registered for this event.</i></td></tr>";
  }

  foreach ($entries as $entry) {
    echo "<tr class=\"entry_row\" id=\"entry_row_{$entry->player->name}\">";
    // Show drop box if event is active.
    echo "<td align=\"center\">";
    if ($event->active == 1){
      if ($entry->dropped()) {
        echo "&#x2690; {$entry->drop_round} <a href=\"event.php?player=".$entry->player->name."&action=undrop&name=".$event->name."\">(undo)</a>";
      } else {
        echo "<input type=\"checkbox\" name=\"dropplayer[]\" value=\"{$entry->player->name}\" />";
      }
    }
    echo "</td><td>";
    echo "{$entry->player->name}";
    echo "</td><td>";
    if ($entry->player->verified) {
      echo image_tag("verified.png", array("alt" => "Verified", "title" => "Player Verified on MTGO"));
    }
    echo "</td>";
    if(strcmp("", $entry->medal) != 0) {
      $img = medalImgStr($entry->medal);
    }
    echo "<td align=\"center\">$img</td>";
    if ($entry->deck) {
      $decklink = $entry->deck->linkTo();
    } else {
      $decklink = $entry->createDeckLink();
    }
    $rstar = "<font color=\"#FF0000\">*</font>";
    if ($entry->deck != NULL) {
      if($entry->deck->cardcount < 60) {$decklink .= $rstar;}
      if($entry->deck->cardcount < 6) {$decklink .= $rstar;}
    }
    echo "<td>$decklink</td>";
    echo "<td align=\"center\">";
    if ($entry->canDelete()) {
      echo "<input type=\"checkbox\" name=\"delentries[]\" value=\"{$entry->player->name}\" />";
    } else {
      not_allowed("Can't delete player, they have matches recorded.");
    }
    echo "</td></tr>";
  }
  echo "<tr id=\"row_new_entry\"><td>Add:</td><td>";
  stringField("newentry", "", 20);
  echo "</td><td>&nbsp;</td><td colspan=\"2\">";
  echo "<input id=\"update_reg\" type=\"submit\" name=\"mode\" value=\"Update Registration\" />";
  echo "</td></tr>";
  if ($event->active == 1) {
    echo "<tr><td colspan=\"6\">";
    echo "<p class=\"squeeze\">Players added after the event has started:</p>";
    echo "<ul>";
    echo "<li>receive 0 points for any rounds already started</li>";
    echo "<li>will be paired with the next round begins</li>";
    echo "</ul>";
    echo "</td></tr>";
  }
  echo "</table>";
  echo "</form>";

  echo "<div id=\"event_run_actions\">";
  echo "<form action=\"event.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"view\" value=\"reg\" />";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\" />";
  echo "<p>Round Actions</p>";
  if ($event->active == 0 && $event->finalized == 0) {
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Start Event\" />";
  } else if ($event->active == 1) {
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Recalculate Standings\" />";
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Reset Event\" />";
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Delete Matches and Re-Pair Round\" />";
  } else {
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Reactivate Event\" />";
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Recalculate Standings\" />";
    echo "<input id=\"start_event\" type=\"submit\" name=\"mode\" value=\"Assign Medals\" />";
  }
  echo "</form>";
  echo "</div>";
}

function pointsAdjustmentForm($event) {
  $entries = $event->getEntries();

  // Start a new form
  echo "<form action=\"event.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\" />";
  echo "<table style=\"border-width: 0px\" align=\"center\">";
  echo "<input type=\"hidden\" name=\"view\" value=\"points_adj\">";
  echo "<tr class=\"top\"> <th> Player </th> <th> </th> <th> Deck </th> <th> Points <br /> Adj. </th> <th> Reason </th> </tr>";
  foreach ($entries as $entry) {
    $name = $entry->player->name;
    $adjustment = $event->getSeasonPointAdjustment($name);
    echo "<tr> <td> {$name} </td>";
    if ($entry->medal != "") {
      $img = medalImgStr($entry->medal);
      echo "<td> {$img} </td>";
    } else {
      echo "<td> </td>";
    }
    if ($entry->deck != NULL) {
      $img = image_tag("verified.png", array("alt" => "Verified", "title" => "Player posted deck"));
      echo "<td> {$img} </td>";
    } else {
      echo "<td> </td>";
    }
    if ($adjustment != NULL) {
      echo "<td style=\"text-align: center;\"> <input type=\"text\" style=\"width: 50px;\" name=\"adjustments[{$name}]\" value=\"{$adjustment['adjustment']}\" /> </td>";
      echo "<td> <input type=\"text\" style=\"width: 400px;\" name=\"reasons[{$name}]\" value=\"{$adjustment['reason']}\" /> </td>";
    } else {
      echo "<td style=\"text-align: center;\"> <input type=\"text\" style=\"width: 50px;\" name=\"adjustments[{$name}]\" value=\"\" /> </td>";
      echo "<td> <input type=\"text\" style=\"width: 400px;\" name=\"reasons[{$name}]\" value=\"\" /> </td>";
    }
    echo "</tr>";
  }
  echo "<tr> <td colspan=\"3\" class=\"buttons\"> ";
  echo "<input type=\"submit\" name=\"mode\" value=\"Update Adjustments\" />";
  echo "</td> </tr> </table> </form>";

}

function printUnverifiedPlayerCell($match, $playername) {
  echo "<td><input type=\"checkbox\" name=\"dropplayer[]\" value=\"{$playername}\">";
  if (($match->getPlayerWins($playername) > 0) || ($match->getPlayerLosses($playername) > 0)) {
    if ($match->getPlayerWins($playername) > $match->getPlayerLosses($playername)) {
      $matchresult = "W ";
    } else {
      $matchresult = "L ";
    }
    echo "<span class=\"match_{$match->verification}\">{$playername}</span> ({$matchresult}{$match->getPlayerWins($playername)}-{$match->getPlayerLosses($playername)}) </td>";
  } else {
    echo "{$playername}</td>";
  }
}

function matchList($event) {
  $matches = $event->getMatches();
  // Prevent warnings in php output.  TODO: make this not needed.
  if (!isset($_POST['newmatchround'])) {$_POST['newmatchround'] = '';}

  echo "<p style=\"text-align: center\"><b>Match List</b><br />";
  echo "<i>* denotes a playoff/finals match.</i><br />";
  echo "<i>To drop a player while entering match results, select the";
  echo " check box next to the players name.</i></p>";
  // Quick links to rounds
  echo "<p style=\"text-align: center\">";
  for ($r = 1; $r < $event->current_round; $r++) {
    echo "<a href=\"event.php?view=match&name={$event->name}#round-{$r}\">Round {$r}</a> ";
  }
  echo "</p>";
  // Start a new form
  echo "<form action=\"event.php\" method=\"post\" enctype=\"multipart/form-data\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
  echo "<input type=\"hidden\" name=\"eventname\" value=\"{$event->name}\">";
  echo "<input type=\"hidden\" name=\"view\" value=\"match\">";
  echo "<table align=\"center\" style=\"border-width: 0px;\">";
  if (count($matches) > 0) {
    echo "<tr class=\"lefted\"><th style=\"text-align: center; padding-right: 10px;\">Round</th><th>Player A</th>";
    echo "<th>Result</th>";
    echo "<th>Player B</th>";
    echo "<th style=\"text-align: center;\"><b>Delete</th></tr>";
  } else {
    echo "<tr><td align=\"center\" colspan=\"5\"><i>";
    echo "There are no matches listed for this event.</td></tr>";
  }
  $first = 1;
  $rndadd = 0;
  $thisround = 0;
  $ezypaste = "";
  $playersInMatches = array();
  foreach ($matches as $match) {
    if ($first && $match->timing == 1) {
      $rndadd = $match->rounds;
    }
    $first = 0;
    if ($match->timing == 2) {
      $printrnd = $match->round + $rndadd;
    } else { 
      $printrnd = $match->round;
    }
    $printplr = $match->getWinner();
    if (is_null($printplr)) {
      $printplr = 'Database Error';
    }
    // TODO: will need to add some code here for byes.
    $star = ($match->timing > 1) ? "*" : "";
    if ($printrnd != $thisround) {
      $thisround = $printrnd;
      $ezypaste = "/me Pairings for Round {$thisround}<br />";
      $playersInMatches = array();
      $extraRoundTitle = "";
      if ($match->timing > 1) {
        $extraRoundTitle = "(Finals Round {$match->round})";
      }
      echo "<tr><td align=\"center\" colspan=\"7\" style=\"background-color: PaleGreen;\"> <a name=\"round-{$thisround}\"></a>ROUND {$thisround} {$extraRoundTitle} </td></tr>";
    }
    echo "<tr><td align=\"center\">$printrnd$star</td>";
    $playersInMatches[] = $match->playera;
    $playersInMatches[] = $match->playerb;
    if (strcasecmp($match->verification, 'verified') != 0 && $event->finalized == 0) {
      $ezypaste .= "/me {$match->playera} vs. {$match->playerb}<br />";
      printUnverifiedPlayerCell($match, $match->playera);
      echo "<td>";
      echo "<input type=\"hidden\" name=\"hostupdatesmatches[]\" value=\"{$match->id}\">";
      resultDropMenu("matchresult[]");
      echo "</td>";
      printUnverifiedPlayerCell($match, $match->playerb);
    } else {
      $playerawins = $match->getPlayerWins($match->playera);
      $playerbwins = $match->getPlayerWins($match->playerb);
      echo "<td class=\"match_{$match->verification}\">{$match->playera}</td>";
      if ($match->playera == $match->playerb) {
        $ezypaste .= "/me {$match->playera} has the BYE<br />";
        echo "<td>BYE</td>";
        echo "<td></td>";
      } else {
        echo "<td>{$playerawins}-{$playerbwins}</td>";
        $ezypaste .= "/me {$match->playera} {$playerawins}-{$playerbwins} {$match->playerb}<br />";
        echo "<td class=\"match_{$match->verification}\">{$match->playerb}</td>";
      }
    }
    echo "<td align=\"center\">";
    echo "<input type=\"checkbox\" name=\"matchdelete[]\" ";
    echo "value=\"{$match->id}\"></td></tr>";
  }
  $ezypaste .= "/me Good luck everyone!<br />";
  echo "<tr><td>&nbsp;</td></tr>";
  if ($event->active) {
    echo "<tr><td align=\"right\" colspan=\"3\"><b>Add Pairing</b></td>";
    echo "<input type=\"hidden\" name=\"newmatchround\" value=\"{$event->current_round}\">"; 
    echo "<input type=\"hidden\" name=\"newmatchresult\" value=\"P\">"; 
    echo "<td colspan=\"4\">";
    playerDropMenu($event, "A", "\n", $playersInMatches);
    echo " vs ";
    playerDropMenu($event, "B", "\n", $playersInMatches);
    echo "</td></tr>";
    echo "<tr><td align=\"right\" colspan=\"3\"><b>Award Bye</b></td>";
    echo "<td colspan=\"4\">";
    playerByeMenu($event, "\n", $playersInMatches);
    echo "</td></tr>";
  } else {
    echo "<tr><td align=\"center\" colspan=\"7\">";
    echo "<b>Add a Match</b></td></tr>";
    echo "<tr><td align=\"center\" colspan=\"7\">";
    roundDropMenu($event, $_POST['newmatchround']);
    playerDropMenu($event, "A");
    resultDropMenu("newmatchresult", array("P" => "In Progress", "BYE" => "BYE"));
    playerDropMenu($event, "B");
    echo "</td></tr>";
  }
  echo "<tr><td>&nbsp;</td></tr>";
  echo "<tr><td align=\"center\" colspan=\"7\">";
  echo "<input type=\"submit\" name=\"mode\" ";
  echo "value=\"Update Match Listing\"></td></tr>";
  echo "</form>";
  echo "</table>";
  echo "<p>Paste stuff:<br />";
  echo "<code>{$ezypaste}</code></p>";
}

function standingsList($event) {
  Standings::printEventStandings($event->name, Player::loginName());
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
  // Start a new form
  echo "<form action=\"event.php\" method=\"post\" ";
  echo "enctype=\"multipart/form-data\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
  echo "<table style=\"border-width: 0px\" align=\"center\">";

  echo "<tr><td colspan=\"2\">";
  echo "<input type=\"hidden\" name=\"view\" value=\"reg\">";
  echo "<table align=\"center\" style=\"border-width: 0px;\">";
  echo "<tr><td align=\"center\" colspan=\"2\"><b>Medals</td></tr>";
  echo "<tr><td colspan=\"2\" width=200>&nbsp;</td></tr>";
  echo "<tr><td align=\"center\"><b>Medal</td>";
  echo "<td align=\"center\"><b>Player</td></tr>";
  echo "<tr><td align=\"center\">";
  echo image_tag("1st.png") . "</td>";
  echo "<td align=\"center\">";
  playerDropMenu($event, "1", $def1);
  echo "</td></tr>";
  echo "<tr><td align=\"center\">";
  echo image_tag("2nd.png") ."</td>";
  echo "<td align=\"center\">";
  playerDropMenu($event, "2", $def2);
  echo "</td></tr>";
  for($i = 3; $i < 5; $i++) {
    echo "<tr><td align=\"center\">";
    echo image_tag("t4.png") . "</td>";
    echo "<td align=\"center\">";
    playerDropMenu($event, "$i", $def4[$i-3]);
    echo "</td></tr>";
  }
  for($i = 5; $i < 9; $i++) {
    echo "<tr><td align=\"center\">";
    echo image_tag("t8.png") . "</td>";
    echo "<td align=\"center\">";
    playerDropMenu($event, "$i", $def8[$i-5]);
    echo "</td></tr>";
  }
  echo "<tr><td>&nbsp;</td></tr>";
  echo "<tr><td colspan=\"2\" align=\"center\">";
  echo "<input type=\"submit\" name=\"mode\" value=\"Update Medals\">";
  echo "</form>";
  echo "</td></tr>";
  echo "</table>";
  echo "</td></tr>";
  echo "</table>";
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
  $event->start = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00";

  if (strcmp($_POST['naming'], "auto") == 0) {
    $event->name = sprintf("%s %d.%02d",$_POST['series'], $_POST['season'],$_POST['number']);
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

  if (!isset($_POST['prereg_allowed'])) {
    $event->prereg_allowed = 0;
  } else {
    $event->prereg_allowed = $_POST['prereg_allowed'];
  }

  if (!isset($_POST['player_reportable'])) {
    $event->player_reportable = 0;
  } else {
    $event->player_reportable = $_POST['player_reportable'];
  }

  if($_POST['mainrounds'] == "") {$_POST['mainrounds'] = 3;}
  if($_POST['mainstruct'] == "") {$_POST['mainstruct'] = "Swiss";}
  $event->mainrounds = $_POST['mainrounds'];
  $event->mainstruct = $_POST['mainstruct'];
  if($_POST['finalrounds'] == "") {$_POST['finalrounds'] = 3;}
  if($_POST['finalstruct'] == "") {$_POST['finalstruct'] = "Single Elimination";}
  $event->finalrounds = $_POST['finalrounds'];
  $event->finalstruct = $_POST['finalstruct'];

  $event->save();

  if (strcmp($_POST['host'], Player::loginName()) != 0) {
    $event->addSteward(Player::loginName());
  }

  return $event;
}

function updateEvent() {
  $event = new Event($_POST['name']);
  $event->start = "{$_POST['year']}-{$_POST['month']}-{$_POST['day']} {$_POST['hour']}:00:00";
  $event->finalized = $_POST['finalized'] == 1 ? 1 : 0;
  $event->player_editdecks = $_POST['player_editdecks'] == 1 ? 1 : 0;
  $event->prereg_allowed = $_POST['prereg_allowed'] == 1 ? 1 : 0;
  $event->player_reportable = $_POST['player_reportable'] == 1 ? 1 : 0;

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
    echo "<img src=\"displayTrophy.php?event={$event->name}\" alt=\"Trophy\" /></td></tr>";
  }
  echo "<tr><th>Trophy Image</th><td>";
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

    $db = Database::getPDOConnection();
    $stmt = $db->prepare("DELETE FROM trophies WHERE event = ?");
    $stmt->bindParam(1, $event, PDO::PARAM_STR);
    $stmt->execute() or die($stmt->errorCode());

    $stmt = $db->prepare("INSERT INTO trophies(event, size, type, image)
      VALUES(?, ?, ?, ?)");
    $stmt->bindParam(1, $event, PDO::PARAM_STR);
    $stmt->bindParam(2, $size, PDO::PARAM_INT);
    $stmt->bindParam(3, $type, PDO::PARAM_STR);
    $stmt->bindParam(4, $f, PDO::PARAM_LOB);
    $stmt->execute() or die($stmt->errorCode());
    fclose($f);

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

function playerByeMenu($event, $def="\n", $skipPlayers = array()) {
  if ($event->active) {
    $playernames = $event->getActivePlayers();
  } else {
    $playernames = $event->getPlayers();
  }
  echo "<select name=\"newbyeplayer\">";
  if(strcmp("\n", $def) == 0) {
    echo "<option value=\"\">- Bye Player -</option>";
  } else {
    echo "<option value=\"\">- None -</option>";
  }
  foreach ($playernames as $player) {
    if (in_array($player, $skipPlayers)) {
      continue;
    }
    $selstr = (strcmp($player, $def) == 0) ? "selected" : "";
    echo "<option value=\"{$player}\" $selstr>";
    echo "{$player}</option>";
  }
  echo "</select>";
}

function playerDropMenu($event, $letter, $def="\n", $skipPlayers = array()) {
  if ($event->active) {
    $playernames = $event->getActivePlayers();
  } else {
    $playernames = $event->getPlayers();
  }
  echo "<select name=\"newmatchplayer$letter\">";
  if(strcmp("\n", $def) == 0) {
    echo "<option value=\"\">- Player $letter -</option>";
  } else {
    echo "<option value=\"\">- None -</option>";
  }
  foreach ($playernames as $player) {
    if (in_array($player, $skipPlayers)) {
      continue;
    }
    $selstr = (strcmp($player, $def) == 0) ? "selected" : "";
    echo "<option value=\"{$player}\" $selstr>";
    echo "{$player}</option>";
  }
  echo "</select>";
}

function roundDropMenu($event, $selected) {
  echo "<select name=\"newmatchround\">";
  echo "<option value=\"\">- Round -</option>";
  for($r = 1; $r <= ($event->mainrounds + $event->finalrounds); $r++) {
    $star = ($r > $event->mainrounds) ? "*" : "";
    echo "<option value=\"$r\"";
    if ($selected == $r) {
      echo " selected";
    }
    echo ">$r$star</option>";
  }
  echo "</select>";
}

function resultDropMenu($name = "newmatchresult", $extra_options = array()) {
  echo "<select name=\"{$name}\">";
  echo "<option value=\"\"></option>";
  echo "<option value=\"2-0\">2-0</option>";
  echo "<option value=\"2-1\">2-1</option>";
  echo "<option value=\"1-2\">1-2</option>";
  echo "<option value=\"0-2\">0-2</option>";
  echo "<option value=\"D\">Draw</option>";
  foreach ($extra_options as $value => $text) {
    echo "<option value=\"{$value}\">{$text}</option>";
  }
  echo "</select>";
}

function playerWinsDropMenu($player = "A", $new = false) {
  if ($new) {
    echo "<select name=\"newmatchplayer{$player}wins\">";
  } else {
    echo "<select name=\"player{$player}wins[]\">";
  }
  echo "<option value=\"\">- {$player} Wins -</option>";
  echo "<option value=\"2\">2 Wins</option>";
  echo "<option value=\"1\">1 Wins</option>";
  echo "<option value=\"0\">0 Wins</option>";
  echo "</select>";
}

function controlPanel($event, $cur = "") {
  $name = $event->name;
  echo "<tr><td colspan=\"2\" align=\"center\">";
  echo "<a href=\"event.php?name=$name&view=reg\">Registration</a>";
  echo " | <a href=\"event.php?name=$name&view=match\">Match Listing</a>";
  echo " | <a href=\"event.php?name=$name&view=standings\">Standings</a>";
  echo " | <a href=\"event.php?name=$name&view=medal\">Medals</a>";
  echo " | <a href=\"event.php?name=$name&view=autoinput\">Auto-Input</a>";
  echo " | <a href=\"event.php?name=$name&view=fileinput\">DCI-R File Input</a>";
  echo " | <a href=\"event.php?name=$name&view=points_adj\">Season Points Adj.</a>";
  echo "</td></tr>";
}

function updateReg() {
  $event = new Event($_POST['name']);
  if (isset($_POST['delentries'])) {
    foreach ($_POST['delentries'] as $playername) {
      $event->removeEntry($playername);
    }
  }
  if(isset($_POST['dropplayer'])) {
    foreach ($_POST['dropplayer'] as $playername) {
      $event->dropPlayer($playername);
    }
  }
  $event->addPlayer($_POST['newentry']);
}

function updateMatches() {
  $event = new Event($_POST['name']);
  if(isset($_POST['matchdelete'])) {
    foreach ($_POST['matchdelete'] as $matchid) {
      Match::destroy($matchid); 
    }
  }

  if (isset($_POST['dropplayer'])) {
    foreach ($_POST['dropplayer'] as $playername) {
      $event->dropPlayer($playername);
    }
  }

  if(isset($_POST['hostupdatesmatches'])) {
    for ($ndx = 0; $ndx < sizeof($_POST['hostupdatesmatches']); $ndx++) {
      $result = $_POST['matchresult'][$ndx];
      $resultForA="notset"; $resultForB="notset";
      if ($result == "2-0") {
        $resultForA = "W20"; $resultForB = "L20";
      } elseif ($result == "2-1") {
        $resultForA = "W21"; $resultForB = "L21";
      } elseif ($result == "1-2") {
        $resultForA = "L21"; $resultForB = "W21";
      } elseif ($result == "0-2") {
        $resultForA = "L20"; $resultForB = "W20";
      } elseif ($result == 'D') {
        // TODO: need to figure out how to enter a draw
      }

      if ((strcasecmp($resultForA, 'notset') != 0) && (strcasecmp($resultForB, 'notset') != 0)) {
        $matchid = $_POST['hostupdatesmatches'][$ndx];
        Match::saveReport($resultForA, $matchid, 'a');
        Match::saveReport($resultForB, $matchid, 'b');
      }
    }
  }

  if (isset($_POST['newmatchplayerA'])) { $pA = $_POST['newmatchplayerA']; } else {$pA = "";}
  if (isset($_POST['newmatchplayerB'])) { $pB = $_POST['newmatchplayerB']; } else {$pB = "";}
  if (isset($_POST['newmatchresult'])) {
    $res = $_POST['newmatchresult'];
    if ($res == "2-0") {
      $pAWins = 2; $pBWins = 0; $res = 'A';
    } elseif ($res == "2-1") {
      $pAWins = 2; $pBWins = 1; $res = 'A';
    } elseif ($res == "1-2") {
      $pAWins = 1; $pBWins = 2; $res = 'B';
    } elseif ($res == "0-2") {
      $pAWins = 0; $pBWins = 2; $res = 'B';
    } elseif ($res == 'D') {
      $pAWins = 0; $pBWins = 0; $res = 'D';
    }
  } else {
    $res = "";
  }
  if (isset($_POST['newmatchround'])) { $rnd = $_POST['newmatchround']; } else {$rnd = "";}

  if ((strcmp($pA, "") != 0) && (strcmp($pB, "") != 0) && (strcmp($res, "") != 0) && (strcmp($rnd, "") != 0)) {
    if ($res == "P") {
      $event->addPairing($pA, $pB, $rnd, $res);
    } else {
      $event->addMatch($pA, $pB, $rnd, $res, $pAWins, $pBWins);
    }
  }

  if (isset($_POST['newbyeplayer']) && (strcmp($_POST['newbyeplayer'], "") != 0)) {
    $p = $_POST['newbyeplayer'];
    $event->addMatch($p, $p, $rnd, 'BYE');
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

function updateAdjustments() {
  $name = $_POST['name'];
  $event = new Event($_POST['name']);

  $adjustments = $_POST['adjustments'];
  $reasons = $_POST['reasons'];

  foreach ($adjustments as $name => $points) {
    if ($points != "") {
      $event->setSeasonPointAdjustment($name, $points, $reasons[$name]);
    }
  }
}

function autoInputForm($event) {
  // Start a new form
  echo "<form action=\"event.php\" method=\"post\" ";
  echo "enctype=\"multipart/form-data\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
  echo "<table style=\"border-width: 0px\" align=\"center\">";

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
  echo "</form>";
  echo "</td></tr>";
  echo "</table>";
  echo "</td></tr>";
  echo "</table>";
}

function autoInput() {
  if (count($_POST['pairings']) == 0 ||
      strlen($_POST['pairings'][0]) == 0) {
    // No data.
    return;
  }
  $event = new Event($_POST['name']);
  $matches = matchesFromDCIPastes($event, $_POST['pairings'], $_POST['standings'], $_POST['finals'], $_POST['champion']);
  if ($matches == 0) {
    // If we didn't add any matches, we should go to the reg, not the matches
    $_POST['view'] = "reg";
  }
}


function authFailed() {
  echo "You are not permitted to make that change. Please contact the ";
  echo "event host to modify this event. If you <b>are</b> the event host, ";
  echo "or feel that you should have privilege to modify this event, you ";
  echo "should contact jamuraa on the forums.<br /><br />";
}

function fileInputForm($event) {
  // Start a new form
  echo "<form action=\"event.php\" method=\"post\" ";
  echo "enctype=\"multipart/form-data\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
  echo "<h3><center>DCI version 2</center></h3>";
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
  echo "</form>\n";
  echo "</td></tr></table>\n";
}

function file3InputForm($event) {
  // Start a new form
  echo "<form action=\"event.php\" method=\"post\" ";
  echo "enctype=\"multipart/form-data\">";
  echo "<input type=\"hidden\" name=\"name\" value=\"{$event->name}\">";
  echo "<h3><center>DCI version 3</center></h3>";
  echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
  echo "<tr><td><b>*302.dat</td><td>\n";
  echo "<input type=\"file\" name=\"302\" id=\"302\" size=40></td></tr>\n";
  echo "<tr><td><b>*303.dat&nbsp;</td><td>\n";
  echo "<input type=\"file\" name=\"303\" id=\"303\" size=40></td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\">\n";
  echo "<input type=\"submit\" name=\"mode\" value=\"Parse DCIv3 Files\">\n";
  echo "</form>\n";
  echo "</td></tr></table>\n";
}

function dciInput() {
  $reg = array();
  $event = new Event($_POST['name']);
  if($_FILES['delt']['size'] > 0) {
    $fileptr = fopen($_FILES['delt']['tmp_name'], 'r');
    $deltcontent = fread($fileptr, filesize($_FILES['delt']['tmp_name']));
    fclose($fileptr);
    $reg = dciregister($event, $deltcontent);
  }
  if($_FILES['kamp']['size'] > 0 && sizeof($reg) > 0) {
    $fileptr = fopen($_FILES['kamp']['tmp_name'], 'r');
    $kampcontent = fread($fileptr, filesize($_FILES['kamp']['tmp_name']));
    fclose($fileptr);
    dciinputmatches($event, $reg, $kampcontent);
  }
  if($_FILES['elim']['size'] > 0 && sizeof($reg) > 0) {
    $fileptr = fopen($_FILES['elim']['tmp_name'], 'r');
    $elimcontent = fread($fileptr, filesize($_FILES['elim']['tmp_name']));
    fclose($fileptr);
    dciinputplayoffs($event, $reg, $elimcontent);
  }
}

function dci3Input() {
  $reg = array();
  $event = new Event($_POST['name']);
  if ($_FILES['302']['size'] > 0) {
    $fileptr = fopen($_FILES['302']['tmp_name'], 'r');
    $regfilecontent = fread($fileptr, filesize($_FILES['302']['tmp_name']));
    fclose($fileptr);
    $reg = dci3register($event, $regfilecontent);
  }
  if ($_FILES['303']['size'] > 0) {
    $fileptr = fopen($_FILES['303']['tmp_name'], 'r');
    $matchfilecontent = fread($fileptr, filesize($_FILES['303']['tmp_name']));
    fclose($fileptr);
    dci3makematches($event, $matchfilecontent, $reg);
  }
}

?>
