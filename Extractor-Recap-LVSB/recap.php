<?php
header("Content-type: text/html; charset=UTF-8");
require('./DOM/simple_html_dom.php');
date_default_timezone_set('GMT');

$DEBUG = FALSE;
$GENERATE = false;

global $log;
$log = '';

function get_debug_html($file){
    $result = file_get_contents($file);
    $result = json_decode($result, true);
	return $result;
}

function get_html($url) {
  global $log;
  $log.='Leyendo ' . $url."\n\r";
  $agent= 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
  $ch = curl_init();
  $timeout = 35;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 15);
  @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_COOKIE, "LANGUAGE=en;DEFLANG=en;BG_UA=Desktop|Windows 7|NT 6.1|Firefox|3.6.12||");
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

// HELPER
function table_height($table)
{
  $trs = $table->find('tr');
  return count($trs);
}
function table_width($table)
{
  $trs = $table->find('tr');
  $rows = count($trs) > 0 ? count($trs) : 0;
  $cols = 0;
  foreach($trs as $tr) {
    $tmp = count($tr->find('td'));
    $cols = $tmp > $cols ? $tmp : $cols;
  }
  return $cols;
}
function table_to_array($table,$x_offset=0,$y_offset=0,$w=0,$h=0) {
  $trs = $table->find('tr');
  // Table size
  $cols = table_width($table);
  $rows = table_height($table);
  // Region size and offsets
  if($w <= 0) $width = $cols + $w - $x_offset;
  else $width = $w;
  if($h <= 0) $height = $rows + $h - $y_offset;
  else $height = $h;
  // table iteration
  for($y=$y_offset; $y<($y_offset+$height); $y++)
  {
      $tr = $trs[$y];
      $tds = $tr->find('td');
      for($x=$x_offset; $x<($x_offset+$width); $x++)
        $row_array[] = trim($tds[$x]->plaintext);
      $table_array[] = $row_array;
      unset($row_array);
  }
  return $table_array;
}

function find_next($el, $txt){
  $it = $el;
   do {
     if(strtsr($el->plaintext, $txt))
      return $el;
     $it = $it->next_sibling();
   } while($it != NULL);
}

function find_first($els, $txt){
  foreach($els as $item)
  {
      if(strstr($item->plaintext, $txt))
      {
        return $item;
        break;
      }
  }
}

function remove_multi_spaces($data){
  $ret = preg_replace("/\s+/", ' ', $data);
  $ret = ltrim($ret);
  return $ret;
}

