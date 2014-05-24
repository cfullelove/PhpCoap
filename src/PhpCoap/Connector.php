<?php

namespace PhpCoap;

use React\Promise;
use React\Promise\Deferred;

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

		$sock = socket_create( AF_INET, SOCK_DGRAM, getprotobyname( 'udp' ) );

		socket_connect( $sock, $host, $port );


		$loop = $this->loop;

		$this->loop->addWriteSocket( $sock, function( $sock ) use ( $loop, $deferred ) {
			$loop->removeWriteSocket( $sock );

			$deferred->resolve( $sock );
		});

		return $deferred->promise()->then( array( $this, 'handleConnect' ) );

	}

	function handleConnect( $sock )
	{
		return new Socket( $sock, $this->loop );
	}
}