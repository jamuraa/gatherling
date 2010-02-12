<?php 
session_start();
include 'lib.php'; 

$hasError = false; 
$errormsg = "";

print_header("PDCMagic.com | Gatherling | Series Control Panel");
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

<?php print_footer() ?> 

<?php 

function do_page() { 
  handleActions();
  $player_series = Player::getSessionPlayer()->stewardsSeries();
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
  if (count($player_series) > 1) { 
    printStewardSelect($player_series, $active_series_name); 
  } else { 
    echo "<center> Managing {$active_series} </center>";
  } 
  $active_series = new Series($active_series_name);
  printError();
  printSeriesForm($active_series);
  printLogoForm($active_series);
  printRecentEventsTable($active_series);
  printStewardsForm($active_series);
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

function printStewardSelect($player_series, $selected) { 
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
  hourDropMenu($series->start_time);
  echo "</td> </tr>";  
  
  # Submit button 
  echo "<tr><td colspan=\"2\" class=\"buttons\">";
  echo "<input type=\"submit\" name=\"action\" value=\"Update Series\" /> </td> </tr>";
  echo "</table> </form>";
}

function printStewardsForm($series) { 
  echo "<form action=\"seriescp.php\" method=\"post\">"; 
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  echo "<h3> <center>Series Organizers</center> </h3>";
  echo "<p style=\"width: 75%; text-align: left;\">Series organizers can create new series events, manage any event in the series, and modify anything on this page.  Please add them with care as they could screw with anything related to your series including changing the logo and the time.  Only verified members can be series organizers. <br /> <b>If you just need a guest host, add them as the host to a specific event!</b> </p>";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
  echo "<tr><th style=\"text-align: center;\"> Player </th> <th style=\"width: 50px; text-align: center;\"> Delete </th> </tr>";
  foreach ($series->stewards as $steward) { 
    echo "<tr> <td style=\"text-align: center;\"> {$steward} </td>"; 
    echo "<td style=\"text-align: center; width: 50px; \"> <input type=\"checkbox\" value=\"{$steward}\" name=\"delstewards[]\" "; 
    if ($steward == Player::loginName()) { 
      echo "disabled=\"yes\" ";
    }
    echo "/> </td> </tr>"; 
  } 
  echo "<tr> <td colspan=\"2\"> Add new: <input type=\"text\" name=\"addsteward\" /> </td> </tr> "; 
  echo "</table> ";
  echo "<input type=\"submit\" value=\"Update Organizers\" name=\"action\" /> ";
} 

function printLogoForm($series) { 
  echo "<form action=\"seriescp.php\" method=\"post\" enctype=\"multipart/form-data\">";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
  echo "<input type=\"hidden\" name=\"series\" value=\"{$series->name}\" />";
  echo "<tr><th> Current Logo </th>";
  echo "<td> <img src=\"displaySeries.php?series={$series->name}\" /> </td> </tr>";
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
    $timefmted = strftime("%b %e", strtotime($event->start)); 
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
    $series = new Series($seriesname); 
    if ($series->authCheck(Player::loginName())) { 
      $series->active = $newactive; 
      $series->start_time = $newtime . ":00:00";
      $series->start_day = $newday;
      $series->save();
    } 
  } else if ($_POST['action'] == "Change Logo") {
    if ($_FILES['logo']['size'] > 0) {
      $file = $_FILES['logo'];
      $name = $file['name'];
      $tmp = $file['tmp_name']; 
      $size = $file['size']; 
      $type = $file['type'];

      $f = fopen($tmp, 'r');
      $content = fread($f, filesize($tmp)); 
      fclose($f);
      
      $series->setLogo($content, $type, $size); 
    }
  } else if ($_POST['action'] == "Update Organizers") { 
    if (isset($_POST['delstewards'])) { 
      $removals = $_POST['delstewards']; 
      foreach ($removals as $deadsteward) { 
        $series->removeSteward($deadsteward);
      }
    }  
    if (!isset($_POST['addsteward'])) {
      return; 
    } 
    $addition = $_POST['addsteward']; 
    $addplayer = new Player($addition);
    if ($addplayer->verified == 0 && Player::getSessionPlayer()->super == 0 ) { 
      $hasError = true;
      $errormsg .= "Can't add {$addition} to stewards, they aren't a verified user!";
      return;
    }
    $series->addSteward($addition); 
  } 
} 
