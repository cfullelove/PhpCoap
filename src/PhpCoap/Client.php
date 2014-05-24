<?php

namespace PhpCoap;

class Client extends \Evenement\EventEmitter
{
	private $gotAck = false;
	private $complete = false;
	
	function __construct( \React\EventLoop\LoopInterface $loop )
	{
		$this->loop = $loop;
	}

	function request( $method, $uri )
	{
		$this->request = new CoapRequest( $uri, $method, '' );
		$this->request->on( 'send', array( $this, 'sendRequest' ) );
		$this->request->setCode( $method );
		$this->request->setType( CoapPdu::NON );
		return $this->request;
	}

	function sendAck( $sock, $messageId, $code = '2.00' )
	{
		$ack = new CoapPdu();
		$ack->setType( CoapPdu::ACK );
		$ack->setCode( $code );
		$pkt = $ack->getMessage();
		socket_send( $sock, $pkt, strlen( $pkt ), null );
	}

	function handleRecv( $sock )
	{
		$r = socket_recv( $sock, $resp, 4096, null );

		if ( $r == false )
		{
			$this->emit( 'error', array( socket_strerror(socket_last_error($sock) ) ) );
			return;
		}

		if ( $resp  !=  "" )
		{
			$this->emit( 'data', array( $resp ) );
		}

	}

	function onData( $data )
	{
		$request = $this->request;

		$resp = CoapResponse::fromBinString( $data );

		if ( $request->getType() == CoapRequest::CON && ! $this->gotAck )
		{
			if ( $resp->getType() == CoapResponse::ACK )
			{
				$request->emit( 'ack', array( $resp ) );
				if ( $resp->getPayload() == "" )
				{
					return;
				}
				else
				{
					$request->emit( 'response', array( $resp ) );
					$this->emit( 'complete' );
					return;
				}
			}
			// Badness!
			$this->sendAck( $this->sock, $resp->getMessageId(), '5.00' );
		}
		elseif ( $resp->getType() == CoapResponse::CON && $this->gotAck )
		{
			$this->sendAck( $this->sock, $resp->getMessageId(), '2.00' );
			$request->emit( 'response', array( $resp ) );
			$this->emit( 'complete' );
			return;
		}
		elseif ( $request->getType() == CoapRequest::NON )
		{
			if ( $resp->getType() == CoapResponse::NON )
			{
				$request->emit( 'response', array( $resp ) );
				$this->emit( 'complete' );
				return;
			}

			if ( $resp->getType() == CoapResponse::CON )
			{
				$this->sendAck( $this->sock, $resp->getMessageId(), '2.00' );
				$request->emit( 'response', array( $resp ) );
				$this->emit( 'complete' );
				return;
			}
		}
	}

	function end()
	{
		$this->complete = true;
		$this->loop->removeReadSocket( $this->sock );
		@socket_close( $this->sock );
	}

	function sendRequest( $request )
	{
		$this->sock = socket_create( AF_INET, SOCK_DGRAM, getprotobyname( 'udp' ) );

		socket_connect( $this->sock, $request->getHost(), $request->getPort() );

		$pkt = $request->getMessage();
		socket_send( $this->sock, $pkt, strlen( $pkt ), null );

		$this->on( 'complete', array( $this, 'end' ) );
		$this->on( 'data', array( $this, 'onData' ) );
		$this->on( 'error', function( $error ) {
			printf( "Error: %s\n", $error );
		} );

		$this->loop->addReadSocket( $this->sock, array( $this, 'handleRecv' ) );

	}

	function post( $uri, $data, $callback )
	{
		$req = $this->request( CoapRequest::POST, $uri );
		$req->setPayload( $data );
		$req->on( 'response', function ( $resp ) use ($callback) {
			call_user_func( $callback, $resp->getPayload() );
		});
		$req->send();
	}

	function get( $uri, $callback )
	{
		$req = $this->request( CoapRequest::GET, $uri );
		$req->on( 'response', function ( $resp ) use ($callback) {
			call_user_func( $callback, $resp->getPayload() );
		});
		$req->send();
	}


}