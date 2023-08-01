<?php

ini_set('display_errors', 0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
error_reporting(0);


require_once (__DIR__ . '/vendor/autoload.php');
use Rct567\DomQuery\DomQuery;
use HeadlessChromium\BrowserFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
include_once('simple_html_dom.php');


    function getWebPage( $url )
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        // $url = urlencode('https://www.amazon.com/dp/B00JITDVD2');

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
            CURLOPT_POST           => false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );
        
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        return $content;
    }


    function postData($url, $jsonData){

        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "POST",        //set request type post or get
            CURLOPT_POST           => true,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_HTTPHEADER     => array(
                                        'Bipro-User: hansemerkur',
                                        'Content-Type: application/json',
                                    ),

        );

        $ch = curl_init( $url );
        curl_setopt_array( $ch, $options );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;

    }


    function getPackageData( $url, $tier, $race, $dob, $tomorrow, $data_type, $stage = null, $package_type = null )
    {
        
        $jsonFile = './source/'.$tier.'.json';
        $jsonData = file_get_contents($jsonFile);

        $data = json_decode($jsonData, true);

        if($data_type == 'package-details'){
            $data['tarifierung']['gegenstand'][0]['rasse'] = $race;
            $data['tarifierung']['gegenstand'][0]['geburtsdatum'] = $dob;
            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][1]['elementarprodukt'][0]['selbstbeteiligung'][0]['wert'] = ($tier == 'tier-0') ? 0 : 250;
            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][2]['elementarprodukt'][0]['selbstbeteiligung'][0]['wert'] = ($tier == 'tier-0') ? 0 : 250;
        }
        elseif($data_type == 'final-details'){
            $data['tarifierung']['verkaufsprodukt'][0]['versicherungsdauer']['beginn'] = $tomorrow;
            $data['tarifierung']['verkaufsprodukt'][0]['versicherungsdauer']['ende'] = date("Y-m-d", strtotime("+1 year", strtotime("+1 day")));
            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][0]['versicherungsdauer']['beginn'] = $tomorrow;
            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][0]['versicherungsdauer']['ende'] = date("Y-m-d", strtotime("+1 year", strtotime("+1 day")));
            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][0]['elementarprodukt'][0]['selbstbeteiligung'][0]['wert'] = ($stage == 'tier-0-final') ? 0 : 250;
            $data['tarifierung']['gegenstand'][0]['rasse'][0] = trim($race);
            $data['tarifierung']['gegenstand'][0]['geburtsdatum'] = $dob;
            $data['tarifierung']['gegenstand'][0]['registriernummer'] = rand(10000, 999999);

            $data['tarifierung']['verkaufsprodukt'][0]['produkt'][0]['paket'][0]['value'] = str_replace("-zahn", "", $package_type);
        }

        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        // print_r($jsonData);
        // die();

        $response = postData($url, $jsonData);

        if ($response !== false) {
            
            $responseData = json_decode($response, true);
            
            $response = [];
            
            if($data_type == 'package-details'){

                $package_value = '';
                $package_type = '';
                $zahn_value = '';
                $tier_2 = false;
                
                // echo "\n" . "\n";
                // echo '---'.$dob.'---';
                // echo "\n";


                foreach ($responseData['tarifierung']['verkaufsprodukt'][0]['produkt'] as $key => $value) {
                       
                    if($value['paket'][0]['value'] == 'Smart' || $value['paket'][0]['value'] == 'Easy' || $value['paket'][0]['value'] == 'Best'){
                        
                        $package_type = $value['paket'][0]['value'];
                        $package_value = $value['elementarprodukt'][0]['beitrag'][1]['betrag']['betrag'];
                        
                        $smallest_zahn = 999999;
                        foreach ($value['elementarprodukt'] as $key => $val1) {
                            foreach ($val1['beitrag'] as $key => $val2) {
                                if($val2['artID']['value'] == '01')
                                    if($smallest_zahn > $val2['betrag']['betrag'] && $val2['betrag']['betrag'] > 0)
                                        $smallest_zahn = $val2['betrag']['betrag'];
                            }
                        }

                        if($smallest_zahn == $package_value)
                            $zahn_value = 0;
                        else
                            $zahn_value = $smallest_zahn;

                    }
                    elseif($value['paket'][0]['value'] == 'Premium' || $value['paket'][0]['value'] == 'PremiumPlus'){
                        
                        $package_type = $value['paket'][0]['value'];
                        $smallest_pkg = 999999;
                        foreach ($value['beitrag'] as $key => $val) {
                                if($val['artID']['value'] == '01')
                                    if($smallest_pkg > $val['betrag']['betrag'])
                                        $smallest_pkg = $val['betrag']['betrag'];
                        }
                        $package_value = $smallest_pkg;

                        $smallest_zahn = 999999;
                        foreach ($value['elementarprodukt'] as $key => $val1) {
                            foreach ($val1['beitrag'] as $key => $val2) {
                                if($val2['artID']['value'] == '01')
                                    if($smallest_zahn > $val2['betrag']['betrag'])
                                        $smallest_zahn = $val2['betrag']['betrag'];
                            }
                        }
                        $zahn_value = $smallest_zahn;

                        $tier_2 = true;

                    }
                

                    $zahn_value = ($zahn_value == '999999') ? 0 : $zahn_value;
                    $package_value = $package_value - (($tier_2) ? $zahn_value : 0);

                    // echo $package_value;
                    // echo "\n";
                    // echo $zahn_value;
                    // echo "\n";
                    // echo $package_type;
                    // echo "\n". "\n". "\n";


                    $response[$package_type] = $package_value;
                    $response[$package_type.'_zahn'] = $zahn_value + $package_value;
                    // $response[$package_type.'_zahn'] = $zahn_value + (($tier_2) ? $package_value : 0);

                    $tier_2 = false;

                }
            }

            if($data_type == 'final-details'){

                // echo "\n" . "\n";
                // echo '---'.$dob.'---';
                // echo "\n";

                $betrag = $responseData['tarifierung']['verkaufsprodukt'][0]['produkt'][0]['beitrag'];
                
                foreach (array_reverse($betrag) as $key => $value) {
                    if (array_key_exists("erhebung", $value)) {
                        
                        $val = $value['betrag']['betrag'];
                        $dateString = $value['erhebung']['beginn'];
                        $date = str_replace("T00:00", "", $dateString);
                    
                        // echo $val;
                        // echo '--';
                        // echo $date;
                        // echo "\n" ."\n";


                        $response[$key]['val']  = $val;
                        $response[$key]['date'] = $date;

                    }
                }

                // die();
            }

            return $response;

        
        } 
        else {
            echo 'Failed to make the API request.';
        }
        
    }


    function readDataFromExcel($id){

        $apiKey = 'AIzaSyBl6GhPrgbuwKGiH_dBlEIU7b_yptQLaI0';

        $spreadsheetId = '1aP03rJyv3ggo5LUhvplz2ILNTgR_AMGjDRBwSpMTx2o';

        $sheetName = 'Tabellenblatt1';

        $url = "https://sheets.googleapis.com/v4/spreadsheets/$spreadsheetId/values/$sheetName?key=$apiKey";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['values'])) {
            foreach ($data['values'] as $row)
            {
                // Each $row is an array of values from the row.
                // Access individual values using $row[0], $row[1], etc.
                // echo implode(', ', $row) . "\n";
                echo $row[0] . "\n";
            }
        }
        else
        {
            echo 'No data found.';
        }
    }



    function getRaceData(){

        $races = [];
        for ($i = 97; $i <= 122; $i++) {
        // for ($i = 97; $i <= 97; $i++) {
            
            $alphabet = chr($i);

            $url = 'https://secure2.hansemerkur.de/service-bipro-dispatcher/ui/tierrassen/Hund/'.$alphabet;

            // echo $url . "\n". "\n";
            
            $races = array_merge($races, json_decode(getWebPage($url), true));

        }

        return (array_unique($races));
    }


    $races = getRaceData();

    $url = 'https://secure2.hansemerkur.de/service-bipro-dispatcher/tarifierung/quote';

    sort($races);
    $needle = false;

    // print_r($races);die();

    foreach ($races as $key => $race){

        if(trim($race) == 'Bosnische Bracke'){
            $needle = true;
        }

        if($needle){
        
            for ($i = 0; $i < 9; $i++){
            // for ($i = 0; $i < 3; $i++){
                    

                $year = date("Y");

                $dob = $year - $i . '-07-01';
                $tomorrow = date("Y-m-d", strtotime("+1 day"));

                // Temp
                // $race = 'Mischling ab 45cm Schulterhöhe';

                $a = getPackageData($url, 'tier-0', $race, $dob, $tomorrow, 'package-details');
                $a_with_zahn_premium     = getPackageData($url, 'final-zahn', $race, $dob, $tomorrow, 'final-details', 'tier-0-final', 'Premium-zahn');
                $a_without_zahn_premium  = getPackageData($url, 'final', $race, $dob, $tomorrow, 'final-details', 'tier-0-final', 'Premium');
                $a_with_zahn_premplus    = getPackageData($url, 'final-zahn', $race, $dob, $tomorrow, 'final-details', 'tier-0-final', 'PremiumPlus-zahn');
                $a_without_zahn_premplus = getPackageData($url, 'final', $race, $dob, $tomorrow, 'final-details', 'tier-0-final', 'PremiumPlus');



                $b = getPackageData($url, 'tier-250', $race, $dob, $tomorrow, 'package-details');
                $b_with_zahn_premium      = getPackageData($url, 'final-zahn', $race, $dob, $tomorrow, 'final-details', 'tier-250-final', 'Premium-zahn');
                $b_without_zahn_premium   = getPackageData($url, 'final', $race, $dob, $tomorrow, 'final-details', 'tier-250-final', 'Premium');
                $b_with_zahn_premplus     = getPackageData($url, 'final-zahn', $race, $dob, $tomorrow, 'final-details', 'tier-250-final', 'PremiumPlus-zahn');
                $b_without_zahn_premplus  = getPackageData($url, 'final', $race, $dob, $tomorrow, 'final-details', 'tier-250-final', 'PremiumPlus');

                // print_r($a);
                // print_r($d_with_zahn);
                // print_r($d_without_zahn);
                
                // die();



                $filename = 'uploads/file7.xlsx'; 
                $spreadsheet = IOFactory::load($filename);

                $sheet = $spreadsheet->getActiveSheet();

                $newData = array(
                    array(
                        'A' => $race,
                        'B' => (string)$i,
                        'C' => $b['Smart'] ?? '',
                        'D' => $b['Easy'] ?? '',
                        'E' => $b['Easy_zahn'] ?? '',
                        'F' => $b['Best'] ?? '',
                        'G' => $b['Best_zahn'] ?? '',
                        'H' => $a['Easy'] ?? '',
                        'I' => $a['Easy_zahn'] ?? '',
                        'J' => $a['Best'] ?? '',
                        'K' => $a['Best_zahn'] ?? '',
                        'L' => $b['Premium'] ?? '',
                        'M' => $b_without_zahn_premium[2]['date'] ?? '',
                        'N' => $b_without_zahn_premium[2]['val'] ?? '',
                        'O' => $b_without_zahn_premium[1]['date'] ?? '',
                        'P' => $b_without_zahn_premium[1]['val'] ?? '',
                        'Q' => $b_without_zahn_premium[0]['date'] ?? '',
                        'R' => $b_without_zahn_premium[0]['val'] ?? '',
                        'S' => $b['Premium_zahn'] ?? '',
                        'T' => $b_with_zahn_premium[2]['date'] ?? '',
                        'U' => $b_with_zahn_premium[2]['val'] ?? '',
                        'V' => $b_with_zahn_premium[1]['date'] ?? '',
                        'W' => $b_with_zahn_premium[1]['val'] ?? '',
                        'X' => $b_with_zahn_premium[0]['date'] ?? '',
                        'Y' => $b_with_zahn_premium[0]['val'] ?? '',
                        'Z' => $b['PremiumPlus'] ?? '',
                        'AA' => $b_without_zahn_premplus[2]['date'] ?? '',
                        'AB' => $b_without_zahn_premplus[2]['val'] ?? '',
                        'AC' => $b_without_zahn_premplus[1]['date'] ?? '',
                        'AD' => $b_without_zahn_premplus[1]['val'] ?? '',
                        'AE' => $b_without_zahn_premplus[0]['date'] ?? '',
                        'AF' => $b_without_zahn_premplus[0]['val'] ?? '',
                        'AG' => $b['PremiumPlus_zahn'] ?? '',
                        'AH' => $b_with_zahn_premplus[2]['date'] ?? '',
                        'AI' => $b_with_zahn_premplus[2]['val'] ?? '',
                        'AJ' => $b_with_zahn_premplus[1]['date'] ?? '',
                        'AK' => $b_with_zahn_premplus[1]['val'] ?? '',
                        'AL' => $b_with_zahn_premplus[0]['date'] ?? '',
                        'AM' => $b_with_zahn_premplus[0]['val'] ?? '',
                        'AN' => $a['Premium'] ?? '',
                        'AO' => $a_without_zahn_premium[2]['date'] ?? '',
                        'AP' => $a_without_zahn_premium[2]['val'] ?? '',
                        'AQ' => $a_without_zahn_premium[1]['date'] ?? '',
                        'AR' => $a_without_zahn_premium[1]['val'] ?? '',
                        'AS' => $a_without_zahn_premium[0]['date'] ?? '',
                        'AT' => $a_without_zahn_premium[0]['val'] ?? '',
                        'AU' => $a['Premium_zahn'] ?? '',
                        'AV' => $a_with_zahn_premium[2]['date'] ?? '',
                        'AW' => $a_with_zahn_premium[2]['val'] ?? '',
                        'AX' => $a_with_zahn_premium[1]['date'] ?? '',
                        'AY' => $a_with_zahn_premium[1]['val'] ?? '',
                        'AZ' => $a_with_zahn_premium[0]['date'] ?? '',
                        'BA' => $a_with_zahn_premium[0]['val'] ?? '',
                        'BB' => $a['PremiumPlus'] ?? '',
                        'BC' => $a_without_zahn_premplus[2]['date'] ?? '',
                        'BD' => $a_without_zahn_premplus[2]['val'] ?? '',
                        'BE' => $a_without_zahn_premplus[1]['date'] ?? '',
                        'BF' => $a_without_zahn_premplus[1]['val'] ?? '',
                        'BG' => $a_without_zahn_premplus[0]['date'] ?? '',
                        'BH' => $a_without_zahn_premplus[0]['val'] ?? '',
                        'BI' => $a['PremiumPlus_zahn'] ?? '',
                        'BJ' => $a_with_zahn_premplus[2]['date'] ?? '',
                        'BK' => $a_with_zahn_premplus[2]['val'] ?? '',
                        'BL' => $a_with_zahn_premplus[1]['date'] ?? '',
                        'BM' => $a_with_zahn_premplus[1]['val'] ?? '',
                        'BN' => $a_with_zahn_premplus[0]['date'] ?? '',
                        'BO' => $a_with_zahn_premplus[0]['val'] ?? '',
                        
                        
                    )
                );


                $highestRow = $sheet->getHighestRow();
                $nextRow = $highestRow + 1;

                foreach ($newData as $dataRow) {
                    $sheet->fromArray($dataRow, null, 'A' . $nextRow);
                    $nextRow++;
                }

                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filename);


                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                
                // die();

            }
        }
        
        // die();
    }