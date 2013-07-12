<?php

/**
 * Description of part_of_speech
 *
 * @author loganathane
 */
class gistfy {
 
  function __construct($db_conn, $db_name, $pos_array) {
    $this->conn = $db_conn;
    $this->db_name = $db_name;
    $this->pos_array = $pos_array;
  }

  //    private $dict;

  public function gettag($mytoken) {
    if($this->conn){
      $connection = new Mongo($this->conn);
    }else{
      $connection = new Mongo();
    }
    $db = $connection->selectDB($this->db_name);
    //        $i = 0;
    $collection = $db->selectCollection("lexicon");
    $search = array('token' => $mytoken);
    $cursor = $collection->find($search);
    foreach ($cursor as $id => $value) {
      return $value['tag'][0];
    }
  }

  public function tag($text) {
    preg_match_all("/[\w\d\.]+/", $text, $matches);
    $nouns = array('NN', 'NNS');

    $return = array();
    $i = 0;
    foreach ($matches[0] as $token) {
      // default to a common noun
      $return[$i] = array('token' => $token, 'tag' => 'NN');

      // remove trailing full stops
      if (substr($token, -1) == '.') {
        $token = preg_replace('/\.+$/', '', $token);
      }

      // get from dict if set
      $temp = $this->gettag(strtolower($token));
      if (isset($temp)) {
        $return[$i]['tag'] = $this->gettag(strtolower($token));
      }

      // Converts verbs after 'the' to nouns
      if ($i > 0) {
        if ($return[$i - 1]['tag'] == 'DT' &&
            in_array($return[$i]['tag'], array('VBD', 'VBP', 'VB'))) {
          $return[$i]['tag'] = 'NN';
        }
      }

      // Convert noun to number if . appears
      if ($return[$i]['tag'][0] == 'N' && strpos($token, '.') !== false) {
        $return[$i]['tag'] = 'CD';
      }

      // Convert noun to past particile if ends with 'ed'
      if ($return[$i]['tag'][0] == 'N' && substr($token, -2) == 'ed') {
        $return[$i]['tag'] = 'VBN';
      }

      // Anything that ends 'ly' is an adverb
      if (substr($token, -2) == 'ly') {
        $return[$i]['tag'] = 'RB';
      }

      // Common noun to adjective if it ends with al
      if (in_array($return[$i]['tag'], $nouns)
          && substr($token, -2) == 'al') {
        $return[$i]['tag'] = 'JJ';
      }

      // Noun to verb if the word before is 'would'
      if ($i > 0) {
        if ($return[$i]['tag'] == 'NN'
            && strtolower($return[$i - 1]['token']) == 'would') {
          $return[$i]['tag'] = 'VB';
        }
      }

      // Convert noun to plural if it ends with an s
      if ($return[$i]['tag'] == 'NN' && substr($token, -1) == 's') {
        $return[$i]['tag'] = 'NNS';
      }

      // Convert common noun to gerund
      if (in_array($return[$i]['tag'], $nouns)
          && substr($token, -3) == 'ing') {
        $return[$i]['tag'] = 'VBG';
      }

      // If we get noun noun, and the second can be a verb, convert to verb
      if ($i > 0) {
        $in_temp = $this->gettag(strtolower($token));
        if (in_array($return[$i]['tag'], $nouns)
            && in_array($return[$i - 1]['tag'], $nouns)
            && isset($in_temp)) {
          if (is_array($this->gettag(strtolower($token))) && in_array('VBN', $this->gettag(strtolower($token)))) {
            $return[$i]['tag'] = 'VBN';
          } else if (is_array($this->gettag(strtolower($token))) && in_array('VBZ', $this->gettag(strtolower($token)))) {
            $return[$i]['tag'] = 'VBZ';
          }
        }
      }

      $i++;
    }

    return $return;
  }

  public function stopWords($text) {
    $keywords = array();
    if(!empty($this->conn)){
      $connection = new Mongo($this->conn);
    }else{
      $connection = new Mongo();
    }
    $db = $connection->selectDB($this->db_name);
    $collection = $db->selectCollection("stopWord");

    // Replace all non-word chars with comma
    $pattern = '/[0-9\W]/';
    $text = preg_replace($pattern, ',', $text);

    // Create an array from $text
    $text_array = explode(",", $text);

    // remove whitespace and lowercase words in $text
    $text_array = array_map(function($x) {
      return trim(strtolower($x));
    }, $text_array);
    $sword = '';
    foreach ($text_array as $term) {
      $search = array('token' => $term);
      $cursor = $collection->find($search);
      foreach ($cursor as $value) {
        $sword = $value['token'];
      }
      if ($sword != $term) {
        $tags = $this->tag($term);
        $keywords[] = $tags;
        $sword = '';
      }
    };

    return array_filter($keywords);
  }

