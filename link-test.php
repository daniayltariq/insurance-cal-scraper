<?php

ini_set('display_errors', E_ALL);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
error_reporting(0);

include_once('simple_html_dom.php');
require_once (__DIR__ . '/vendor/autoload.php');
use Rct567\DomQuery\DomQuery;
use HeadlessChromium\BrowserFactory;

    function get_web_page( $url )
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

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
            // CURLOPT_PROXY          => 'zproxy.lum-superproxy.io',
            // CURLOPT_PROXYPORT      => '22225',
            // CURLOPT_PROXYUSERPWD   => 'lum-customer-hl_fa848026-zone-daniel_sahlin_zone-country-se:0xwx5ytxlfcc',
            // CURLOPT_HTTPPROXYTUNNEL=> 1,
        );
        
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        return $header['content'] = $content;
        // return $header;
    }


    if (1) {
        
        echo get_web_page('https://careers-salemstate.icims.com/jobs/search?pr=0&searchRadius=200&in_iframe=1');

    }