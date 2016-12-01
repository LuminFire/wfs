<?php

$start_url = $argv[1];
$cookie = $argv[2];

$queue = array( $start_url );

// Create a stream
$opts = array(
	'http'=>array(
		'method'=>"GET",
		'header'=>"Accept-language: en\r\n" .
		"Cookie: JSESSIONID=$cookie\r\n"
	)
);

$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
// $file = file_get_contents('http://www.example.com/', false, $context);

$baseurl = dirname( $start_url ) . '/';

while( $cur_url = array_shift( $queue ) ) {

	$a = 1;

	if ( strpos($cur_url, $baseurl) === false ) {
		print "Out of path: $cur_url\n";
	}

	$output_file = str_replace( $baseurl, '', $cur_url );

	if ( file_exists( $output_file ) ) {
		continue;
	}

	$output_dir = dirname( $output_file );

	if ( $output_dir !== '.' && $output_dir !== '/' ) {
		mkdir( $output_dir, 0777, true );
	}

	$xml = file_get_contents( $cur_url, false, $context );

	file_put_contents( $output_file, $xml );

	$xmld = simplexml_load_string( $xml );

	if ( !$xmld ) {
		print $cur_url . "\n";
		continue;
	}

	$res = $xmld->xpath('//*[@schemaLocation]');
	foreach($res as $newLoc){
		$schemaLocation = (string)$newLoc['schemaLocation'];
		if ( filter_var($schemaLocation, FILTER_VALIDATE_URL) === false) {
			$schemaLocation = dirname( $cur_url ) . '/' . $schemaLocation;

			$schemaLocation = canonicalize( $schemaLocation );
		}
		$queue[] = $schemaLocation;
	}
}

function canonicalize($address) {
	$address = explode('/', $address);
	$keys = array_keys($address, '..');

	foreach($keys AS $keypos => $key)
	{
		array_splice($address, $key - ($keypos * 2 + 1), 2);
	}

	$address = implode('/', $address);
	$address = str_replace('./', '', $address);
	return $address;
}