if(isset($_POST['sendform']) && $_POST['sendform']) {

// HTML DATA
	if($DEBUG)
	{
    if($GENERATE)
    {
  		$links['vegas'] = 'http://www.lasvegassportsbetting.com/Monday-Night-Football-Odds-Free-Betting-Picks-Baltimore-Ravens-vs-New-England-Patriots_A41667.html';
  		$links['sd'] = 'http://content.sportsdirectinc.com/football/nfl-boxscores.aspx?page=/data/NFL/results/2016-2017/boxscore52008.html';
  		$links['bov'] = 'http://stats.bovada.lv/NFL/recaps/recap.asp?GameId=11417&affid=1308237';
  		$links['sn'] = 'http://www.sportsnet.ca/football/nfl/games/1635788/baltimore-ravens-vs-new-england-patriots/';

  	foreach($links as $key => $link)
  		$html[$key] = get_html($link);

  	foreach($html as $key => $html_item)
    {
  		file_put_contents('RecapData/'.$key.'.html',$html_item);
      $log.='Salvando ' . $key."\n\r";
    }

    } else {
      // DEBUG - NO GENERATE
      $html['vegas'] = file_get_contents('RecapData/vegas.html');
      $html['sd'] = file_get_contents('RecapData/sd.html');
      $html['bov'] = file_get_contents('RecapData/bov.html');
      $html['sn'] = file_get_contents('RecapData/sn.html');
      $html['sd_rcp'] = file_get_contents('RecapData/sd_rcp.html');

      $log.="Leyendo páginas (vegas, sd, bov, sn/cbsScore) localmente\n\r";
    }

	} else {
		$links['vegas'] = $_POST['link1'];
		$links['sd'] = $_POST['link2'];
		$links['bov'] = $_POST['link3'];
		$links['sn'] = $_POST['link4'];

    foreach($links as $key => $link)
    {
  		$html[$key] = get_html($link);
      if(strlen($html[$key]) < 400)
        die("Algo falló cargando las páginas"); // Forma burda de comprobar cURL
    }
	}


  // Load DOM
  foreach($html as $key => $html_item)
    $html[$key] = str_get_html($html_item);

  // OBTAINING DATA FROM DIFFERENT SITES

  // Get current Sport
  $items = $html['sd']->find('span[class=sdi-title-insport]');
  $data['sport'] = trim($items[0]->plaintext);
  if(strstr($data['sport'], "College Basket"))
    $data['sport'] = "NCAAB";

  unset($items);

  // Get additional HTMLs
  switch($data['sport'])
  {
    case 'NBA':
    case 'NCAAB':
      $links['sd_rcp'] = preg_replace("/\/boxscore([0-9]*)\.html/", '/recap$1.html', $links['sd']);
      $html['sd_rcp'] = get_html($links['sd_rcp']);
      $html['sd_rcp'] = str_get_html($html['sd_rcp']);
      $log.="Detectado Basket, cargado ".$links['sd_rcp']." \n\r";
    break;
    default:

  }

  // Get team names
  $items = $html['vegas']->find('article div h2');
  foreach($items as $item)
  {
      if(preg_match("/(.*)(?:[\s]+)vs[\.]?(?:[\s]+)(.*)/", $item->plaintext, $results)!=false)
      {
        $data['title'] = $item->plaintext;
        $data['team1']['name'] = $results[1];
        $data['team2']['name'] = $results[2];
        break;
      }
  }

  // Get odds title
  $data['titleodds'] = '';
  foreach($items as $item)
  {
      if(strstr($item->plaintext, "Odds") && strstr($item->next_sibling(), "Gm#"))
      {
        $data['titleodds'] = $item->plaintext;
        break;
      }
  }
  if(empty($data['titleodds']))
    $data['titleodds'] = $data['title'] . " " . $data['sport'] . " Las Vegas Odds";

  unset($teams);

  // Get teams Gm
  $gms = $html['vegas']->find('article div table');
  foreach($gms as $item)
  {
      if(strstr($item->plaintext, "Gm#"))
      {
        unset($gms);
        $data['team1']['gm'] = $item->find('tr')[1]->find('td')[0]->plaintext;
        $data['team2']['gm'] = $item->find('tr')[2]->find('td')[0]->plaintext;
        break;
      }
  }


  // Scores
  $data['team1']['score'] = isset($_POST['t1sc']) ? $_POST['t1sc'] : '';
  $data['team2']['score'] = isset($_POST['t2sc']) ? $_POST['t1sc'] : '' ;

  // Push input
  $data['pushtext'] = isset($_POST['pushtext']) ? $_POST['pushtext'] : '';
  $data['pushlink'] = isset($_POST['pushlink']) ? $_POST['pushlink'] : '' ;

  // Pick
  $items = $html['vegas']->find('article div h2');
  $pick_terms = array('Pick', 'Prediction', 'Free');
  foreach($items as $item)
  {
    foreach($pick_terms as $term)
      if(strstr($item->plaintext, $term))
      {
        unset($items);
        $data['picks'] = $item->find('a')[0]->innertext;
        $data['picks_sentence'] = $item->outertext;
        break;
      }
  }

  // Get Who
  $items = $html['sd']->find('div[class=sdi-title-page-who]');
  $data['who'] = $items[0]->plaintext;

  // Get Short Team Names and Score alternative to input
  $result = preg_split("[,]", $data['who']);

  if(count($result) == 2)
  {
    preg_match("/([a-zA-Z\s\.]+)\s([0-9]+)/", $result[0], $info);
    $name1 = ltrim($info[1]);
    $score1 = $info[2];

    preg_match("/([a-zA-Z\s\.]+)\s([0-9]+)/", $result[1], $info);
    $name2 = ltrim($info[1]);
    $score2 = $info[2];

  /*  if(strstr($data['team1']['name'], $name1))
    {
      $data['team1']['name_short'] = $name1;
      $data['team1']['score_sd'] = $score1;
      $data['team2']['name_short'] = $name2;
      $data['team2']['score_sd'] = $score2;
    } else //if (strstr($data['team2']['name'], $name1))
    {*/
      $data['team1']['name_short'] = $name2;
      $data['team1']['score_sd'] = $score2;
      $data['team2']['name_short'] = $name1;
      $data['team2']['score_sd'] = $score1;
    //}
    /*
    if(strstr($data['team1']['name'], $name2))
    {
      $data['team1']['name_short'] = $name2;
      $data['team1']['score_sd'] = $score2;
    } else if (strstr($data['team2']['name'], $name2))
    {
      $data['team2']['name_short'] = $name2;
      $data['team2']['score_sd'] = $score2;
    }
  }*/
  }

  // Get SD First lines
  $items = $html['sd']->find('div[class=sdi-quickhits]');
  $result = preg_split("[<br[/]?>]", $items[0]->innertext);
  foreach($result as $item)
  {
    if(strstr($item, "Where"))
    {
      $it_desc = preg_match("/(?<=<\/strong>)(.*)/", $item, $matches);
      $data['where'] = $matches[0];
    } else if(strstr($item, "When"))
    {
      $it_desc = preg_match("/(?<=<\/strong>)(.*)/", $item, $matches);
      $data['when'] = $matches[0];
    } else if(strstr($item, "Attendance"))
    {
      $it_desc = preg_match("/(?<=<\/strong>)(.*)/", $item, $matches);
      $it_desc = preg_match("/(?:[\s]*\:)?(.*)/", $matches[0], $matches);
      $data['att'] = $matches[1];
    }

  }
  unset($items);

  // Get Bovada 2 first <p>
  $items = $html['bov']->find('div[id=chalk-stats]')[0]->find('p');
  for($i=0; $i<count($items); $i++) if(!empty($items[$i]->plaintext)) break;
  $data['firstp'] = $items[$i]->plaintext;
  $i++;
  $data['secondp'] = array_key_exists($i, $items) ? $items[$i]->plaintext : '';

  // Next matchup
  $data['next'] = end($items)->plaintext;

  // PLAYER STATS
  $items = $html['sd']->find('div[class=sdi-title-page-section]');
  foreach($items as $item)
  {
      if(strstr($item->plaintext, "PLAYER STAT"))
      {
        $data['playerstats']['team1']['name'] = $item->parent()->find('div[class=sdi-so-title]')[0]->plaintext;
        $data['playerstats']['team2']['name'] = $item->parent()->find('div[class=sdi-so-title]')[1]->plaintext;

        if($data['sport']=='NFL' or $data['sport']=='NFL ')
        {/*
          $data['playerstats']['team1']['qb'] = array();
          $data['playerstats']['team2']['qb'] = array();*/
          $table1 = $item->parent()->find('table[class=sdi-data-wide]')[0];
          $table2 = $item->parent()->find('table[class=sdi-data-wide]')[1];
          $data['playerstats']['team1']['qb']['names'] = call_user_func_array('array_merge', table_to_array($table1,0,2,1));
          $data['playerstats']['team1']['qb']['stats'] = table_to_array($table1,1,2);
          $data['playerstats']['team2']['qb']['names'] =  call_user_func_array('array_merge', table_to_array($table2,0,2,1));
          $data['playerstats']['team2']['qb']['stats'] = table_to_array($table2,1,2);
          /*
          $vals1 = $table1->find('tr')[2]->find('td');
          $vals2 = $table2->find('tr')[2]->find('td');
          $data['playerstats']['team1']['qb']['name'] = $vals1[0]->plaintext;
          $data['playerstats']['team2']['qb']['name'] = $vals2[0]->plaintext;
          unset($vals1[0]);
          unset($vals2[0]);
          foreach($vals1 as $td)
            $data['playerstats']['team1']['qb']['stats'][] = ltrim($td->plaintext);
          foreach($vals2 as $td)
            $data['playerstats']['team2']['qb']['stats'][] = ltrim($td->plaintext);
          */
        }
        unset($items);
        break;
      }
  }

  // Final scoring summary
  $items = $html['sd']->find('div[class=sdi-title-page-section]');
  $item = find_first($items, "Final Scoring Summary");
  $item = $item->next_sibling();
  $item = $item->find('table')[0];
  $data['playerstats']['team1']['stats'] = table_to_array($item,1,1,0,1)[0];
  $data['playerstats']['team2']['stats'] = table_to_array($item,1,2,0,1)[0];
  /*
  $rows = $item->find('tr');
  $cols = $rows[1]->find('td');
  unset($cols[0]);
  foreach($cols as $val)
      $data['playerstats']['team1']['stats'][] = $val->plaintext;
  $cols = $rows[2]->find('td');
  unset($cols[0]);
  foreach($cols as $val)
      $data['playerstats']['team2']['stats'][] = $val->plaintext;
  */

// Long text
$data['longtext'] = "";
if(strstr($data['sport'], "NCAA"))
{
  $items = $html['sn']->find('div[class=storyCopy]');
} else
   $items = $html['sn']->find('div[class=article-copy]');

 foreach ($items[0]->find('p') as $p)
 {
   if(strstr($p, "UP NEXT"))
    break;
   $data['longtext'] .= "<p>" . $p->plaintext . "</p>\n\r";
 }



// By-sport data extraction
 switch($data['sport'])
 {
   case 'NCAAB':
   case 'NBA':
   $log.="\r\nExtracting NBA/NCAAB data";
    // Top performances
     $items = $html['sd_rcp']->find('div[class=sdi-title-page-section]');
     foreach($items as $item)
     {
         if(strstr($item->plaintext, "Top Game Performance"))
         {
           $table = $item->next_sibling()->find('table[class=sdi-data-wide]')[0];
           $data['team1']['topperformances'] = table_to_array($table, 0, 1, 1, 3);
           $data['team2']['topperformances'] = table_to_array($table, 2, 1, 1, 3);
           foreach($data['team1']['topperformances'] as $key => $item)
            $data['team1']['topperformances'][$key] = $item[0];
            foreach($data['team2']['topperformances'] as $key => $item)
             $data['team2']['topperformances'][$key] = $item[0];
          /* echo "<pre>";
           print_r($data['team1']['topperformances']);
           print_r($data['team2']['topperformances']);
           echo "</pre>";*/
           unset($items);
           break;
         }
     }

     // Stats
     if(strstr($data['sport'], "NCAA"))
     {
       $item = $html['sn']->find('table[class=lineScore]')[0]->find('tr[id=final]');
       $data['team1']['stat'] = $item[0]->find('td')[0]->plaintext;
       $data['team2']['stat'] = $item[1]->find('td')[0]->plaintext;

       preg_match("/(?:.*)?\(([0-9\-]*)\)(?:.*)?/", $data['team1']['stat'], $results);
       $data['team1']['stat'] = $results[1];

       preg_match("/(?:.*)?\(([0-9\-]*)\)(?:.*)?/", $data['team2']['stat'], $results);
       $data['team2']['stat'] = $results[1];

       /* TheScore Old js-loaded
       $item = $html['sn']->find('div[class=game-teams]')[0];
       $teams = $item->find('div[class=team-place-record]');
       $data['team1']['stat'] = preg_split("[,]", $teams[0]->plaintext)[0];
       $data['team2']['stat'] = preg_split("[,]", $teams[1]->plaintext)[0];*/
     } else {
       $item = $html['sn']->find('div[class=live-game-header-container]')[0];
       $data['team1']['stat'] = $item->find('div[class=team-info-container-left]')[0]->find('div[class=game-score]')[0]->plaintext;
       $data['team2']['stat'] = $item->find('div[class=team-info-container-right]')[0]->find('div[class=game-score]')[0]->plaintext;
     }

   break;
   default:

 }



  // Filter data array
  function filter(&$value, $key) {
    $exceptions = array("longtext");
    if(in_array($key, $exceptions)) return;

    //$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $value = remove_multi_spaces($value);
  }
  array_walk_recursive($data, "filter");

  // DATA TRANSFORMATIONS
  $stat_tds_1 = '';
  $stat_tds_1_len = count($data['playerstats']['team1']['stats']);
  for($x=0; $x<($stat_tds_1_len-1);$x++)
  {
    $stat_tds_1.="<td align=\"center\">"
    .$data['playerstats']['team1']['stats'][$x]
    ."</td>\r\n";
  }
  $stat_tds_1.="<td align=\"center\"><strong>".$data['playerstats']['team1']['stats'][$x]."</strong></td>\r\n";
  $stat_tds_2 = '';
  $stat_tds_2_len = count($data['playerstats']['team2']['stats']);
  for($x=0; $x<($stat_tds_2_len-1);$x++)
  {
    $stat_tds_2.="<td align=\"center\">"
    .$data['playerstats']['team2']['stats'][$x]
    ."</td>\r\n";
  }
  $stat_tds_2.="<td align=\"center\"><strong>".$data['playerstats']['team2']['stats'][$x]."</strong></td>\r\n";


  // Load template & replace league/sport-specific terms
  switch(trim($data['sport']))
  {
      case 'NFL':
      {
        $log.="Cargada template de Football \n\r";
        $template = file_get_contents("Templates/NFL-template.txt");

        $n_rows = max(count($data['playerstats']['team1']['qb']['names']),
                      count($data['playerstats']['team2']['qb']['names']));
        $n_cols = count($data['playerstats']['team1']['qb']['stats'][0]); // per qb

        $qb_cells = '';
        for($y=0; $y<$n_rows; $y++) {
          $qb_cells .= "\t<tr>\r\n";
          // Fill names
          if(isset($data['playerstats']['team1']['qb']['names'][$y]))
            $qb_cells .=  "\t\t<td>".$data['playerstats']['team1']['qb']['names'][$y]."</td>\r\n";
          else $qb_cells .=  "\t\t<td></td>\r\n";
          // Fill stats
          for($x=0; $x<$n_cols; $x++)
          {
            if(isset($data['playerstats']['team1']['qb']['stats'][$y][$x]))
              $qb_cells .=  "\t\t<td align=\"right\">".$data['playerstats']['team1']['qb']['stats'][$y][$x]."</td>\r\n";
            else   $qb_cells .=  "\t\t<td align=\"right\"></td>\r\n";
          }
          $qb_cells .= "\t\t<td>##NBSP##</td>\r\n";
          // Fill names
          if(isset($data['playerstats']['team2']['qb']['names'][$y]))
              $qb_cells .=  "\t\t<td>".$data['playerstats']['team2']['qb']['names'][$y]."</td>\r\n";
          else   $qb_cells .=  "\t\t<td></td>\r\n";
          // Fill stats
          for($x=0; $x<$n_cols; $x++)
          {
            if(isset($data['playerstats']['team2']['qb']['stats'][$y][$x]))
                $qb_cells .=  "\t\t<td align=\"right\">".$data['playerstats']['team2']['qb']['stats'][$y][$x]."</td>\r\n";
            else   $qb_cells .=  "\t\t<td align=\"right\"></td>\r\n";
          }
          $qb_cells .= "\t</tr>";
        }

        $template = str_replace("##passings##", $qb_cells, $template);

        $template = str_replace("##ps_name1##", $data['playerstats']['team1']['name'], $template);
        $template = str_replace("##ps_name2##", $data['playerstats']['team2']['name'], $template);

        /*

        $tmp = "";
        foreach($data['playerstats']['team1']['qb']['stats'] as $val)
          $tmp .= "\t<td>".$val."</td>\n\r";

        $template = str_replace("##passing1tds##", $tmp, $template);

        $tmp = "";
        foreach($data['playerstats']['team2']['qb']['stats'] as $val)
          $tmp .= "\t<td>".$val."</td>\n\r";

        $template = str_replace("##passing2tds##", $tmp, $template);*/

      }break;
      case 'NBA':
      case 'NCAAB':
      {
        $log.="Cargada template de NBA/NCAAB \n\r";
        if(strstr($data['sport'],"NCAA"))
          $template = file_get_contents("Templates/NCAABK-template.txt");
        else
          $template = file_get_contents("Templates/NBA-template.txt");

        $template = str_replace("##scoring1##", $data['team1']['topperformances'][0], $template);
        $template = str_replace("##assists1##", $data['team1']['topperformances'][1], $template);
        $template = str_replace("##rebounds1##", $data['team1']['topperformances'][2], $template);
        $template = str_replace("##scoring2##", $data['team2']['topperformances'][0], $template);
        $template = str_replace("##assists2##", $data['team2']['topperformances'][1], $template);
        $template = str_replace("##rebounds2##", $data['team2']['topperformances'][2], $template);
        $template = str_replace("##stat1##", $data['team1']['stat'], $template);
        $template = str_replace("##stat2##", $data['team2']['stat'], $template);
        $template = str_replace("##ps_name1##", $data['team1']['name'], $template);
        $template = str_replace("##ps_name2##", $data['team2']['name'], $template);
      }
      break;
    default:
      $template = file_get_contents("Templates/template.txt");
  }
  $output = $template;

  // Replace generic data terms
  @$output = str_replace("##finalstatsheader##", $stat_H, $output); // TO DO
  $output = str_replace("##finalstats1##", $stat_tds_1, $output);
  $output = str_replace("##finalstats2##", $stat_tds_2, $output);
  $output = str_replace("##who##", $data['who'], $output);
  $output = str_replace("##when##", $data['when'], $output);
  $output = str_replace("##where##", $data['where'], $output);
  $output = str_replace("##title##", $data['title'], $output);
  $output = str_replace("##titleodds##", $data['titleodds'], $output);
  $output = str_replace("##firstp##", $data['firstp'], $output);
  $output = str_replace("##secondp##", $data['secondp'], $output);
  $output = str_replace("##longtext##", $data['longtext'], $output);
  $output = str_replace("##lastp##", $data['next'], $output);
  $output = str_replace("##att##", $data['att'], $output);
  $output = str_replace("##name1##", $data['team1']['name'], $output);
  $output = str_replace("##name2##", $data['team2']['name'], $output);
  $output = str_replace("##gm1##", $data['team1']['gm'], $output);
  $output = str_replace("##gm2##", $data['team2']['gm'], $output);

/*
  switch($data['sport'])
  {
    case 'NFL':
    $output = str_replace("##ps_name1##", $data['playerstats']['team1']['name'], $output);
    $output = str_replace("##ps_name2##", $data['playerstats']['team2']['name'], $output);
    break;
    default:
    case 'NBA':
    $output = str_replace("##ps_name1##", $data['team1']['name'], $output);
    $output = str_replace("##ps_name2##", $data['team2']['name'], $output);
  }
  */


  if(!empty($data['team1']['score']))
    $output = str_replace("##score1##", $data['team1']['score'], $output);
  else if (!empty($data['team1']['score_sd']))
    $output = str_replace("##score1##", $data['team1']['score_sd'], $output);

  if(!empty($data['team1']['score']))
    $output = str_replace("##score2##", $data['team2']['score'], $output);
  else if (!empty($data['team1']['score_sd']))
    $output = str_replace("##score2##", $data['team2']['score_sd'], $output);

  $output = str_replace("##pushlink##", $data['pushlink'], $output);
  $output = str_replace("##pushtext##", $data['pushtext'], $output);
  $output = str_replace("##picks_sentence##", $data['picks_sentence'], $output);


  //$output_code = $output;
 $output_code = htmlspecialchars($output);
 $output_code = str_replace("&amp;nbsp;", " ", $output_code);
 $output_code = str_replace("##NBSP##", "&amp;nbsp;", $output_code);

}


