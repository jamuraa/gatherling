<?php
session_start();
include 'lib.php';

print_header("Format Information");
?>

<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Format Description: <?php if(isset($_GET['id'])) {echo $_GET['id'];} ?> </div>

<p>No description available for this format yet. </p>

</div> <!-- gatherling_main -->
</div> <!-- grid_10 suffix_1 prefix_1 -->

<?php print_footer(); ?> 
