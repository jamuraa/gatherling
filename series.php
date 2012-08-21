<?php
session_start();
include 'lib.php';

print_header("Event Information");
?>

<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Regular Events </div>

<?php
$active_series = Series::activeNames();

foreach ($active_series as $series_name) {
  if ($series_name == "Other") {
    continue;
  }
  $series = new Series($series_name);
  if (strtotime($series->mostRecentEvent()->start) + (86400 * 7 * 4) < time() && !$series->nextEvent()) {
    continue;
  }
?>
<div class="series">
  <div class="series-name"><?php echo "$series->name";?></div>
  <div class="series-logo"><?php echo Series::image_tag($series->name); ?></div>
  <div class="series-stewards">
    Hosted by
    <ul>
      <?php foreach ($series->stewards as $player) { ?>
        <li><?php echo $player; ?></li>
      <?php } ?>
    </ul>
  </div> <!-- Series-stewards -->
  <div class="series-info">
    <table>
    <?php
    $season_format_name = str_replace(' ','', $series->this_season_format);
    $season_format_link = "format.php?mode=desc&id=" . $season_format_name;
    ?>
    <tr> <th> Format </th> <td> <a href="<?php echo $season_format_link?>"> <?php echo $series->this_season_format ?></a> </td> </tr>
    <?php
    $start_format = "%I:%M %P";
    if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN") {
      $start_format = str_replace('P', 'p', $start_format);
    }
    ?>
    <tr> <th> Regular Time </th> <td> <?php echo $series->start_day ?>, <?php echo strftime($start_format, strtotime($series->start_time)) ?> Eastern Time </td> </tr>
    <tr> <th> Rules </th> <td> <a href="<?php echo $series->this_season_master_link ?>">Season <?php echo $series->this_season_season ?> Master Document</a> </td> </tr>
    <tr> <th> Most Recent Event </th> <td> <?php echo $series->mostRecentEvent()->linkReport() ?> </td> </tr>
    <?php
    $nextevent = $series->nextEvent();
    if ($nextevent) {
      $next_format = "%B %e %I:%M %P";
      if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN") {
        $next_format = str_replace("P", "p", $next_format);
        $next_format = str_replace("e", "#d", $next_format);
      }
    ?>
      <tr> <th> Next Event </th> <td> <?php echo strftime($next_format . " registration", strtotime($nextevent->start) - minutes(30)) ?> </td> </tr>
    <?php } else { ?>
      <tr> <th> Next Event </th> <td> Not scheduled yet </td> </tr>
    <?php } ?>
    </table>
  </div> <!-- Series-info -->
</div> <!-- Series -->
<?php } ?>

<div class="clear"></div>
</div> <!-- gatherling_main -->
</div> <!-- grid_10 suffix_1 prefix_1 -->

<?php print_footer(); ?>

