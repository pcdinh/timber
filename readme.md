
Timber
======

The timber logging server is a simple, fast application logging server written in PHP5, with no external dependancies. It's designed to receive log messages as quickly as possible and then asynchronously either write them locally or relay them to a remote timber instance for aggregation.

Running it
----------

Run a simple local logging server that writes to a file:

	$ ./timberd -d -l 127.0.0.1 -p 11124 --file ./myapp.log

Architecture
------------

The timberd server uses two processes, a reader process and a writer process.

The reader process listens to the inbound TCP connections for log messages, which it passes to the writer process for processing. This process is entirely asynchronous and is designed to receive high volumes of log messages.

The writer process accepts inbound log messages and either writes them to a local database or relays them to a remote timberd server for aggregation. Initially the messages will be queued in memory, but more advanced techniques
like disk buffering or integration with a queue like [beanstalkd][3] are possible.

Security
--------

Messages are passing clear text and there is no authentication at a socket layer. Security could later be added through signing and encryption of log payloads. Additionally, network level security should be used to ensure timberd instances are not publically accessible.

Protocol
--------

The protocol is fairly simple and largely inspired by the [memcached protocol][1].

The primary type of message is a logging message:

	<level> <host> <application> <subsystem> <level> <bytes>\n
	... data payload follows\n

The host, application and subsystem are send to the server to identify where the log message is coming from. For instance:

	INFO example.org demoapplication commerce 8094\n
	... 8094 bytes of data payload follows\n

The var bytes is used to describe how long the log payload that follows is in bytes. All lines are terminated with a bare newline.

Additionally, gzip compression can be used on the payloads. This is indicated with a gzip token at the end of the command:

	TRACE example.org myapp myservice 100002 gzip\n
	... 100002 bytes of gzipped payload follows\n

Messages
--------

Log messages are broken into different priority levels:

TRACE is the lowest level of message and is essentially debugging messages, INFO is for informational messages, WARN is for non-fatal warnings, ERROR is for recoverable errors and FATAL is for non-recoverable errors.

A message is composed of a timestamp, a message and then optionally a stacktrace and a series of application specific context items, for instance things like memory usage, the script that the log message came from, etc.

The payload is [json][2] encoded and follows a defined structure:

	{
		level: 'TRACE | INFO | WARN | ERROR | FATAL'
		message: 'Error message text'
		timestamp: ... unix timestamp
		stacktrace: ... optional, see stack trace format
		context: ... optional, see context format
	}

The stacktrace key can be ommitted, but if provided should describe the calling stack before the log message in the application in the following structure:

	[
		{
			file: .. the file the application was in
			line: .. the line number
			class: .. the name of the application class
			function: .. the function or method that the application was in
		},

		... repeated for each line of the trace
	]

The context key provides a key=>value map of contextual information.


  [1]: http://code.sixapart.com/svn/memcached/trunk/server/doc/protocol.txt
  [2]: http://www.ietf.org/rfc/rfc4627.txt?number=4627
  [3]: http://kr.github.com/beanstalkd/

