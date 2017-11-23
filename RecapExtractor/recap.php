<?php
header("Content-type: text/html; charset=UTF-8");
require('./DOM/simple_html_dom.php');
date_default_timezone_set('GMT');

$DEBUG = true;
$GENERATE = false;

global $log;
$log = '';

function get_debug_html($file){
    // GET HTML
    $result = file_get_contents($file);

    // Javascript to PHP array
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
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 15);
  @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_COOKIE, "LANGUAGE=en;DEFLANG=en;BG_UA=Desktop|Windows 7|NT 6.1|Firefox|3.6.12||");
  $data = curl_exec($ch);
   //echo "Curl result: ".curl_errno($ch);
  curl_close($ch);

  return $data;
}


// HELPER
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

      $log.="Leyendo páginas (vegas, sd, bov, sd) localmente\n\r";
    }

	} else {
		$links['vegas'] = $_POST['link'];
		$links['sd'] = $_POST['link'];
		$links['bov'] = $_POST['link'];
		$links['sn'] = $_POST['link'];

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
  $data['sport'] = $items[0]->plaintext;
  unset($items);

  // Get team names
  $teams = $html['vegas']->find('article div h2');
  foreach($teams as $item)
  {
      if(preg_match("/(.*)(?:[\s]+)vs[\.]?(?:[\s]+)(.*)/", $item->plaintext, $results)!=false)
      {
        $data['title'] = $item->plaintext;
        $data['team1']['name'] = $results[1];
        $data['team2']['name'] = $results[2];
        unset($teams);
        break;
      }
  }

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
  foreach($items as $item)
  {
      if(strstr($item->plaintext, "Picks:"))
      {
        unset($items);
        $data['picks'] = $item->find('a')[0]->innertext;
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
    preg_match("/([a-zA-Z\s]+)\s([0-9]+)/", $result[0], $info);
    $name1 = ltrim($info[1]);
    $score1 = $info[2];

    preg_match("/([a-zA-Z\s]+)\s([0-9]+)/", $result[1], $info);
    $name2 = ltrim($info[1]);
    $score2 = $info[2];

    if(strstr($data['team1']['name'], $name1))
    {
      $data['team1']['name_short'] = $name1;
      $data['team1']['score_sd'] = $score1;
    } else if (strstr($data['team2']['name'], $name1))
    {
      $data['team2']['name_short'] = $name1;
      $data['team2']['score_sd'] = $score1;
    }

    if(strstr($data['team1']['name'], $name2))
    {
      $data['team1']['name_short'] = $name2;
      $data['team1']['score_sd'] = $score2;
    } else if (strstr($data['team2']['name'], $name2))
    {
      $data['team2']['name_short'] = $name2;
      $data['team2']['score_sd'] = $score2;
    }
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
  $items = $html['bov']->find('td[class=Sport_data]')[0]->find('p');
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
        unset($items);
        $data['playerstats']['team1']['name'] = $item->parent()->find('div[class=sdi-so-title]')[0]->plaintext;
        $data['playerstats']['team2']['name'] = $item->parent()->find('div[class=sdi-so-title]')[1]->plaintext;

        if($data['sport']=='NFL' or $data['sport']=='NFL ')
        {
          $data['playerstats']['team1']['qb'] = array();
          $data['playerstats']['team2']['qb'] = array();
          $table1 = $item->parent()->find('table[class=sdi-data-wide]')[0];
          $table2 = $item->parent()->find('table[class=sdi-data-wide]')[1];
          $vals1 = $table1->find('tr')[2]->find('td');
          $vals2 = $table2->find('tr')[2]->find('td');
          $data['playerstats']['team1']['qb']['name'] = $vals1[0]->plaintext;
          $data['playerstats']['team2']['qb']['name'] = $vals2[0]->plaintext;
          unset($vals1[0]);
          unset($vals2[0]);
          foreach($vals1 as $td)
            $data['playerstats']['team1']['qb'][] = ltrim($td->plaintext);
          foreach($vals2 as $td)
            $data['playerstats']['team2']['qb'][] = ltrim($td->plaintext);
        }

        break;
      }
  }

  // Final scoring summary
  $items = $html['sd']->find('div[class=sdi-title-page-section]');
  $item = find_first($items, "Final Scoring Summary");
  $item = $item->next_sibling();
  $item = $item->find('table')[0];
  $rows = $item->find('tr');
  $cols = $rows[1]->find('td');
  unset($cols[0]);
  foreach($cols as $val)
      $data['playerstats']['team1']['stats'][] = $val->plaintext;
  $cols = $rows[2]->find('td');
  unset($cols[0]);
  foreach($cols as $val)
      $data['playerstats']['team2']['stats'][] = $val->plaintext;


// Long text
 $data['longtext'] = "";
 $items = $html['sn']->find('div[class=article-copy]');
 foreach ($items[0]->find('p') as $p)
 {
   if(strstr($p, "UP NEXT"))
    break;
   $data['longtext'] .= $p->plaintext . "\n\r";
 }

  // Filter data array
  function filter(&$value, $key) {
    $exceptions = array("longtext");
    if(in_array($key, $exceptions)) return;

    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $value = remove_multi_spaces($value);
  }
  array_walk_recursive($data, "filter");

  // Variante
  switch($data['sport'])
  {
      case 'NFL':
      case 'NFL ':
      {
        $variante = file_get_contents("Templates/NFL-template.txt");
        $variante = str_replace("##passing1##", $data['playerstats']['team1']['qb']['name'], $variante);
        $variante = str_replace("##passing2##", $data['playerstats']['team2']['qb']['name'], $variante);

        $tmp = "";
        foreach($data['playerstats']['team1']['qb'] as $val)
          $tmp .= "\t<td>".$val."</td>\n\r";

        $variante = str_replace("##passing1tds##", $tmp, $variante);

        $tmp = "";
        foreach($data['playerstats']['team2']['qb'] as $val)
          $tmp .= "\t<td>".$val."</td>\n\r";

        $variante = str_replace("##passing2tds##", $tmp, $variante);

      }break;
    default:
    $variante = '';
  }


  // Load template
  $output = file_get_contents("Templates/template.txt");

  // Replace data

  // Start replacing variant because it might contain other args
  $output = str_replace("##variante##", $variante, $output);

  $output = str_replace("##who##", $data['who'], $output);
  $output = str_replace("##when##", $data['when'], $output);
  $output = str_replace("##where##", $data['where'], $output);
  $output = str_replace("##title##", $data['title'], $output);
  $output = str_replace("##firstp##", $data['firstp'], $output);
  $output = str_replace("##secondp##", $data['secondp'], $output);
  $output = str_replace("##longtext##", $data['longtext'], $output);
  $output = str_replace("##lastp##", $data['next'], $output);
  $output = str_replace("##att##", $data['att'], $output);
  $output = str_replace("##name1##", $data['team1']['name'], $output);
  $output = str_replace("##name2##", $data['team2']['name'], $output);
  $output = str_replace("##gm1##", $data['team1']['gm'], $output);
  $output = str_replace("##gam2##", $data['team2']['gm'], $output);
  $output = str_replace("##ps_name1##", $data['playerstats']['team1']['name'], $output);
  $output = str_replace("##ps_name2##", $data['playerstats']['team2']['name'], $output);

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
  $output = str_replace("##pick##", $data['picks'], $output);

  $output_code = htmlspecialchars($output);

}


?>
<html>
<body style="background:#ccc; padding: 30px 50px 50px 50px">
<h1 style="display:inline-block">Recap extractor</h1> by jfk

<form id="input" method="post" action="recap.php">
<input type="hidden" name="sendform" value="1">
<table>
<tr>
<td>Las vegas</td><td><input type="text" name="link1" size="80"></td>
</tr>
<tr>
<td>Sportsdirect</td><td><input type="text" name="link2" size="80"></td>
</tr>
<tr>
<td>Bovada</td><td><input type="text" name="link3" size="80"></td>
</tr>
<tr>
<td>Sportsnet</td><td><input type="text" name="link4" size="80"></td>
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
<input type="submit">
</form>

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

<div style="background:white; color:#333; padding: 5px 5px 5px 5px; overflow: scroll">
<?php
  if(isset($_POST['sendform']) && $_POST['sendform']) {
    echo "<pre>";
    echo $output_code;
    echo "</pre>";
  }
?>
</div>

</body>
</html>
<!-- El arte ni se crea ni se destruye, solo se transforma -->
