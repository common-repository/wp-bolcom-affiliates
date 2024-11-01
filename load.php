<?php
if( ! isset($_REQUEST['file']) ) {
        header("HTTP/1.1 500 Internal Server Error");
        die();
}

$url = 'https://plugins.biz2web.nl/load/';
$_REQUEST['root'] = urlencode(home_url()).'/?pagename=bolcom-load';
$_REQUEST['bcdir'] = urlencode(plugins_url('', __FILE__));
$_REQUEST['lang'] = get_locale();
$_REQUEST['auth_key'] = get_option('wpbol_auth_key');
if( isset($_REQUEST['pagename']) )
        unset($_REQUEST['pagename']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $_REQUEST);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$return = curl_exec($ch);
header("Content-Type: ".curl_getinfo($ch, CURLINFO_CONTENT_TYPE)."; charset=UTF-8");
echo($return);
curl_close($ch);
