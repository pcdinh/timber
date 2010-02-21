<?php

/**
 * The core protocol class that checks the connection buffer for complete commands
 * and returns them for processing
 */
class Timber_Protocol
{
	const COMMAND_LOG='log';
	const COMMAND_RELAY='relay';

	/**
	 * Reads a command and a possible payload from the connection
	 * @return either an object or false
	 */
	public function readCommand($connection)
	{
		if($connection->hasLine())
		{
			$line = $connection->peekLine();
			$payload = false;

			// try and parse a command out
			if($command = $this->_parseCommand($line))
			{
				// return if we have no payload
				if(!$command->payload)
				{
					$connection->readLine();
					return array($command, false);
				}
				// otherwise wait till we have a payload
				else if($command->payload && $connection->has(strlen($line)+$command->payload+2))
				{
					$connection->readLine();
					$payload = rtrim($connection->read($command->payload+1),"\n");

					// TODO: gzip decoding
					$payload = json_decode($payload);

					return array($command, $payload);
				}
				else
				{
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Parses a command line from the connection
	 */
	private function _parseCommand($line)
	{
		$tokens = explode(' ', $line);

		// dispatch to a known parser
		switch(strtoupper($tokens[0]))
		{
			case 'TRACE':
			case 'INFO':
			case 'WARN':
			case 'ERROR':
			case 'FATAL':
				return $this->_parseLogCommand($tokens);
			case 'RELAY':
				return $this->_parseRelayCommand($tokens);
		}

		throw new Timber_ProtocolException("Unknown command ".$tokens[0]);
	}

	/**
	 * Parses a LOG command tokens
	 */
	private function _parseLogCommand($tokens)
	{
		if(count($tokens) < 5 || count($tokens) > 6)
		{
			throw new Timber_ProtocolException(
				"Incorrect parameter count for LOG command"
				);
		}

		return (object) array(
			'command' => self::COMMAND_LOG,
			'level' => strtoupper($tokens[0]),
			'host' => $tokens[1],
			'application' => $tokens[2],
			'subsystem' => $tokens[3],
			'payload' => $tokens[4]
			);
	}

	/**
	 * Parses a RELAY command tokens
	 */
	private function _parseRelayCommand($tokens)
	{
		if(count($tokens) != 1)
		{
			throw new Timber_ProtocolException(
				"Incorrect parameter count for RELAY command"
				);
		}

		return (object) array(
			'command' => self::COMMAND_RELAY,
			'payload' => false,
			);
	}
}
