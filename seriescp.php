<?php 
session_start();
include 'lib.php'; 

$hasError = false; 
$errormsg = "";

print_header("Series Control Panel");
?>

<div class="grid_10 suffix_1 prefix_1"> 
<div id="gatherling_main" class="box"> 
<div class="uppertitle"> Series Control Panel </div> 

<?php 
if (!Player::isLoggedIn()) { 
  linkToLogin();
} else { 
  do_page();
}
?>
<div class="clear"></div> 
</div></div>

<?php print_footer(); ?> 

<?php 

function do_page() { 
  $player_series = Player::getSessionPlayer()->StewardsSeries();
  if (count($player_series) == 0) { 
    printNoSeries(); 
    return;
  }
  
  if (isset($_POST['series'])) { 
    $_GET['series'] = $_POST['series']; 
  } 
  if (!isset($_GET['series'])) { 
    $_GET['series'] = $player_series[0];
  } 
  $active_series_name = $_GET['series'];

  handleActions();
  
  $view = "recent_events";
  
  if (isset($_GET['view'])) {$view = $_GET['view'];}
  if (isset($_POST['view'])) {$view = $_POST['view'];}  
  
  if ($view != "no_view") {
      if (count($player_series) > 1) { 
          printOrganizerSelect($player_series, $active_series_name); 
      } else { 
          echo "<center> Managing {$active_series_name} </center>";
      }
  }
  $active_series = new Series($active_series_name);
  $active_format = NULL;
  
  if (isset($_POST['format'])) {
      $active_format = $_POST['format'];
  } else {
      $active_format = "";
  }
  printError();
  
  if ($view != "no_view") {
      printSeriesForm($active_series);
      printLogoForm($active_series);
      seriesCPMenu($active_series);
  }
    
  if (!$active_series->authCheck(Player::loginName())) {
    printNoSeries(); 
    return;
  } else if ($view == "no_view") {
      ; // show nothing
  } else if ($view == "recent_events") {
    printRecentEventsTable($active_series);
  } elseif ($view == "points_management") {
    printPointsForm($active_series);
  } elseif ($view == "organizers") {
    printSeriesStewarsForm($active_series);
  } elseif ($view == "format_editor") {
    Format::formatEditor("seriescp.php", $active_format, $active_series_name);
  } elseif ($view == "trophies") {
      printMissingTrophies($active_series);
  } elseif ($view == "season_standings") {
    $active_series->seasonStandings($active_series, $active_series->currentSeason());
} 

function printMissingTrophies($series) {
  $recentEvents = $series->getRecentEvents(1000);
  $winningDeck = NULL;
  
  echo "<center><h3>Events Missing Trophies</h3></center>";
  echo "<table style=\"width: 75%;\"><tr><th>Event</th><th>Date</th><th>Winner</th><th>Deck</th></tr> ";
  
  if (count($recentEvents) == 0)  {
    echo "<tr><td colspan=\"4\" style=\"text-align: center; font-weight: bold;\">No Events Yet!</td></tr>";
  }
  
  foreach ($recentEvents as $event) {
      if (!$event->hastrophy) {
          echo "<tr><td style=\"text-align: center;\"><a href=\"event.php?name={$event->name}\">{$event->name}</a></td> ";
          $format = '%b %e';
          // Check for Windows to find and replace the %e 
          // modifier correctly
          if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
              $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
          }
          $timefmted = strftime($format,strtotime($event->start));
          echo "<td style=\"text-align: center;\">{$timefmted}</td>"; 
          $finalists = $event->getFinalists();
          foreach ($finalists as $finalist) {
              if ($finalist['medal'] == '1st') {
                  $winningPlayer = $finalist['player'];
                  $winningDeck = new Deck($finalist['deck']);
              }
          }
          if (!is_null($winningDeck)) {
              echo "<td style=\"text-align: center;\"><a href=\"./profile.php?player={$winningPlayer}\">{$winningPlayer}</a></td>";
              echo "<td style=\"text-align: center;\">{$winningDeck->linkTo()}</td>";
          } else {
              echo "<td></td><td></td>";
          }
          echo "</tr>";
      }
  }
  echo "</table>";    
}

