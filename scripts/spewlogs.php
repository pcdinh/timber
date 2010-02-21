#!/usr/bin/env php
<?php

// simple script to spew fake messages
if(!$sock = @fsockopen("127.0.0.1", 11124, $errno, $errstr))
{
	die("failed to connect: $errstr\n");
}

$payload = array(
	'level' => 'INFO',
	'message' => "The height of a full-grown\n, full-size llama is between 1.7 meters and 1.8 meters tall.",
	'timestamp' => time(),
	);

$json = json_encode($payload);
$preamble = sprintf("INFO example.org myapp myservice %d",strlen($json));

fwrite($sock, $preamble."\n");
fwrite($sock, $json."\n");

printf(">> %s",fread($sock, 1024));
fclose($sock);
