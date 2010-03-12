<?php 
session_start(); 
include 'lib.php'; 

print_header("PDCMagic.com | Gatherling | Season Report");

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
	seriesDropMenu($_GET['series'], 1);
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
  uasort($points, 'reverse_total_sort');

  echo "<h3><center>Point Scoreboard for {$series->name} season {$season}</center></h3>";
  echo "<table class=\"scoreboard\">";
  echo "<tr class=\"top\"> <th> Place </th> <th> Player </th> <th> Total Points </th>";
  foreach ($seasonevents as $evname) { 
    echo "<th> {$evname} </th>";
  } 
  echo "</tr>";    
  $count = 0;
  foreach ($points as $player => $pointar) { 
    $count++;
    if ($count % 2 != 0) { 
      echo "<tr class=\"odd\"> ";
    } else { 
      echo "<tr > ";
    } 
    echo "<td> {$count} </td> <td class=\"playername\"> {$player} </td> <td> {$pointar['.total']} </td> "; 
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