  public function sentence_split($string) {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT',html_entity_decode($string));
    $re = '/# Split sentences on whitespace between them.
        (?<=                # Begin positive lookbehind.
        [.!?|]             # Either an end of sentence punct,
        | [.!?][\'"]        # or end of sentence punct and quote.
        )                   # End positive lookbehind.
        (?<!                # Begin negative lookbehind.
        Mrs\.             # or "Mrs.",
        | Prof\.            # or "Prof.",
        | \s(e\.g\.|i\.e\.)              # or e.g., i.e.
        | \s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\.              # Month,
        | \s(Sept)\.
        | \s[A-Z]\.              # or initials ex: "George W. Bush",
        | \s\([A-Z]\.              # or initials ex: "(W. Bush",
        | \s([A-Z][A-Z])\.             # or initials ex: "G.W. Bush",
        | \.[A-Z]\.             # or initials ex: "G.W. Bush",
        | \s(\.\.\.)              # or "...",
        | Ah!              # or initials ex: "George W. Bush",
        # or... (you get the idea).
        )                   # End negative lookbehind.
        \s+                 # Split on whitespace between sentences.
        /ix';
    // split on whitespace between sentences preceded by a punctuation mark
    $result = preg_split($re, $string, -1, PREG_SPLIT_NO_EMPTY);
    return $this->brackets_checker($this->quote_checker($result));
  }

  //unique keyword values.
  public function uni_keyword($array) {
    $counts = array();

    foreach ($array as $key => $subarr) {
      // Add to the current group count if it exists
      if (isset($counts[$subarr[0]['token']])) {

        $counts[$subarr[0]['token']]['rank']++;

        // or initialize to 1 if it doesn't exist
      } else {

        $counts[$subarr[0]['token']]['rank'] = 1;
        $counts[$subarr[0]['token']]['token'] = $subarr[0]['token'];
        $counts[$subarr[0]['token']]['tag'] = $subarr[0]['tag'];
      }

      // Or the ternary one-liner version
      // instead of the preceding if/else block
      $counts[$subarr[0]['token']] = isset($counts[$subarr[0]['token']]) ? $counts[$subarr[0]['token']]++ : 1;
    }
    return $counts;
  }

  //sentence_ranker function takes 2 input i.e.

  public function sentence_ranker($string) {
    $overall_keyword = $this->stopWords($string);
    $sentences = $this->sentence_split($string);
    $overall_keywords = $this->uni_keyword($overall_keyword); //count overall paragraph keyword values
//     $pos_array = array('CD' => 1, 'JJ' => 5, 'NN' => 10, 'NNS' => 10, 'RB' => 5, 'VB' => 7, 'VBG' => 8, 'VBN' => 7, 'VBP' => 6); //parts of speech rank array.
    arsort($overall_keywords); //sort array
    $i = 0; //initialize array key
    $keyword_sen = array(); //initialize array
    foreach ($sentences as $k => $sentence) {
      $keyword = $this->uni_keyword($this->stopWords($sentence)); //count sentence keyword value.
      //        var_dump($keyword);
      arsort($keyword); //sort array
      //            $rank = ($k) == 0 ? //check for first sentence.
      //                    1000 : //set rank for first sentance as 10.
      //                    ($k) == (count($sentence)-1) ?
      //                    -1000 :
      //                    0; //else set value to 0.
      if($k == 0){
        $rank = 10000;
      }elseif ($k == (count($sentences)-1) && str_word_count($sentence) < 4) {
        $rank = -1000;
      }  else {
        $rank = 0;
      }
      foreach ($overall_keywords as $key => $value) {
        if (array_key_exists($key, $keyword)) {
          if (array_key_exists($keyword[$key]['tag'], $this->pos_array)) {//tag
            $rank = $rank + ($value['rank'] * $keyword[$key]['rank'] * $this->pos_array[$keyword[$key]['tag']]);
          } else {
            $rank = $rank + ($value['rank'] * $keyword[$key]['rank']);
          }
        }
      }
      $keyword_sen[$i]['sentence'] = $sentence;
      $keyword_sen[$i]['rank'] = $rank;
      //$keyword_sen[$i]['keyword'] = $keyword; //uncomment if sentance keyword rank required.
//                  $keyword_sen[$i]['wordcount'] = str_word_count($sentence); // uncomment if sentance word count required.
      $i++; //array key incrementer.
    }
    //Potential tags based ranking starts
    $potential_tags = $this->potential_tags($string);
    $sent_rank = array();
    foreach ($keyword_sen as $key => $sen_let){
      foreach ($potential_tags as $pt) {
        if (strpos($sen_let['sentence'], $pt) !== false) {
          $sent_rank[$key]['sentence'] = $sen_let['sentence'];
          $sent_rank[$key]['rank'] = $sen_let['rank'] * 10;
          //$sent_rank[$key]['keyword'] = $sen_let['keyword']; //uncomment if sentance keyword rank required.
//           $sent_rank[$key]['wordcount'] = $sen_let['wordcount']; // uncomment if sentance word count required.
        }else{
          $sent_rank[$key] = $sen_let;
        }
      }
    }
    return $sent_rank;
  }

  public  function gistfy_compare($x, $y) {
    if ($x['rank'] == $y['rank'])
      return 0;
    else if ($x['rank'] > $y['rank'])
      return -1;
    else
      return 1;
  }

  public function gist($string, $max_count) {
    $gist_rank_array = $this->sentence_ranker($string);
    uasort($gist_rank_array, array($this, 'gistfy_compare'));
    //    var_dump($gist_rank_array);
    $para = '';
    $len = 0;
    foreach ($gist_rank_array as $key => $sentence_let) {
      //    $max_count = 800; // max sentence length.
      $temp_len = $len + str_word_count($sentence_let['sentence']); //set temp
      if ($temp_len <= $max_count) {
        $para .= ($key & 1) == 0 ?
        trim($sentence_let['sentence']) . '<br>' :
        $sentence_let['sentence'] . '<br>';
        $len = $len + str_word_count($sentence_let['sentence']);
      }
    }
    return $para;
  }

  public function convert_smart_quotes($string)
  {
    $search = array(chr(145),
        chr(146),
        chr(147),
        chr(148),
        chr(151));

    $replace = array("'",
        "'",
        '"',
        '"',
        '-');

    return str_replace($search, $replace, $string);
  }

  public function quote_checker ($sent){
    $newsent = array();
    $temp_sent = '';
    $flag_quote = FALSE;
    foreach ($sent as $value) {
      if(preg_match('/^(["]).*\1/m', $value) == 1){
        $newsent[] = $value;
      }elseif(preg_match('/^(["]).*/m', $value) == 1){
        $temp_sent = $value;
        $flag_quote = TRUE;
      }elseif(preg_match('/(["]).*$/m', $value) == 1 && !empty ($temp_sent)){
        $newsent[] = $temp_sent .' '. $value;
        $temp_sent = '';
        $flag_quote = FALSE;
      } elseif (strpos($value, '"')) {
        $temp_sent = $value;
        $flag_quote = TRUE;

      } elseif($flag_quote) {
        $temp_sent .= ' '.$value;
      }  else {
        $newsent[] = $value;
      }
    }

    return $newsent;
  }
  //brackets checker
  public function brackets_checker ($sent){
    $newsent = array();
    $temp_sent = '';
    $flag_quote = FALSE;
    for($i=0; $i <= count($sent); $i++){
      if (isset($sent[$i])){
        if(preg_match("/\(.*?\)/", $sent[$i]) == 1){
          $newsent[] = $sent[$i];
        }elseif(preg_match('/^([\(]).*/m', $sent[$i]) == 1){
          $temp_sent = $sent[$i];
          $flag_quote = TRUE;
        }elseif(preg_match('/([\)]).*$/m', $sent[$i]) == 1){
          $newsent[] = $temp_sent .' '. $sent[$i];
          $temp_sent = '';
          $flag_quote = FALSE;
        }  elseif($flag_quote) {
          $temp_sent .= ' '.$sent[$i];
        }  else {
          $newsent[] = $sent[$i];
        }
      }
    }

    return $newsent;
  }
  
  // Potential tags
  public function potential_tags($text){
    $nlptags = array();
    $nlptags = $this->tag($text);
    $i = 0;
    $sizeof = sizeof($nlptags);
    $grammer = array('NN', 'NNP', 'JJ');
    $per = array();
    while ($i<$sizeof){
      if (isset($nlptags[$i]) && isset($nlptags[$i+1]) && in_array(trim($nlptags[$i]['tag']), $grammer)
          && in_array(trim($nlptags[$i+1]['tag']), $grammer)){
        $temp1 = $this->stopWords(trim($nlptags[$i]['token']));
        $temp2 =$this->stopWords(trim($nlptags[$i+1]['token']));
        if(!empty($temp1) && !empty($temp2)){
          $per[]= $nlptags[$i]['token'] .' '.$nlptags[$i+1]['token'];
        }
        $i++;
      }
      $i++;
    }
  
    return  array_unique($per);
  }

}

?>
