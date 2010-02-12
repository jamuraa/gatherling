<?php 
session_start();
include 'lib.php'; 

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
  $active_series = $_GET['series'];
  if (count($player_series) > 1) { 
    printStewardSelect($player_series, $active_series); 
  } else { 
    echo "<center> Managing {$active_series} </center>";
  } 
  printSeriesForm($active_series);
  printLogoForm($active_series);
} 

function printNoSeries() { 
  echo "<center>You're not a coordinator of any series, so you can't use this page.<br />";
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

function printSeriesForm($seriesname) { 
  $series = new Series($seriesname);
  echo "<form action=\"seriescp.php\" method=\"post\">";
  echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
  echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesname}\" />";
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

function printLogoForm($seriesname) { 
  $series = new Series($seriesname); 
  echo "<form action=\"seriescp.php\" method=\"post\" enctype=\"multipart/form-data\">";
  echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
  echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesname}\" />";
  echo "<tr><th> Current Logo </th>";
  echo "<td> <img src=\"displaySeries.php?series={$seriesname}\" /> </td> </tr>";
  echo "<tr><th> Upload New Logo </th>";
  echo "<td> <input type=\"file\" name=\"logo\" /> ";
  echo "<input type=\"submit\" name=\"action\" value=\"Change Logo\" /> </td> </tr>";
  echo "</table> </form> "; 
} 

function handleActions() { 
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
  }
} 
