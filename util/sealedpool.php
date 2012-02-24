<?php

include('../lib.php');
header("content-type: text/plain");

$pool = array();
$packs = array("Tenth Edition", "Lorwyn", "Lorwyn", "Time Spiral", "Time Spiral", "Planar Chaos", "Future Sight");

for($p = 0; $p < 7; $p++) {
  $pack= genpack($packs[$p]);
  for($n = 0; $n < 11; $n++) {
    $pool[$pack[$n]]++;
  }
}

foreach($pool as $card => $qty) {
  echo "$qty $card\n";
}


function genpack($set) {
  $pack = array();
  $card = 0;
  $ndx = -1;
  $list = setList($set);
  drShuffle($list);
  while($card < 1) {
    $ndx++;
    if($list[$ndx]["isw"] == 1 && $list[$ndx]["name"] != "") {
      $pack[$card] = $list[$ndx]["name"];
      $card++;
    }
  } 
  while($card < 2) {
        $ndx++;
        if($list[$ndx]["isg"] == 1  && $list[$ndx]["name"] != "") {
            $pack[$card] = $list[$ndx]["name"];
            $card++;
        }
    }
  while($card < 3) {
        $ndx++;
        if($list[$ndx]["isu"] == 1 && $list[$ndx]["name"] != "") {
            $pack[$card] = $list[$ndx]["name"];
            $card++;
        }
    }
  while($card < 4) {
        $ndx++;
        if($list[$ndx]["isr"] == 1 && $list[$ndx]["name"] != "") {
            $pack[$card] = $list[$ndx]["name"];
            $card++;
        }
    }
  while($card < 5) {
        $ndx++;
        if($list[$ndx]["isb"] == 1 && $list[$ndx]["name"] != "") {
            $pack[$card] = $list[$ndx]["name"];
            $card++;
        }
    }
  while($card < 11) {
    if($list[$ndx]["name"] != "") {
    $ndx ++;
    $pack[$card] = $list[$ndx]["name"];
    $card++;
    }
  }
  return $pack;
}

function setList($set) {
  $db = dbcon();
  $query = "SELECT name, isw, isg, isu, isr, isb 
    FROM cards WHERE cardset=\"$set\"";
  $result = mysql_query($query, $db);
  $ret = array();
  $n = 0;
  while($row = mysql_fetch_assoc($result)) {
    $ret[$n] = $row;
    $n++;
  }
  return $ret;
}

function drShuffle(&$arr) {
    mt_srand(makeSeed());
    for($i = 0; $i < sizeof($arr); $i++) {
        $swp = mt_rand(0, sizeof($arr) - 1);
        $tmp = $arr[$swp];
        $arr[$swp] = $arr[$i];
        $arr[$i] = $tmp;
    }
}

# From PHP Manual at: http://us2.php.net/manual/en/function.mt-srand.php
# Retrieved 2007-01-04
function makeSeed() {
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

?>
