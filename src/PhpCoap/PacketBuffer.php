<?php

namespace PhpCoap;

class PacketBuffer extends \Evenement\EventEmitter
{

	public $listening = false;
	private $packets = array();

	function __construct( $sock, \React\EventLoop\LoopInterface $loop )
	{
		$this->sock = $sock;
		$this->loop = $loop;
	}

	function send( $packet )
	{
		
        array_push( $this->packets, $packet );

        if ( ! $this->listening )
        {
            $this->listening = true;

            $this->loop->addWriteSocket($this->sock, array($this, 'handleSend'));
        }
	}

	function handleSend()
	{
		$pkt = array_shift( $this->packets );

		socket_send( $this->sock, $pkt, strlen( $pkt ), null );

		$this->packet = null;

		$this->emit( 'sent', array( $pkt ) );

		if ( count( $this->packets ) == 0 )
		{
			$this->listening = false;
			$this->loop->removeWriteSocket( $this->sock );
			$this->emit( 'sent-all' );
		}
	}

	function close()
	{
		$this->packets = array();
		$this->loop->removeWriteSocket( $this->sock );
	}

}