#!/usr/bin/env php
<?php

$qty = 100;
$processes = 25;
$children = array();
$starttime = microtime(true);

// build a log message
$payload = array(
	'level' => 'INFO',
	'message' => "The height of a full-grown, full-size llama is between 1.7 meters and 1.8 meters tall.",
	'timestamp' => time(),
	);

$json = json_encode($payload);
$preamble = sprintf("INFO example.org myapp myservice %d",strlen($json));

// spawn off processes
for($i=1; $i<=$processes; $i++)
{
	$pid = pcntl_fork();

	if (!$pid)
	{
		for($i=0; $i<$qty; $i++)
		{
			if(!$sock = @fsockopen("127.0.0.1", 11124))
			{
				die("connection failed");
			}

			fwrite($sock, $preamble."\n");
			fwrite($sock, $json."\n");

			fclose($sock);
		}

		exit(0);
	}
	else
	{
		$children[] = $pid;
	}
}

// wait for them to finish
foreach($children as $child)
{
	pcntl_waitpid($child, $status);
}

$stoptime = microtime(true);

printf("sent %d log messages in %.4f seconds, %0.2f messages per second\n",
	$qty * $processes,
	$stoptime - $starttime,
	($qty * $processes) / ($stoptime - $starttime)
	);