function parseCards($text) {
  $cardarr = array();
  $lines = explode("\n", $text);
  foreach ($lines as $card) {
      // AE Litigation
      $card = preg_replace("/Æ/", "AE", $card);
      $card = preg_replace("/\306/", "AE", $card);
      $card = preg_replace("/ö/", "o", $card);
      $card = strtolower($card);
      $card = trim($card);
      if ($card != '') {
          $cardarr[] = $card;
      }
  }
  return $cardarr;
}

function printError() { 
  global $hasError;
  global $errormsg; 
  if ($hasError) { 
    echo "<div class=\"error\">{$errormsg}</div>";
  } 
} 

function printNoSeries() { 
  echo "<center>You're not a organizer of any series, so you can't use this page.<br />";
  echo "<a href=\"player.php\">Back to the Player Control Panel</a></center>";
} 

function printOrganizerSelect($player_series, $selected) { 
  echo "<center>";
  echo "<form action=\"seriescp.php\" method=\"get\">";
  echo "<select name=\"series\">";
  foreach ($player_series as $series) { 
    echo "<option value=\"{$series}\"";
    if ($series == $selected) { 
      echo " selected"; 
    } 
    echo ">{$series}</option>";
  } 
  echo "</select>"; 
  echo "<input type=\"submit\" value=\"Select Series\">";
  echo "</form>";
}

function printSeriesForm($series) { 
  echo "<form action=\"seriescp.php\" method=\"post\">";
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  # Active
  echo "<tr><th> Series is Active </th> <td> ";
  if ($series->active == 1) { 
    echo "<select name=\"isactive\"> <option value=\"1\" selected>Yes</option> <option value=\"0\">No</option></select>"; 
  } else { 
    echo "<select name=\"isactive\"> <option value=\"1\">Yes</option> <option value=\"0\" selected>No</option></select>"; 
  } 
  echo "</td></tr>";
  # Start day
  echo "<tr><th> Normal start day </th> <td> ";
  echo "<select name=\"start_day\">";
  $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
  foreach ($days as $dayofweek) { 
    if ($dayofweek == $series->start_day) {
      echo "<option selected>{$dayofweek}</option>";
    } else { 
      echo "<option>{$dayofweek}</option>";
    } 
  }
  echo "</select>";
  echo "</td></tr>";
  # Start time
  echo "<tr><th> Normal start time </th> <td> "; 
  $time_parts = explode(":", $series->start_time);
  timeDropMenu($time_parts[0], $time_parts[1]);
  echo "</td> </tr>";  
  # Pre-registration on by default?
  echo "<tr><th>Pre-Registration Default</th>";
  echo "<td><input type=\"checkbox\" value=\"1\" name=\"preregdefault\" ";
  if($series->prereg_default == 1) {
      echo "checked "; 
  }
  echo "/></td></tr>";
  
  # Submit button 
  echo "<tr><td colspan=\"2\" class=\"buttons\">";
  echo "<input type=\"submit\" name=\"action\" value=\"Update Series\" /> </td> </tr>";
  echo "</table> </form>";
}

function seriesCPMenu($series, $cur = "") {
  $name = $series->name;
  echo "<table><tr><td colspan=\"2\" align=\"center\">";
  echo "<a href=\"seriescp.php?series=$name&view=recent_events\">Recent Events</a>";
  echo " | <a href=\"seriescp.php?series=$name&view=points_management\">Season Points Management</a>";
  echo " | <a href=\"seriescp.php?series=$name&view=organizers\">Series Organizers</a>";
  echo " | <a href=\"seriescp.php?series=$name&view=format_editor\">Format Editor</a>";
  echo " | <a href=\"seriescp.php?series=$name&view=trophies\">Trophies</a>";
  echo " | <a href=\"seriescp.php?series=$name&view=season_standings\">Season Standings</a>";
  echo "</td></tr></table>";
}

