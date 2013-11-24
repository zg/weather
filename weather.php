<?php
date_default_timezone_set('America/New_York');
$output = '';
if((isset($_GET['city']) && isset($_GET['state']) && strlen($_GET['city']) && strlen($_GET['state'])) || (isset($_GET['zip']) && strlen($_GET['zip']))){
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
    if(isset($_GET['city']) && isset($_GET['state']) && strlen($_GET['city']) && strlen($_GET['state'])){
        $url .= str_replace(' ','%20',$_GET['city']).',%20';
        $url .= str_replace(' ','%20',$_GET['state']);
    }
    if(isset($_GET['zip']) && strlen($_GET['zip'])){
        if(substr($url,-1) != '=')
            $url .= '+';
        $url .= $_GET['zip'];
    }
    $url .= '&sensor=false';
    $json_request = file_get_contents($url);
    $response = json_decode($json_request);
    $formatted_address = '';
    $latitude = 0;
    $longitude = 0;
    if($response->status == 'OK'){
        $formatted_address = $response->results[0]->formatted_address;
        $latitude = $response->results[0]->geometry->location->lat;
        $longitude = $response->results[0]->geometry->location->lng;
        $url = 'http://forecast.weather.gov/MapClick.php?lat='.$latitude.'&lon='.$longitude.'&unit=1&lg=english&FcstType=json';
        $json_request = file_get_contents($url);
        $response = json_decode($json_request);
        if(!is_null($response)){
            if(isset($_GET['format']) && $_GET['format'] == 'sms'){
                for($i = 0; $i < 1; $i++){
                    $time = $response->time->startPeriodName[$i];
                    $temp = $response->data->temperature[$i];
                    $weather = $response->data->weather[$i];
                    $text = $response->data->text[$i];
                    $output .= $time.': '.$text."\n";
        //            $output .= $time.': '.$temp.chr(176).'C ('.$weather.')'."\n";
                }
            } else {
                $output .= '<b><p>Weather for '.$formatted_address.'</p></b>'."\n";
                for($i = 0; $i < 10; $i++){
                    $time = $response->time->startPeriodName[$i];
                    $temp = $response->data->temperature[$i];
                    $weather = $response->data->weather[$i];
                    $text = $response->data->text[$i];
                    $output .= '<br><b>'.$time.'</b>: '.$text."\n";
        //            $output .= $time.': '.$temp.chr(176).'C ('.$weather.')'."\n";
                }
            }
            $output = rtrim($output);
            $chunks = explode("||||",wordwrap($output,140,'||||',false));
            $total = count($chunks);
            foreach($chunks as $page => $chunk){
                require_once('class.phpmailer.php');
                $mail = new PHPMailer;
                $mail->IsSMTP();
                $mail->Host = '';
                $mail->SMTPAuth = true;
                $mail->Host = '';
                $mail->Port = 0;
                $mail->Username = "";
                $mail->Password = "";
                $mail->SetFrom('');
                $mail->AddAddress('');
                $mail->Subject = date('M j').' Weather';
                $mail->MsgHTML(sprintf("(%d/%d) %s",$page+1,$total,$chunk));
                if(!$mail->Send()){
                    echo 'Mailer Error: '.$mail->ErrorInfo;
                }
            }
        } else {
            $output = '<b><font color="red">No weather data found.</font></b>';
        }
    }
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Weather Report</title>
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php if(strlen($output) != 0){ echo $output."\n"; } else { ?>
<p>Weather Report</p>
<form method="get">
City &amp; State: <input type="text" name="city"> <input type="text" size="3" name="state"><br>
- OR -<br>
ZIP Code: <input type="text" size="5" name="zip"><br>
<input type="submit" value="Go">
</form><?php } ?>
<hr>
<small>Credits to NOAA and Google for weather and geocoding.</small>
</body>
</html>
