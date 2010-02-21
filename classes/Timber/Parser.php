<?php

/**
 * The command parser
 */
class Timber_Parser
{
	const COMMAND_LOG='LOG';
	const COMMAND_RELAY='RELAY';

	/**
	 * Parses a command
	 */
	public function parse($line)
	{
		$tokens = explode(' ', rtrim($line,"\n"));

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
			case 'OK':
				return $this->_parseOkCommand($tokens);
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
			'name' => self::COMMAND_LOG,
			'level' => strtoupper($tokens[0]),
			'host' => $tokens[1],
			'application' => $tokens[2],
			'subsystem' => $tokens[3],
			'payloadsize' => $tokens[4],
			'payload' => false,
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
			'name' => self::COMMAND_RELAY,
			'payloadsize' => false,
			'payload' => false
			);
	}
}