function printSeriesOrganizersForm($series) { 
  $player = new Player(Player::loginName());
  echo "<form action=\"seriescp.php\" method=\"post\">"; 
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  echo "<h3> <center>Series Organizers</center> </h3>";
  echo "<p style=\"width: 75%; text-align: left;\">Series organizers can create new series events, manage any event in the series, and modify anything on this page.  Please add them with care as they could screw with anything related to your series including changing the logo and the time.  Only verified members can be series organizers. <br /> <b>If you just need a guest host, add them as the host to a specific event!</b> </p>";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
  echo "<tr><th style=\"text-align: center;\"> Player </th> <th style=\"width: 50px; text-align: center;\"> Delete </th> </tr>";
  foreach ($series->stewards as $organizer) { 
    echo "<tr> <td style=\"text-align: center;\"> {$organizer} </td>"; 
    echo "<td style=\"text-align: center; width: 50px; \"> <input type=\"checkbox\" value=\"{$organizer}\" name=\"delorganizers[]\" "; 
    if ($organizer == $player->loginName() && !$player->isSuper()) { 
      echo "disabled=\"yes\" ";
    }
    echo "/> </td> </tr>"; 
  } 
  echo "<tr> <td colspan=\"2\"> Add new: <input type=\"text\" name=\"addorganizer\" /> </td> </tr> "; 
  echo "<tr> <td colspan=\"2\" class=\"buttons\">"; 
  echo "<input type=\"hidden\" name=\"view\" value=\"organizers\" />";  
  echo "<input type=\"submit\" value=\"Update Organizers\" name=\"action\" /> </td> </tr> "; 
  echo "</table> ";
}

function printPointsRule($rule, $key, $rules, $formtype = 'text', $size = 4) { 
  echo "<tr> <th> {$rule} </th>";
  if ($formtype == 'text') { 
    echo "<td> <input type=\"text\" value=\"{$rules[$key]}\" name=\"new_rules[{$key}]\" size=\"{$size}\" /> </td> </tr> ";
  } else if ($formtype == 'checkbox') { 
    echo "<td> <input type=\"checkbox\" value=\"1\" name=\"new_rules[{$key}]\" ";
    if ($rules[$key] == 1) { 
      echo "checked "; 
    }
    echo " /> </td> </tr>";
  } else if ($formtype == 'format') { 
    echo "<td> ";
    formatDropMenu($rules[$key], 0, "new_rules[{$key}]");
    echo "</td> </tr>";
  } 
} 

