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
  $seasonreport = $series->seasonPointsTable($_GET['season']); 
  showReport($series, $_GET['season'], $seasonreport);
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

function showReport($series, $season, $points) { 
  arsort($points);

  echo "<h3><center>Point Scoreboard for {$series->name} season {$season}</center></h3>";
  echo "<table>";
  echo "<tr class=\"top\"> <th> Place </th> <th> Player </th> <th> Points </th> </tr>";
  $count = 0;
  foreach ($points as $player => $seasonpts) { 
    $count++;
    echo "<tr> <td> {$count} </td> <td> {$player} </td> <td> {$seasonpts} </td> </tr> "; 
  }
  echo "</table>"; 
}

