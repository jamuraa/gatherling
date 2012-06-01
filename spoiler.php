<html>

<?php
require_once('lib.php');
$db = Database::getConnection();

$cardsets = array();

if(isset($_GET['format'])) {
  $format = $_GET['format'];
  $stmt = $db->prepare("SELECT cardset FROM setlegality WHERE format= ?");
  $stmt->bind_param("s", $format);
  $stmt->execute();
  $stmt->bind_result($cardset);


  while ($stmt->fetch()) {
    $cardsets[] = $cardset;
  }
  $stmt->close();
} else {
  $cardsets[] = $_GET['set'];
}

$stmt = $db->prepare("SELECT id , (isw + isg + isu + isr + isb) AS n,
  isw, isg, isu, isb, isr
  FROM cards WHERE cardset = ? ORDER BY n , isw desc, isg desc, isu desc,
  isr desc, isb desc, name");
?>

<body bgcolor=\"#404040\">

<?php
$n = 0;
$w = $g = $u = $r = $b = 0;
foreach ($cardsets as $cardset) {
  $stmt->bind_param("s", $cardset);
  $stmt->execute() or die($stmt->error);
  $stmt->bind_result($id, $total, $isw, $isg, $isu, $isb, $isr);
  while ($stmt->fetch()) {
    if( $isw != $w || $isg != $g || $isu != $u || $isr != $r || $isb != $b) {
      echo "<br><br>";
      $n = 0;
    }
    printf("<img src=\"/cards/%d.jpg\">\n", $id);
    $w = $isw;
    $g = $isg;
    $u = $isu;
    $r = $isr;
    $b = $isb;
    $n++;
  }
}
$stmt->close();
?>

</body>
</html>