function printPointsForm($series) { 
  $chosen_season = $series->currentSeason();
  if (isset($_GET['season'])) {$chosen_season = $_GET['season'];}
  if (isset($_POST['season'])) {$chosen_season = $_POST['season'];}
  
  echo "<h3><center> Season Points Management </center> </h3>";
  echo "<p style=\"width:75%; text-align: left;\">Here you can edit the way that season points are calculated for each player.  Choose the season that you want your point rules to be active for, and then put in the number of season points for each type of event.  You can adjust the points a player gets for each event individually as well, to take away points for not posting a deck for example or giving extra points for a tiebreaker-miss of top eight.</p>";
  echo "<p style=\"width:75%; text-align: left;\">Points are cumulative, so if someone gets the first place, they will get points for first place, participation, each round they played (in the main event, not the finals), for each match they won, lost, and got a bye, as well as the points for posting a decklist if they do post.  However, The first place to top 8 points are NOT added together, you only get points for where you end up (calculated by the medals).  An event winner doesn't get points for the second place, top 4 or top 8.</p>";
  echo "<p style=\"width:75%; text-align: left;\"><strong>Points are NOT counted for events with the 'Custom' number!</strong></p>";
  echo "<center>"; 
  echo "<form action=\"seriescp.php\">";
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  seasonDropMenu($chosen_season); 
  echo "<input type=\"hidden\" name=\"view\" value=\"points_management\" />";  
  echo "<input type=\"submit\" value=\"Choose Season\" />";
  echo "</form>";
  echo "</center>";
  $seasonrules = $series->getSeasonRules($chosen_season);
  echo "<form action=\"seriescp.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  echo "<input type=\"hidden\" name=\"season\" value=\"{$chosen_season}\" />";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
  echo "<tr> <th class=\"top\" colspan=\"2\"> Season {$chosen_season} Settings </th></tr>";
  printPointsRule("First Place", "first_pts", $seasonrules);
  printPointsRule("Second Place", "second_pts", $seasonrules);
  printPointsRule("Top 4", "semi_pts", $seasonrules);
  printPointsRule("Top 8", "quarter_pts", $seasonrules);
  printPointsRule("Participating", "participation_pts", $seasonrules);
  printPointsRule("Each round played", "rounds_pts", $seasonrules);
  printPointsRule("Match win", "win_pts", $seasonrules);
  printPointsRule("Match loss", "loss_pts", $seasonrules);
  printPointsRule("Round bye", "bye_pts", $seasonrules);
  printPointsRule("Posting a decklist", "decklist_pts", $seasonrules);
  printPointsRule("Require decklist for points", "must_decklist", $seasonrules, 'checkbox');
  printPointsRule("WORLDS Cutoff (players)", "cutoff_ord", $seasonrules);
  printPointsRule("Master Document Location", "master_link", $seasonrules, 'text', 50);
  printPointsRule("Season Format", "format", $seasonrules, 'format');
  echo "<tr> <td colspan=\"2\" class=\"buttons\">";
  echo "<input type=\"hidden\" name=\"view\" value=\"points_management\" />";
  echo "<input type=\"submit\" name=\"action\" value=\"Update Points Rules\" />";
  echo "</td> </table> </form>";
} 

function printLogoForm($series) {
  echo "<form action=\"seriescp.php\" method=\"post\" enctype=\"multipart/form-data\">";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  echo "<tr><th> Current Logo </th>";
  echo "<td>". Series::image_tag($series->name) . "</td> </tr>";
  echo "<tr><th> Upload New Logo </th>";
  echo "<td> <input type=\"file\" name=\"logo\" /> ";
  echo "<input type=\"submit\" name=\"action\" value=\"Change Logo\" /> </td> </tr>";
  echo "</table> </form> ";
}

function printRecentEventsTable($series) {
  $recentEvents = $series->getRecentEvents();
  echo "<center> <h3> Recent Events </h3> </center>";
  echo "<table style=\"width: 75%;\"> <tr> <th> Event </th> <th> Date </th> <th> Players </th> <th> Hosts </th> </tr> ";
  if (count($recentEvents) == 0)  {
    echo "<tr><td colspan=\"4\" style=\"text-align: center; font-weight: bold;\"> No Events Yet! </td> </tr>";
  } 
  foreach ($recentEvents as $event) {
    echo "<tr> <td> <a href=\"event.php?name={$event->name}\">{$event->name}</a> </td> ";
    $format = '%b %e';
    // Check for Windows to find and replace the %e 
    // modifier correctly
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
    }
    $timefmted = strftime($format,strtotime($event->start));
    echo "<td> {$timefmted} </td> <td style=\"text-align: center;\"> {$event->getPlayerCount()} </td>";
    echo "<td> {$event->host}";
    if ($event->cohost != "") {
        echo " / {$event->cohost} </td> ";
    } 
    echo "</tr>";
  }
  echo "</table>";
} 

