<?php

/**
 * Simple synchronous client
 */
abstract class Timber_Client
{
	private $_socket, $_parser;

	/**
	 * Constructor
	 */
	public function __construct($socket)
	{
		$this->_socket = $socket;
		$this->_parser = new Timber_Parser();
	}

	/**
	 * Reads a command object from the socket
	 */
	public function readCommand()
	{
		while($line = fgets($this->_socket))
		{
			$command = $this->_parser->parse($line);

			if($command->payloadsize)
			{
				$payload = fread($this->_socket, $command->payloadsize+1);
				$command->payload = json_decode($payload);
			}

			// defer to template method
			$this->handleCommand($command);

			// write a response
			//fwrite($this->_socket, sprintf("OK %s\n",$command->payloadsize));
		}
	}

	public function loop()
	{
		while($command = $this->readCommand());
	}

	/**
	 * Template method to handle a command
	 */
	abstract public function handleCommand($command);
}
