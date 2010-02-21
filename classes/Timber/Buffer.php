<?php

/**
 * A buffer for timber connections
 */
class Timber_Buffer
{
	private $_buffer='';

	/**
	 * Saves data in a buffer
	 * @param string $data the data to write
	 */
	public function write( $data )
	{
		$this->_buffer .= $data;
	}

	/**
	 * Returns the first line terminated with \n found in the buffer, or FALSE
	 * @return string
	 */
	public function readLine()
	{
		if(($idx = strpos($this->_buffer,"\n")) === false) return false;

		$line = substr($this->_buffer,0,$idx);
		$this->_buffer = substr($this->_buffer,$idx+1);
		return rtrim($line,"\n");
	}

	/**
	* Read $size bytes from the buffer
	* @param int $size The amount of data to read
	* @param bool $exact Whether to return false if there isn't enough data to read
	* @return string
	*/
	public function read( $size=4096, $exact=false )
	{
		if($exact && $this->length() < $size) return false;

		$data = substr( $this->_buffer, 0, $size );
		$this->_buffer = substr( $this->_buffer, $size );
		return $data;
	}

	/**
	 * @return int the number of bytes left in the buffer
	 */
	public function length()
	{
		return strlen($this->_buffer);
	}
}
