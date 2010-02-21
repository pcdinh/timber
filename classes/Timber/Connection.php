<?php

/**
 * Represents a connection in the Timber_Server
 */
class Timber_Connection
{
	private $_readBuffer, $_writeBuffer, $_parser, $_command;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_readBuffer = new Timber_Buffer();
		$this->_writeBuffer = new Timber_Buffer();
		$this->_parser = new Timber_Parser();
	}

	/**
	 * Reads $bytes in to the connection's buffers from a stream
	 */
	public function read($stream, $bytes)
	{
		$this->_readBuffer->write($stream->read($bytes));
	}

	/**
	 * Writes from the connections buffers to the stream
	 * @return int the number of bytes remaining to write
	 */
	public function write($stream)
	{
		$stream->write($this->_writeBuffer->read());
		return $this->_writeBuffer->length();
	}

	/**
	 * Returns a command from the read buffer
	 */
	public function getCommand()
	{
		// if no command exists, look for one
		if(!isset($this->_command) && $line = $this->_readBuffer->readLine())
		{
			$this->_command = $this->_parser->parse($line);
		}

		// check if we can read a payload
		if(isset($this->_command) && $this->_command->payloadsize != false)
		{
			if($payload = $this->_readBuffer->read($this->_command->payloadsize, true))
			{
				$this->_command->payload = json_decode($payload);
			}
		}

		// try and return a command
		if(isset($this->_command) && (!$this->_command->payloadsize || $this->_command->payload))
		{
			$command = $this->_command;
			unset($this->_command);
			return $command;
		}
	}

	/**
	 * Returns an OK response
	 */
	public function ok($bytes)
	{
		$this->_writeBuffer->write(sprintf("OK %d\n", $bytes));
	}

	/**
	 * Relays a command to the connection
	 */
	public function relay($command)
	{
		// TODO: better strategy for serializing commands
		$string = sprintf("%s %s %s %s %d",
			$command->level,
			$command->host,
			$command->application,
			$command->subsystem,
			$command->payloadsize
			);

		$this->_writeBuffer->write("$string\n");
		$this->_writeBuffer->write(json_encode($command->payload)."\n");
	}
}
