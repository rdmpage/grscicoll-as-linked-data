<?php

function get($url, $format = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	curl_close($ch);
	
	return $response;
}


$done = false;
$limit = 1000;
$offset = 0;

$mode = 'collection';
//$mode = 'institution';

$api_url = 'https://api.gbif.org/v1/grscicoll/' . $mode;

while (!$done)
{
	$url = $api_url . "?limit=$limit&offset=$offset";
	
	echo $url . "\n";
	
	$json = get($url, 'application/json');
	
	$obj = json_decode($json);
	
	if ($obj)
	{
		$offset += $limit;
		file_put_contents($mode . '/' . $offset . '.json', $json);
		$done = $obj->endOfRecords;
	}
	else
	{
		$done = false;
	}
}