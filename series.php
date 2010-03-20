<?php
session_start();
include 'lib.php';

$formatforums = array('Standard' => 'http://forums.pdcmagic.com/viewforum.php?f=4',
  'Extended' => 'http://forums.pdcmagic.com/viewforum.php?f=5',
  'Classic' => 'http://forums.pdcmagic.com/viewforum.php?f=6',
  'Zendikar Block' => 'http://forums.pdcmagic.com/viewforum.php?f=20');

print_header("PDCMagic.com | Gatherling | Event Information");
?>

<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Regular Pauper Player-Run Events </div>

<?php
$active_series = Series::activeNames();

foreach ($active_series as $series_name) {
  if ($series_name == "Other") { 
    continue;
  } 
  $series = new Series($series_name);
?>
<div class="series">
  <div class="series-name">
    <?php echo $series->name ?>
  </div> 
  <div class="series-logo">
    <img src="displaySeries.php?series=<?php echo $series->name ?>" /> 
  </div>
  <div class="series-stewards">
    Hosted by
    <ul> 
      <?php foreach ($series->stewards as $player) { ?> 
        <li><?php echo $player; ?></li>
      <?php } ?> 
    </ul> 
  </div> 
  <div class="series-info">
    <table> 
    <tr> <th> Format </th> <td> <a href="<?php echo $formatforums[$series->this_season_format]; ?>">Pauper <?php echo $series->this_season_format ?></a> </td> </tr> 
    <tr> <th> Regular Time </th> <td> <?php echo $series->start_day ?>, <?php echo strftime("%I:%M %P", strtotime($series->start_time)) ?> Eastern Time </td> </tr> 
    <tr> <th> Rules </th> <td> <a href="<?php echo $series->this_season_master_link ?>">Season <?php echo $series->this_season_season ?> Master Document</a> </td> </tr> 
    <tr> <th> Most Recent Event </th> <td> <?php echo $series->mostRecentEvent()->linkReport() ?> </td> </tr> 
    <?php 
    $nextevent = $series->nextEvent(); 
    if ($nextevent) { 
    ?> 
      <tr> <th> Next Event </th> <td> <?php echo strftime("%B %e %I:%M %P registration", strtotime($nextevent->start) - minutes(30)) ?> </td> </tr> 
    <?php } else { ?> 
      <tr> <th> Next Event </th> <td> Not scheduled yet </td> </tr> 
    <?php } ?> 
    </table> 
  </div> 
</div> 
<?php } ?> 

<div class="clear"></div> 
</div> </div> 

<?php print_footer(); ?> 

