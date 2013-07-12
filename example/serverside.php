<?php
// Insert this block of code at the very top of your page:
$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$start = $time;

include_once('simple_html_dom.php');
include_once('..\gistfy.php');
include_once ('config.inc');

if($_GET['button'] == 'read'){
$html = file_get_html($_GET['url']);
$subject = '';
//                $subject = $html->find('div[id=story]');
//                get title
foreach ($html->find('h1') as $e) {
  $title = $e->plaintext;
}
// find all article tags (div[itemprop=articleBody]) in.com
if (empty($subject)) {
  foreach ($html->find('span[id=advenueINTEXT]') as $e) {

    $subject .= $e->plaintext . ' ';
     
  }
  //                    $subject .= '- div[class=article_body] article';
}
// find all article tags (div[itemprop=articleBody]) in.com
if (empty($subject)) {
  foreach ($html->find('div[itemprop=articleBody]') as $e) {
    foreach ($e->find('p') as $p) {
      $subject .= $p->plaintext . ' ';
    }
  }
  //                    $subject .= '- div[class=article_body] article';
}
// find all article tags (div[itemprop=articleBody]) in.com
if (empty($subject)) {
  foreach ($html->find('div[id=inc_dec]') as $e) {
    $subject .= $e->plaintext . ' ';
  }
  //                    $subject .= '- div[class=article_body] article';
}
// find all article tags ('article')
if (empty($subject)) {
  foreach ($html->find('article') as $e) {
    foreach ($e->find('p') as $p) {
      $subject .= $p->plaintext . ' ';
    }
  }
  //                    $subject .= '- div[class=article_body] article';
}
// find all div tags with id=story('div[class*=story]')--The hindu article-text
if (empty($subject)) {
  foreach ($html->find('div[class*=article-text]') as $e) {
    foreach ($e->find('p[class=body]') as $p) {
      $subject .= $p->plaintext . ' ';
    }
  }
  //                    $subject .= '- div[class*=article-text]';
}
// find all div tags with class=story('div[class*=story]')->
if (empty($subject)) {
  foreach ($html->find('div[class*=story]') as $e) {
    foreach ($e->find('p') as $p) {
      $subject .= $p->plaintext . ' ';
    }
  }
  //                    $subject .= '- div[class*=story]';
}
// find all div tags with id=story('div[div*=story]')->
if (empty($subject)) {
  foreach ($html->find('div[id*=story]') as $e) {
    foreach ($e->find('p') as $p) {
      $subject .= $p->plaintext . ' ';
    }
  }
  //                    $subject .= '- div[id*=story]';
}
// clean up memory
$html->clear();
unset($html);

$tagger = new gistfy($mongo_host, $mongo_db, $pos_array);
$mypara = $tagger->gist($subject, $_GET['size']);
// Place this part at the very end of your page
$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$finish = $time;
$totaltime = ($finish - $start);
$potential_tags = $tagger->potential_tags($subject);
$gistfy = array(
    "title" => $title,
    "gist" => $mypara,
    "url" => $_GET['url'],
    "count" => str_word_count($mypara),
    "totaltime" => $totaltime,
    "percent" => (str_word_count($mypara) / str_word_count($subject)) * 100,
    "potential_tags" => $potential_tags;
);

print json_encode($gistfy);
// var_dump($gistfy);
}elseif($_GET['button'] == 'tread'){
  $tagger = new gistfy($mongo_host, $mongo_db, $pos_array);
  $mypara = $tagger->gist($_GET['new_message'], $_GET['size']);
  $potential_tags = $tagger->potential_tags($subject);
  // Place this part at the very end of your page
  $time = microtime();
  $time = explode(" ", $time);
  $time = $time[1] + $time[0];
  $finish = $time;
  $totaltime = ($finish - $start);
  
  $gistfy = array(
//       "title" => $title,
      "gist" => $mypara,
//       "url" => $_GET['url'],
      "count" => str_word_count($mypara),
      "totaltime" => $totaltime,
      "percent" => (str_word_count($mypara) / str_word_count($_GET['new_message'])) * 100,
      "potential_tags" => $potential_tags;
  );
  
  print json_encode($gistfy);
}
?>
