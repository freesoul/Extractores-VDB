<?
header("Content-type: text/html; charset=UTF-8");
require('./DOM/simple_html_dom.php');
date_default_timezone_set('GMT');

// SETTINGS

$table_class = "article";
//$button_class= "button league_button"
$max_n = 4;

// DEBUG SETTINGS

$width = 737;
$padding = 100;
$bg = '#151515;';

?>
<html>
<head>
	<style>
		.button > a {
		display: inline-block;
		text-decoration: none;
		padding: 10px;
		}
		.league_button {
			background-color: green;
			color: #fff;
		}
		.league_button:hover {
			background-color: darkgreen;
			color: #fff;
		}
		
		/* table general */
		table.article {
			width: 100%;
			background: #fff;
			/*border: 1px solid #000000;*/
			color: #000;
			margin: 15px auto;
			border-radius: 10px;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			border-collapse: separate;
		}
		table.article td, table.article th {
			padding: 4px 14px;
		}

		/* table alternation */
		table.article tr:not(:first-child) td:nth-child(2n-1) {
			background: #DEDEDE;
		}
		
		/* Table header */
		table.article tr:first-child td{
			/*bg made in ambient.css*/
			font-weight: bold;
		}
		table.article tr:first-child td:first-child {
			border-top-left-radius: 9px;
		}
		table.article tr:first-child td:last-child {
			border-top-right-radius: 9px;
		}
		table.article tr:first-child td{
			background: -webkit-gradient( linear, left top, left bottom, color-stop(0.05, #3ec947), color-stop(1, #006500));
			background: -moz-linear-gradient( center top, #3ec947 5%, #006500 100%);
			filter: progid: DXImageTransform.Microsoft.gradient(startColorstr='#3ec947', endColorstr='#006500');
			background-color: #3ec947;
			color: #000;
		}
	</style>
</head>
<body bgcolor="#FFEEEE">
<br>
<center>
<h1>VDB-Extractor</h1>
<!--Made by JFK-->
<form action="extractor2.php" method="post">
    Art&iacute;culo: <input type="text" name="input_url"/><br>
    Enlace: <input type="text" name="output_url"/><br>
    <input type="submit">
</form>
</center>
<br/>

<?php
function get_debug_html($file){
    // GET HTML
    $result = file_get_contents($file);
    
    // Javascript to PHP array
    $result = json_decode($result, true);

	return $result;
}
	
function get_html($url) {
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

function get_data($url) {
    // GET HTML
    $html = get_html($url);   

    // LOAD HTML DOM
    $html = str_get_html($html);
    
    // Get last script text which contents data
    $result = end($html->find('script'))->innertext;
    
    // Prepare script to load into json decoder
    $result = strstr($result, "{");
    $result = substr($result, 0, -1);
	
    // Javascript to PHP array
    $result = json_decode($result, true);
	
	return $result;
}


// HELPER

function ArrayGetKeyTypes($arr, $key){
	$output = array();
	foreach($arr as $item)
		if(!in_array($item[$key], $output))
			$output[] = $item[$key];
		
	return $output;
}

function GetChildByKey($parent,$args){
	foreach($parent as $item)
	{
		// Check this item
		$ok = true;
		
		// Must have all keys
		foreach($args as $key => $val)
		{
			if(!array_key_exists($key,$item))
			{ $ok = false; break;}
			if($item[$key]!=$val)
			{ $ok = false; break; }
		}	
		
		if($ok)
			return $item;
	}
	return false;
}

// Outcome process
function GetOutcomePrice($outcome){
	
	$output = "";
	
             if ($outcome["description"]=="test") { return false; };
                   
                // Know if we had to add parenthesis
                $sethandicap=false;
                
                // Set handicap
                if(isset($outcome["price"]["handicap"]) 
                        && $outcome["price"]["handicap"]!= "") 
                {
                    $sethandicap=true;
                    $handicap = $outcome["price"]["handicap"];
                    

                    // Set type
                    $o_or_u = false;
                    if(isset($outcome["type"]) && ($outcome["type"]=="U") || ($outcome["type"]=="O")) 
                    {
                        $o_or_u = true;
                        $type = strtolower($outcome["type"]);
                    } else $type="";
                    

						if($handicap == "0.0")
						{
							$handicap = "Pick";
						} else {

									
								$n_handicap = floatval($handicap);
									
								$half_decimal = false;
								if (fmod($n_handicap,1)!==0.00) $half_decimal=true;
									   
									// Write down number without decimal
									$value="0";
									$handicap="";
								if($n_handicap > 0) 
								{
									$value = abs(floor($n_handicap));
									if(!$o_or_u) $handicap = "+";
									if($value != "0") $handicap.=$value;
								} else {
									$value = abs(ceil($n_handicap));
									if(!$o_or_u) $handicap = "-";
									if($value != "0") $handicap.=$value;
								}
										
										// Write down half
								if($half_decimal)
									$handicap .= "&frac12;";


						}

                }
                
                
                // Create output
                $american = $outcome["price"]["american"];
                if($american == "") $american ="&ndash;";    // no number
				if($american == "+100") $american = "EVEN";
                
                if($sethandicap)
                {
                    // Add parenthesis
                      $output.= "<a href=\"".$_POST['output_url']."\" rel=\"nofollow\">".$handicap." (".$american.")".$type."</a>";
                } else {
                    // No parenthesis
                      $output.= "<a href=\"".$_POST['output_url']."\" rel=\"nofollow\">".$american."</a>";
                }
				
				
	return $output;
	
}

function make_button($text)
{	
	$output="<div class=\"button\"><a class=\"league_button\" href=\"javascript:void(0);\">".
	$text."</a></div>\r\n";
	return $output;
}

function make_button_link($text, $link)
{
	$output="<div class=\"button\" style=\"display: inline-block; margin-right: 5px; margin-bottom: 5px\"><a class=\"league_button\" href=\"".$link."\">".
	$text."</a></div>\r\n";
	return $output;
}


//////// CODE GENERATION START

if(isset($_POST['input_url'])) {
	
	// GET ARRAY
	$debug = false;
	
	$url = $_POST['input_url'];

	if($debug) 		
		$result = get_debug_html("debug.txt"); 
    else
		$result = get_data($url);
	
	
	// START REPORT
	
    echo "<h1>Report de \"".$result['items'][0]['description']."\"</h1>";
    ?>


<h1>C&oacute;digo:</h1>

<div style="background-color:lightgray">
    
<pre>
<?php

    // CODE GENERATION
	
    $output = "";
	
	// Title
	$title = $result['items'][0]['description'];
	
	
	// Goto buttons
	$output.="<div style=\"margin-bottom: 5px; font-size: 1.2em\">Find fast:</div>";
	$group_number = 1;
	foreach(array_slice($result['items'][0]['displayGroups'],1) as $group) {
		$output.= make_button_link($group["description"], "#outcome_group_".$group_number) . " ";
		$group_number++;
	}
	
	$output.="<h1 style=\"text-align: center\">".$title."</h1>";
	
	// Competitors 
	$competitors = ArrayGetKeyTypes($result['items'][0]['competitors'],'description');
	
	// GAME LINES
	$gamelines = $result['items'][0]['displayGroups'][0];
	
	$periodTypes = ArrayGetKeyTypes($gamelines["itemList"], "periodType");
	
	
	// Insert button
	$output.=make_button($gamelines["description"]);
	
	foreach($periodTypes as $periodType)
	{
		$args = array (
			'description' => '3-Way Moneyline',
			'periodType' => $periodType
		);
		$ml = GetChildByKey($gamelines["itemList"],$args);
		
		$args = array (
			'description' => 'Goal Spread',
			'periodType' => $periodType
		);
		$spread = GetChildByKey($gamelines["itemList"],$args);
		
		$args = array (
			'description' => 'Total',
			'periodType' => $periodType
		);
		$total = GetChildByKey($gamelines["itemList"],$args);
		
		if($periodType!="Regulation Time")
			$output.="<h3>".$periodType."</h3>";
		
		$output.="<table cellspacing=\"0\" class=\"".$table_class."\">\r\n";
		$output.="\t<tr>\r\n";
		$output.="\t\t<td></td>\r\n";
		$output.="\t\t<td>Spread</td>\r\n";
		$output.="\t\t<td>MoneyLine</td>\r\n";
		$output.="\t\t<td>Total</td>\r\n";
		$output.="\t</tr>\r\n";
		$output.="\t<tr>\r\n";
		$output.="\t\t<td>".$competitors[0]."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($spread["outcomes"][0])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($ml["outcomes"][0])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($total["outcomes"][0])."</td>\r\n";
		$output.="\t</tr>\r\n";
		$output.="\t<tr>\r\n";
		$output.="\t\t<td>".$competitors[1]."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($spread["outcomes"][1])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($ml["outcomes"][1])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($total["outcomes"][1])."</td>\r\n";
		$output.="\t</tr>\r\n";
		$output.="\t<tr>\r\n";
		$output.="\t\t<td>DRAW</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($spread["outcomes"][2])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($ml["outcomes"][2])."</td>\r\n";
		$output.="\t\t<td>".GetOutcomePrice($total["outcomes"][2])."</td>\r\n";
		$output.="\t</tr>\r\n";
		$output.="</table>\r\n\r\n\r\n";
	}
	

	// PROPS
	$props = array_slice($result['items'][0]['displayGroups'],1);
	$group_number = 0;
	foreach($props as $group)
    {
		$group_number++;
	
		// Insert button
        $output.= "<h3 id=\"outcome_group_".$group_number."\" style=\"display: inline-block; margin-right: 10px;\">".$group['description']."</h3>\r\n";
		$output.=make_button_link("Go Top", "#");

	
        foreach($group["itemList"] as $item)
        {
           $period_type = "";
           if(strpos($item["periodType"], "Half")!== false              
              || strpos($item["periodType"], "Set")!== false
              || strpos($item["periodType"], "Inning")!== false
              //|| strpos($item["periodType"], "Regulation")!== false
              || ( (strpos($result['items'][0]['sport'], "SOCC") !== false) 
                && (strpos($item["periodType"], "Match") !== false)	)
             )
           {
               $period_type = " - ".$item["periodType"];
           }
           
		   
		   // Get td number (maximum is max_n*2, but can be lower)
		   $number_outcomes = count($item["outcomes"]);
		   
		   $n_per_line = $max_n;
		   
		   if($number_outcomes < $n_per_line)
		   {
				if($n_per_line%2!=0)
					$n_per_line -= 1;
				
				while($number_outcomes < $n_per_line)
					$n_per_line = $n_per_line / 2;
		   }
		   
		   $td_per_line = $n_per_line * 2;
		   
		   
		   // Fix td size for 2 and 3 
		   if($td_per_line == 4)
			   $td_size = ' width="25%"';
		   else if ($td_per_line == 6)
			   $td_size = ' width="16.6%"';
			else $td_size='';
		   
			   // Header
			$output.="<table cellspacing=\"0\" class=".$table_class.">\r\n";
			$output.="\t<tr>\r\n";
			$output.="\t\t<td colspan=\"".$td_per_line."\">".
			htmlentities($item["description"], ENT_COMPAT, 'UTF-8')
			."</td>\r\n";
			$output.="\t</tr>\r\n";
			
			
		
			/// Outcomes
			$i = 0;
			$output.="\t<tr>\r\n";
			foreach($item["outcomes"] as $outcome)
            {
				
				$output.="\t\t<td".$td_size.">".$outcome["description"]."</td><td".$td_size.">".GetOutcomePrice($outcome)."</td>\r\n";
				$i++;
				if($i==$n_per_line)
				{
					$i=0;
					$output.="\t</tr>\r\n\t<tr>\r\n";
				}
					
            }
			
			// Fill rest of line with tds
			if($i!=0)
				while($i!=$n_per_line)
				{
					$i++;
					$output.="<td></td><td></td>";
				}
			
			$output.="\t</tr>\r\n";
			$output.="</table>\r\n\r\n\r\n";
        }
    }
    
    echo str_replace("&amp;frac12;","&frac12;",htmlspecialchars($output));

?>
    
    </pre>
</div>
    
    
<h1> RESULTADO:</h1>
    
<div style="color: #fff; margin: 0 auto; background: <?php echo $bg; ?>; 
	width: <?php echo $width; ?>px; padding: 30px <?php echo $padding; ?>px;">	
        <? echo $output; 
} ?>
   </div>