<?php

namespace PhpCoap\Client;

use React\Promise;
use React\Promise\Deferred;
use PhpCoap\PacketStream;
use PhpCoap\CoapRequest;
use PhpCoap\CoapResponse;


class Connector extends \Evenement\EventEmitter
{

	private $loop;

	function __construct( \React\EventLoop\LoopInterface $loop )
	{
		$this->loop = $loop;
	}

	function create( $host, $port )
	{
		$deferred = new Deferred();

		$sock = stream_socket_client( sprintf( 'udp://%s:%s', $host, $port ), $errno, $errstr );

		if ( $sock == false )
		{
			$this->emit( 'error', array( $errno, $errstr ) );
			return;
		}

		$loop = $this->loop;

		$this->loop->addWriteStream( $sock, function( $sock ) use ( $loop, $deferred ) {
			$loop->removeWriteStream( $sock );

			$deferred->resolve( $sock );
		});

		return $deferred->promise()->then( array( $this, 'handleConnect' ) );

	}

	function handleConnect( $sock )
	{
		return new PacketStream( $sock, $this->loop );
	}
}