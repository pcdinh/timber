<?php

require_once('phpmio/classes/Stream.php');
require_once('phpmio/classes/Exception.php');
require_once('phpmio/classes/Selector.php');
require_once('phpmio/classes/SelectionKey.php');
require_once('phpmio/classes/StreamFactory.php');

/**
 * The reader process for timberd
 * @author Lachlan Donald <lachlan@ljd.cc>
 */
class Timber_Server
{
	private $_selector, $_factory, $_listen, $_port, $_relays=array();

	/**
	 * Constructor
	 */
	public function __construct($listen='0.0.0.0', $port=11124)
	{
		$this->_listen = $listen;
		$this->_port = $port;

		// Create our base objects
		$this->_selector = new MioSelector();
		$this->_factory  = new MioStreamFactory();

		// Register a server stream with the selector
		$this->_selector->register(
			$this->_factory->createServerStream( $listen.':'.$port ),
			MioSelectionKey::OP_ACCEPT
		);

		// Create a protocol parser
		$this->_parser = new Timber_Protocol();
	}

	/**
	 * Runs the server
	 */
	public function run()
	{
		$this->_console("listening for connections on port %d", $this->_port);

		// loop for ever, this is going to be server
		while( true )
		{
			// keep selecting until there's something to do
			while( !$count = $this->_selector->select() ) { }

			// when there's something to do loop over the ready set
			foreach( $this->_selector->selected_keys as $key )
			{
				try
				{
					if( $key->isAcceptable() )
					{
						// if the stream has connections ready to
						// accept then accept them until there's no more
						while( $stream = $key->stream->accept() )
						{
							$this->_console("accepted connection from %s",
								stream_socket_get_name($stream->getStream(), true)
								);

							$this->_selector->register(
								$stream,
								MioSelectionKey::OP_READ,
								new Timber_Connection()
								);
						}
					}
					elseif( $key->isReadable() )
					{
						$data = $key->stream->read( 4096 );
						$this->_verbose("<< %s", rtrim($data,"\n"));

						$key->attachment->write($data, Timber_Connection::READ_BUFFER);

						// check for a command to execute
						if($command = $this->_parser->readCommand($key->attachment))
						{
							$this->_verbose("got a %s command", $command[0]->command);
							$key->attachment->write("OK\n", Timber_Connection::WRITE_BUFFER);
							$key->setInterestOps( MioSelectionKey::OP_WRITE );

							var_dump($this->_selector->selection_keys);

							/* write to all relays
							foreach($this->_relays as $relay)
							{
								$this->_verbose("relaying %s command", $command[0]->command);
								$relay[1]->write("LLAMA\n", Timber_Connection::WRITE_BUFFER);
							}
							*/
						}
					}
					elseif( $key->isWritable() )
					{
						if($data = $key->attachment->read(4096, Timber_Connection::WRITE_BUFFER))
						{
							$this->_verbose(">> %s", rtrim($data,"\n"));
							$key->stream->write($data);
							$key->setInterestOps( MioSelectionKey::OP_READ );
						}
					}
				}
				catch(MioClosedException $e)
				{
					$this->_verbose("client disconnected without closing connection", true);
					$this->_selector->removeKey($key);
				}
				catch(Exception $e)
				{
					$this->_console($e->getMessage());
					$this->_selector->removeKey($key);
				}
			}
		}
	}

	/**
	 * Adds a socket to relay log commands to
	 */
	public function relay($socket)
	{
		$stream = new MioStream($socket,"relay");
		$connection = new Timber_Connection();
		$this->_relays[] = array($stream, $connection);

		$this->_console("adding a socket for relaying");

		$this->_selector->register(
			$stream,
			MioSelectionKey::OP_WRITE,
			$connection
			);
	}

	private function _verbose($message)
	{
		$args = func_get_args();
		call_user_func_array(array($this,'_console'),$args);
	}

	private function _console($message)
	{
		printf("[%s %sKb] ", date('Y-m-d H:i:s'), round(memory_get_usage()/1024));
		$args = func_get_args();
		$params = array_merge(array($message),array_slice($args,1));
		call_user_func_array('printf',$args);
		printf("\n");
	}
}
