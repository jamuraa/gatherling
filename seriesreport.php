<?php 
session_start(); 
include 'lib.php'; 

print_header("PauperKrew.com | Gatherling | Season Report");

?> 

<div class="grid_10 prefix_1 suffix_1"> 

<div id="gatherling_main" class="box">
<div class="uppertitle"> Season Report </div>
<?php 
selectSeason();
if (isset($_GET['series']) && isset($_GET['season'])) { 
  $series = new Series($_GET['series']); 
  showReport($series, $_GET['season']);
}  

?>

</div> 
</div> 

<?php print_footer(); ?> 

<?php 

function selectSeason() { 
	echo "<form action=\"seriesreport.php\" method=\"get\">";
	echo "<table class=\"form\" style=\"border-width: 0px\" align=\"center\">";
	echo "<tr><th>Series</th><td>";
  Series::dropMenu($_GET['series'], 1);
	echo "</td></tr>";
	echo "<tr><th>Season</th><td>";
	seasonDropMenu($_GET['season'], 1);
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td colspan=\"2\" class=\"buttons\">";
  echo "<input type=\"submit\" value=\"Get Season Scoreboard\" />\n";
	echo "</td></tr></table></form>";
} 


function reverse_total_sort($a, $b) { 
  if ($a['.total'] == $b['.total']) { 
    return 0;
  } 
  return ($a['.total'] < $b['.total']) ? 1 : -1; 
} 

function showReport($series, $season) {
  $seasonevents = $series->getSeasonEventNames($season);
  $points = $series->seasonPointsTable($season);
  $cutoff = $series->getSeasonCutoff($season);
  uasort($points, 'reverse_total_sort');

  echo "<h3><center>Scoreboard for {$series->name} season {$season}</center></h3>";
  echo "<table class=\"scoreboard\">";
  echo "<tr class=\"top\"> <th> Place </th> <th> Player </th> <th> Total </th>";
  foreach ($seasonevents as $evname) { 
    $shortname = preg_replace("/^{$series->name} /", '', $evname);
    $reportlink = "eventreport.php?event=" . urlencode($evname);
    echo "<th> <a href=\"{$reportlink}\">{$shortname}</a> </th>";
  } 
  echo "</tr>";    
  $count = 0;
  foreach ($points as $playername => $pointar) { 
    $player = new Player($playername);
    $count++;
    $classes = "";  
    if ($count % 2 != 0) { 
      $classes .= "odd";
    } 
    if ($count == $cutoff) { 
      $classes .= " cutoff";
    } 
    echo "<tr class=\"{$classes}\"> ";
    echo "<td> {$count} </td> <td class=\"playername\"> {$player->linkTo()} </td> <td> {$pointar['.total']} </td> "; 
    foreach ($seasonevents as $evname) { 
      if (isset($pointar[$evname])) { 
        if (is_array($pointar[$evname])) { 
          echo "<td> <span title=\"{$pointar[$evname]['why']}\"> {$pointar[$evname]['points']} </span> </td>";
        } else { 
          echo "<td> {$pointar[$evname]} </td>"; 
        } 
      } else { 
        echo "<td> </td> "; 
      } 
    }  
    echo "</tr> "; 
  }
  echo "</table>"; 
}

