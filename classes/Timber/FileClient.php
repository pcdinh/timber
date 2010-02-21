<?php

class Timber_FileClient extends Timber_Client
{
	public function __construct($path, $socket)
	{
		parent::__construct($socket);
	}

	public function handleCommand($command)
	{
		var_dump($command->payload->message);
	}
}
