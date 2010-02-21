<?php

/**
 * A connection buffer
 */
class Timber_Connection
{
	const READ_BUFFER='read';
	const WRITE_BUFFER='write';

	private $_buffer=array('read'=>'', 'write'=>'');

	/**
	 * Saves data in a buffer, either read or write
	 * @param string $buffer The buffer to read, either read or write
	 */
	public function write( $data, $buffer='read' )
	{
		$this->_buffer[$buffer] .= $data;
	}

	/**
	 * Returns the first line terminated with \n found in the buffer, or FALSE
	 */
	public function readLine( $buffer='read' )
	{
		if(!$this->hasLine($buffer)) return false;

		$idx = strpos($this->_buffer[$buffer],"\n");
		$line = substr($this->_buffer[$buffer],0,$idx);
		$this->_buffer[$buffer] = substr($this->_buffer[$buffer],$idx+1);
		return rtrim($line,"\n");
	}

	/**
	 * Returns the first line terminated with \n found in the buffer, without
	 * removing it from the buffer.
	 */
	public function peekLine( $buffer='read' )
	{
		if(!$this->hasLine($buffer)) return false;

		$idx = strpos($this->_buffer[$buffer],"\n");
		return rtrim(substr($this->_buffer[$buffer],0,$idx),"\n");
	}

	/**
	* Whether a buffer has a line in in it (terminated by a \n)
	* @param string $buffer The buffer to read
	* @return bool
	*/
	public function hasLine( $buffer='read' )
	{
		return strpos($this->_buffer[$buffer],"\n") !== false;
	}

	/**
	* Whether a buffer has a certain number of bytes in it
	* @param string $buffer The buffer to read
	* @param int $size The number of bytes
	* @return bool
	*/
	public function has( $size, $buffer='read' )
	{
		return strlen($this->_buffer[$buffer]) >= $size;
	}

	/**
	* Read a chunk of data from a write buffer
	* @param string $buffer The buffer to read, either read or write
	* @param int $size The amount of data to read
	* @return string
	*/
	public function read( $size=4096, $buffer='read' )
	{
		$data = substr( $this->_buffer[$buffer], 0, $size );
		$this->_buffer[$buffer] = substr( $this->_buffer[$buffer], $size );
		return $data;
	}
}
