<?
header("Content-type: text/html; charset=UTF-8");

require('./DOM/simple_html_dom.php');



function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

?>
<body bgcolor="#FFEEEE">
<br>
<center>
<h1>Extractor v2.5</h1>
<!--Made by JFK-->
<form action="index.php" method="post">
    Art&iacute;culo: <input type="text" name="input_url"/><br>
    Enlace: <input type="text" name="output_url"/><br>
    <input type="submit">
</form>

</center>

<br/>

<?php


function get_cookies($url)
{ 
  $agent= 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
    
  $ch = curl_init();
  $timeout = 10;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  
		$data = curl_exec($ch);
		curl_close($ch);

		echo "cookies: " . $data;
		preg_match_all('|Set-Cookie: (.*);|U', $data, $matches);   
		$cookies = implode('; ', $matches[1]);
		
		
  return $cookies;
	
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
  
	curl_setopt($ch, CURLOPT_COOKIE, get_cookies($url));


  $data = curl_exec($ch);
   echo "Curl result: ".curl_errno($ch);
  curl_close($ch);
  return $data;
}


if(isset($_POST['input_url'])) {

        date_default_timezone_set('GMT');
	// REGISTER IP AND URL USAGE.
	$myfile = fopen("log.txt", "a") or die("Error on server");
	$txt = "[".date("d-m-Y (H:i:s)") ."] - ";
        $txt .= get_client_ip();
	$txt.=" => ".$_POST['input_url']."\r\n";
	fwrite($myfile, $txt);
	fclose($myfile);

    
    // GET HTML
    $html = get_html($_POST['input_url']);   

    // LOAD HTML DOM
    $html = str_get_html($html);
    
    
    // Get last script text which contents data
    $result = end($html->find('script'))->innertext;
    
    // Prepare script to load into json decoder
    $result = strstr($result, "{");
    $result = substr($result, 0, -1);
    
    // Javascript to PHP array
    $result = json_decode($result, true);
    
    echo "<h1>Report de \"".$result['items'][0]['description']."\"</h1>";
    ?>


<h1> Copiar el c&oacute;digo:</h1>
<div style="background-color:lightgray">
    
    <pre>
<?php

    // CODE GENERATION

    $output = "";

    $ignorefirst=0;
    foreach($result['items'][0]['displayGroups'] as $group)
    {
        // Dirty'n fast way to ignore first element
        if($ignorefirst==0) { $ignorefirst++; continue; }

        //$output.= "<h2>".$group['description']."</h2>\r\n";
        foreach($group["itemList"] as $item)
        {
           $period_type = "";
           if(strpos($item["periodType"], "Half")!== false              
              || strpos($item["periodType"], "Set")!== false
              || strpos($item["periodType"], "Inning")!== false
              || strpos($item["periodType"], "Regulation")!== false
              || ( (strpos($result['items'][0]['sport'], "SOCC") !== false) 
                && (strpos($item["periodType"], "Match") !== false)	)
                   )
           {
               $period_type = " - ".$item["periodType"];
           }
           
           $output.= "\t<p><strong>".htmlentities($result['items'][0]['description'], ENT_COMPAT, 'UTF-8')."<br/>\r\n\t\t".htmlentities($item["description"], ENT_COMPAT, 'UTF-8').$period_type."</strong></br>\r\n";
            
			
			/// END-LEVEL LIST
			foreach($item["outcomes"] as $outcome)
            {
                if ($outcome["description"]=="test") { continue; };
                   
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
                        } else
                        {
                            $value = abs(ceil($n_handicap));
                            if(!$o_or_u) $handicap = "-";
                            if($value != "0") $handicap.=$value;
                        }
                        
                        // Write down half
                        if($half_decimal)
                            $handicap .= "&frac12;";


		}

                }
                
                
                // Add format
                $output.="\t\t";
                
                
                // Create output
                $american = $outcome["price"]["american"];
                if($american == "") $american ="&ndash;";    // no number

                
                if($sethandicap)
                {
                    // Add parenthesis
                      $output.= htmlspecialchars($outcome["description"])." &nbsp;&nbsp;<a href=\"".$_POST['output_url']."\" rel=\"nofollow\">".$handicap." (".$american.")".$type."</a>";
                } else {
                    // No parenthesis
                      $output.= htmlentities($outcome["description"], ENT_COMPAT, 'UTF-8')." &nbsp;&nbsp;<a href=\"".$_POST['output_url']."\" rel=\"nofollow\">".$american."</a>";
                }
				
				// Add <br/> only if not last element
				if(($outcome === end($item["outcomes"])) == false)
					$output.="<br/>";
				
				$output.="\r\n";
				
            }
            $output.= "\t</p>\r\n";
        }
    }
    
    echo str_replace("&amp;frac12;","&frac12;",htmlspecialchars($output));

?>
    
    </pre>
</div>
    
    
<h1> RESULTADO:</h1>
    
        <? echo $output; 
}


?>
         