function handleActions() {
  global $hasError;
  global $errormsg;

  if (!isset($_POST['series'])) { 
    return;
  } 
  $seriesname = $_POST['series'];
  $series = new Series($seriesname); 
  if (!$series) { 
    return;
  } 
  if (!$series->authCheck(Player::loginName())) {
    return;
  }
  if ($_POST['action'] == "Update Series") {
    $newactive = $_POST['isactive'];
    $newtime = $_POST['hour'];
    $newday = $_POST['start_day'];

    if (!isset($_POST['preregdefault'])) 
        $prereg = 0;
    else
        $prereg = $_POST['preregdefault'];

    $series = new Series($seriesname); 
    if ($series->authCheck(Player::loginName())) { 
      $series->active = $newactive; 
      $series->start_time = $newtime . ":00:00";
      $series->start_day = $newday;
      $series->prereg_default = $prereg;
      $series->save();
    } 
  } else if ($_POST['action'] == "Change Logo") {
    if ($_FILES['logo']['size'] > 0) {
      $file = $_FILES['logo'];
      $name = $file['name'];
      $tmp = $file['tmp_name']; 
      $size = $file['size']; 
      $type = $file['type'];

      $series->setLogo($tmp, $type, $size); 
    }
  } else if ($_POST['action'] == "Update Organizers") { 
    if (isset($_POST['delorganizers'])) { 
      $removals = $_POST['delorganizers']; 
      foreach ($removals as $deadorganizer) { 
        $series->removeSteward($deadorganizer);
      }
    }  
    if (!isset($_POST['addorganizer'])) {
      return; 
    } 
    $addition = $_POST['addorganizer']; 
    $addplayer = Player::findByName($addition);
    if ($addplayer == NULL) { 
      $hasError = true;
      $errormsg .= "Can't add {$addition} to Series Organizers, they don't exist!";
      return; 
    } 
    if ($addplayer->verified == 0 && Player::getSessionPlayer()->super == 0 ) { 
      $hasError = true;
      $errormsg .= "Can't add {$addplayer->name} to Series Organizers, they aren't a verified user!";
      return;
    }
    $series->addSteward($addplayer->name); 
  } else if ($_POST['action'] == "Update Points Rules") { 
    $new_rules = $_POST['new_rules']; 
    $series->setSeasonRules($_POST['season'], $new_rules); 
  } else if ($_POST['action'] == "Update Banlist") {
      $format = new Format($_POST['format']);

      if (isset($_POST['addbancard']) && $_POST['addbancard'] != '') {
          $cards = parseCards($_POST['addbancard']);
          if(count($cards) > 0) {
              foreach($cards as $card) {
                  $success = $format->insertCardIntoBanlist($card);
              }
              if(!$success) {
                  $hasError = true;
                  $errormsg .= "Can't add {$card} to Ban list, it is either not in the database, on the legal card list, or already on the ban list";
                  return; 
              }
          }
      }

      if (isset($_POST['delbancards'])) {
          $delBanCards = $_POST['delbancards'];
          foreach($delBanCards as $cardName){
              $success = $format->deleteCardFromBanlist($cardName);
              if(!$success) {
                  $hasError = true;
                  $errormsg .= "Can't delete {$cardName} from ban list";
                  return; 
              }
          }
      }
  }else if ($_POST['action'] == "Delete Entire Banlist") {
      $format = new Format($_POST['format']);
      $success = $format->deleteEntireBanlist(); // leave a message of success
  } else if ($_POST['action'] == "Update Legal List") {
      $format = new Format($_POST['format']);

      if (isset($_POST['addlegalcard']) && $_POST['addlegalcard'] != '') {
          $cards = parseCards($_POST['addlegalcard']);
          if(count($cards) > 0) {
              foreach($cards as $card) {
                  $success = $format->insertCardIntoLegallist($card);
              }
              if(!$success) {
                  $hasError = true;
                  $errormsg .= "Can't add {$card} to Legal list, it is either not in the database, already on the ban list, or already on the legal list";
                  return; 
              }
          }
      }

      if (isset($_POST['dellegalcards'])) {
          $dellegalCards = $_POST['dellegalcards'];
          foreach($dellegalCards as $cardName){
              $success = $format->deleteCardFromLegallist($cardName);
              if(!$success) {
                  $hasError = true;
                  $errormsg .= "Can't delete {$cardName} from legal list";
                  return; 
              }
          }
      }
  } else if ($_POST['action'] == "Delete Entire Legal List") {
      $format = new Format($_POST['format']);
      $success = $format->deleteEntireLegallist(); // leave a message of success
  } else if ($_POST['action'] == "Update Cardsets") {
      $format = new Format($_POST['format']);
      
      if(isset($_POST['cardsetname'])) {
          $cardsetName = $_POST['cardsetname'];
          if ($cardsetName != "Unclassified") {
              $format->insertNewLegalSet($cardsetName);
          }     
      }
      
      if(isset($_POST['delcardsetname'])) {
          $delcardsets = $_POST['delcardsetname'];
          foreach($delcardsets as $cardset) {
              $success = $format->deleteLegalCardSet($cardset);
              if(!$success) {
                  $hasError = true;
                  $errormsg .= "Can't delete {$cardset} from allowed cardsets";
                  return; 
              }
          }
      }      
  } else if($_POST['action'] == "Update Format") {
      $format = new Format($_POST['format']);

      if(isset($_POST['minmain'])) {$format->min_main_cards_allowed = $_POST['minmain'];}    
      if(isset($_POST['maxmain'])) {$format->max_main_cards_allowed = $_POST['maxmain'];}    
      if(isset($_POST['minside'])) {$format->min_side_cards_allowed = $_POST['minside'];}    
      if(isset($_POST['maxside'])) {$format->max_side_cards_allowed = $_POST['maxside'];}    

      if(isset($_POST['singleton']))        {$format->singleton = 1;}         else {$format->singleton = 0;}    
      if(isset($_POST['commander']))        {$format->commander = 1;}         else {$format->commander = 0;}
      if(isset($_POST['vanguard']))         {$format->vanguard = 1;}          else {$format->vanguard = 0;}
      if(isset($_POST['planechase']))       {$format->planechase = 1;}        else {$format->planechase = 0;}
      if(isset($_POST['prismatic']))        {$format->prismatic = 1;}         else {$format->prismatic = 0;}

      if(isset($_POST['allowcommons']))   {$format->allow_commons = 1;}   else {$format->allow_commons = 0;}    
      if(isset($_POST['allowuncommons'])) {$format->allow_uncommons = 1;} else {$format->allow_uncommons = 0;}
      if(isset($_POST['allowrares']))     {$format->allow_rares = 1;}     else {$format->allow_rares = 0;}
      if(isset($_POST['allowmythics']))   {$format->allow_mythics = 1;}   else {$format->allow_mythics = 0;}
      
      $format->save();
  } else if($_POST['action'] == "New") {
      echo "<form action=\"seriescp.php\" method=\"post\">";
      echo "<input type=\"hidden\" name=\"view\" value=\"no_view\" />";
      echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
      echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
      echo "<tr><td colspan=\"2\">New Format Name: <input type=\"text\" name=\"newformatname\" STYLE=\"width: 175px\"/></td></tr>";
      echo "<td colspan=\"2\" class=\"buttons\">";
      echo "<input type=\"submit\" value=\"Create New Format\" name =\"action\" /></td></tr>";
      echo"</table></form>";
  } else if($_POST['action'] == "Create New Format") {      
      $format = new Format("");
      $format->name = $_POST['newformatname'];
      $format->type = "Private";
      $format->series_name = $_POST['series'];
      $success = $format->save();
      if ($success) {
          echo "<center><h4>New Format $format->name Created Successfully!</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"format\" value=\"$format->name\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";
      } else {
          echo "<center><h4>New Format {$_POST['newformatname']} Could Not Be Created:-(</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";          
      }
  } else if($_POST['action'] == "Load") {
      echo "<center><h4>Load Format</h4></center>\n";
      echo "<form action=\"seriescp.php\" method=\"post\">"; 
      echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
      echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
      echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
      echo "<tr><td>";
      formatsDropMenu("Private", $_POST['series']);
      echo "</td>";
      echo "<td colspan=\"2\" class=\"buttons\">";
      echo "<input type=\"submit\" value=\"Load Format\" name =\"action\" /></td></tr>";
      echo"</table></form>";
  } else if($_POST['action'] == "Save As") {
      $format = new Format($_POST['format']);
      $oldformatname = $format->name;
      echo "<form action=\"seriescp.php\" method=\"post\">"; 
      echo "<input type=\"hidden\" name=\"view\" value=\"no_view\" />";
      echo "<input type=\"hidden\" name=\"oldformat\" value=\"$oldformatname\" />";
      echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
      echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
      echo "<tr><td colspan=\"2\">Save Format As... <input type=\"text\" name=\"newformat\" STYLE=\"width: 175px\"/></td></tr>";
      echo "<td colspan=\"2\" class=\"buttons\">";
      echo "<input type=\"submit\" value=\"Save\" name =\"action\" /></td></tr>";
      echo"</table></form>";
  } else if($_POST['action'] == "Save") {
      $format = new Format("");
      $format->name = $_POST['newformat'];
      $format->type = "Private";
      $format->series_name = $_POST['series'];
      $success = $format->saveAs($_POST['oldformat']);
      if ($success) {
          echo "<center><h4>New Format $format->name Saved Successfully!</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"format\" value=\"$format->name\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";
      } else {
          echo "<center><h4>New Format {$_POST['newformat']} Could Not Be Saved :-(</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"format\" value=\"{$_POST['oldformat']}\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";          
     }
  } else if($_POST['action'] == "Rename") {
      echo "<center><h4>Rename Format</h4></center>\n";
      echo "<form action=\"seriescp.php\" method=\"post\">"; 
      echo "<input type=\"hidden\" name=\"view\" value=\"no_view\" />";
      echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
      echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
      echo "<tr><td>";
      formatsDropMenu("Private", $_POST['series']);
      echo "</td>";
      echo "<td colspan=\"2\">Rename Format As... <input type=\"text\" name=\"newformat\" STYLE=\"width: 175px\"/></td></tr>";
      echo "<td colspan=\"2\" class=\"buttons\">";
      echo "<input type=\"submit\" value=\"Rename Format\" name =\"action\" /></td></tr>";
      echo"</table></form>";
  } else if($_POST['action'] == "Rename Format") {
      $format = new Format("");
      $format->name = $_POST['newformat'];
      $format->type = "Private";
      $format->series_name = $_POST['series'];
      $success = $format->rename($_POST['format']);
      if ($success) {
          echo "<center><h4>Format {$_POST['format']} Renamed as $format->name Successfully!</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"format\" value=\"$format->name\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";
      } else {
          echo "<center><h4>Format {$_POST['format']} Could Not Be Renamed :-(</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"format\" value=\"{$_POST['format']}\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";          
      }
  } else if($_POST['action'] == "Delete") {
      echo "<center><h4>Delete Format</h4></center>\n";
      echo "<form action=\"seriescp.php\" method=\"post\">"; 
      echo "<input type=\"hidden\" name=\"view\" value=\"no_view\" />";
      echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
      echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
      echo "<tr><td>";
      formatsDropMenu("Private", $_POST['series']);
      echo "</td>";
      echo "<td colspan=\"2\" class=\"buttons\">";
      echo "<input type=\"submit\" value=\"Delete Format\" name =\"action\" /></td></tr>";
      echo"</table></form>";
  } else if($_POST['action'] == "Delete Format") {
      $format = new Format($_POST['format']);
      $success = $format->delete();
      if ($success) {
          echo "<center><h4>Format {$_POST['format']} Deleted Successfully!</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";
      } else {
          echo "<center><h4>Could Not Delete {$_POST['format']}!</h4>";
          echo "<form action=\"seriescp.php\" method=\"post\">";
          echo "<input type=\"hidden\" name=\"format\" value=\"{$_POST['format']}\" />";
          echo "<input type=\"hidden\" name=\"series\" value=\"{$_POST['series']}\" />";
          echo "<input type=\"submit\" value=\"Continue\" name =\"action\" />";
          echo "</form></center>";          
      }      
  }
}
 
