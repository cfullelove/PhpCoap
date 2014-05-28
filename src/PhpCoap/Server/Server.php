<?php

namespace PhpCoap\Server;

use PhpCoap\PacketStream;

class Server extends \Evenement\EventEmitter
{
	private $loop;

	private $sessions = array();

	function __construct( \React\EventLoop\LoopInterface $loop )
	{
		$this->loop = $loop;
	}

	function receive( $port, $host = '127.0.0.1' )
	{
		$this->sock = stream_socket_server( sprintf( 'udp://%s:%s', $host, $port ), $errno, $errstr, STREAM_SERVER_BIND );

		if ( $this->sock === false )
		{
			throw \Exception( sprintf( "Error( %s ) : %s", $errno, $errstr ) );
		}

		$this->packetStream = new PacketStream( $this->sock, $this->loop );
		$this->packetStream->on( 'packet', array( $this, 'handlePacket' ) );
	}

	function handlePacket( $pkt, $peer )
	{
		if (! array_key_exists( $peer, $this->sessions ) )
		{
			$this->sessions[ $peer ] = new RequestHandler( $this->packetStream, $peer  );
			$this->sessions[ $peer ]->on( 'complete', function() use ( $peer ) {
				unset( $this->sessions[ $peer ] );
			});
			$this->sessions[ $peer ]->on( 'request', function() {
				$this->emit( 'request', func_get_args() );
			});
		}

		$this->sessions[ $peer ]->handlePacket( $pkt );
	}
}
