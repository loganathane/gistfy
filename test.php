<?php

include_once('gistfy_mysql.php');
include_once ('example\config.inc');

$string = 'The fourth Wells account moving to another agency is the packaged paper-products division of Georgia-Pacific Corp., which arrived at Wells only last fall. Like Hertz and the History Channel, it is also leaving for an Omnicom-owned agency, the BBDO South unit of BBDO Worldwide. BBDO South in Atlanta, which handles corporate advertising for Georgia-Pacific, will assume additional duties for brands like Angel Soft toilet tissue and Sparkle paper towels, said Ken Haldin, a spokesman for Georgia-Pacific in Atlanta.';



$tagger = new gistfy($mongo_host, $mongo_db, $pos_array);
$nlptags = $tagger->tag($string);
// $stopwords = $tagger->uni_keyword($tagger->stopWords($string));
$mypara = $tagger->gist($string, 100);


echo $mypara;
// echo '<pre>';
// var_dump($uni_per);
// var_dump($nlptags);
?>
