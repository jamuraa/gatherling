<?php
include 'lib.php';

  $ratings = new Ratings();
  $ratings->deleteAllRatings();
  $ratings->calcAllRatings();
  
?>
