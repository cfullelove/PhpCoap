<?php

namespace PhpCoap;

class Socket extends \Evenement\EventEmitter
{
	protected $writable = true;
	protected $readable = true;

	private $loop;

	function __construct( $sock, \React\EventLoop\LoopInterface $loop )
	{
		$this->sock = $sock;
		$this->loop = $loop;
		$this->buffer = new PacketBuffer( $this->sock, $loop );



		$this->resume();
	}


	function send( $packet )
	{
		$this->buffer->send( $packet );
	}

	function resume()
	{
		$this->loop->addReadSocket( $this->sock, array( $this, 'handleRecv' ) );
	}

	function pause()
	{
		$this->loop->removeReadSocket( $this->sock );
	}

	function handleRecv( $sock )
	{
		$r = socket_recv( $sock, $pkt, 4096, null );

		if ( $r == false )
		{
			$this->emit( 'error', array( socket_strerror(socket_last_error($sock) ) ) );
			return;
		}

		if ( $pkt  !=  "" )
		{
			$this->emit( 'packet', array( $pkt, $this ) );
		}

	}

	function close()
	{
		$this->buffer->close();
		@socket_close( $this->sock );
		$this->loop->removeReadSocket( $this->sock );
		$this->buffer->removeAllListeners();
        $this->removeAllListeners();
	}
}