?>
<html>
<head>
<title>Recap Extractor by JFK</title>
<script>
function CopyToClipboard(containerid) {
if (document.selection) {
    var range = document.body.createTextRange();
    range.moveToElementText(document.getElementById(containerid));
    range.select().createTextRange();
    document.execCommand("Copy");
} else if (window.getSelection) {
    var range = document.createRange();
     range.selectNode(document.getElementById(containerid));
     window.getSelection().addRange(range);
     document.execCommand("Copy");
}}
</script>
</head>
<body style="background:#ccc; padding: 30px 50px 50px 50px">
<h1 style="display:inline-block">Recap extractor</h1> by jfk

<form id="input" method="post" action="recap.php">
<input type="hidden" name="sendform" value="1">
<table>
<tr>
<td>Las vegas</td><td><input type="text" name="link1" size="80" value="<?php echo isset($_POST['link1']) ? $_POST['link1'] : "";?>"></td>
</tr>
<tr>
<td>Sportsdirect</td><td><input type="text" name="link2" size="80" value="<?php echo isset($_POST['link2']) ? $_POST['link2'] : "";?>"></td>
</tr>
<tr>
<td>Bovada</td><td><input type="text" name="link3" size="80" value="<?php echo isset($_POST['link3']) ? $_POST['link3'] : "";?>"></td>
</tr>
<tr>
<td>Sportsnet/cbsSports</td><td><input type="text" name="link4" size="80" value="<?php echo isset($_POST['link4']) ? $_POST['link4'] : "";?>"></td>
</tr>
<tr>
<td>Team 1 Score (opcional)</td><td><input type="text" name="t1sc" size="40"></td>
</tr>
<tr>
<td>Team 2 Score (opcional)</td><td><input type="text" name="t2sc" size="40"></td>
</tr>
<tr>
<td>Push text</td><td><input type="text" name="pushtext" size="40"></td>
</tr>
<tr>
<td>Push link</td><td><input type="text" name="pushlink" size="80"></td>
</tr>
</table>
<input type="submit"> <a href="javascript:void(0)" onclick="CopyToClipboard('cod')">Copiar código</a>
</form>

<?php
if(isset($_POST['sendform']) && $_POST['sendform']) {
?>
<br>

<div style="background:black; color:white; padding: 5px 5px 5px 5px; overflow: hidden">
  <h3 style="margin-top: 0px">LOG:</h3>
<?php
  if(isset($_POST['sendform']) && $_POST['sendform']) {
    echo "<pre>";
    echo $log;
    echo "Los datos han sido extraídos: \n\r";
    print_r($data);
    echo "</pre>";
  }
?>
</div>

<h1>OUTPUT CODE:</h1>

<div style="background:white; color:#333; padding: 5px 5px 5px 5px; overflow: scroll" id="cod">
<?php
  if(isset($_POST['sendform']) && $_POST['sendform']) {
    echo "<pre>";
    echo $output_code;
    echo "</pre>";
  }
?>
</div>

<h1>PREVIEW:</h1>
  <?= htmlspecialchars_decode($output_code);?>
<div>

</div>

<?php } ?>

</body>
</html>
<!-- El arte ni se crea ni se destruye, solo se transforma -->
