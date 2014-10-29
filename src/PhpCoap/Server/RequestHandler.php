<?php

namespace PhpCoap\Server;

use PhpCoap\PacketStream;
use PhpCoap\CoapRequest;
use PhpCoap\CoapResponse;


class RequestHandler extends \Evenement\EventEmitter
{

	const STATE_INIT = 0;
    const STATE_RESP_PENDING = 1;
    const STATE_SENDING_RESP = 2;
    const STATE_WAITING_ACK = 3;
    const STATE_WAITING_CLOSE = 5;
    const STATE_END = 6;

    private $connector;
    private $coapRequest;

    private $sock;
    private $response;
    private $request;
    private $state = self::STATE_INIT;
    private $peer;


	function __construct( PacketStream $sock, $peer )
	{
		$this->sock = $sock;
		$this->peer = $peer;
	}

	function send( CoapResponse $resp )
	{
		$that = $this;
		$this->state = self::STATE_SENDING_RESP;
		$this->sock->send( $resp->getMessage(), $this->peer );
		if ( $this->request->getType() == CoapRequest::NON )
		{
			$this->close();
		}
		else
		{
			$this->state = self::STATE_WAITING_ACK;
		}
	}

	function handlePacket( $pkt )
	{

		$this->request = CoapRequest::fromBinString( $pkt );

		if ( $this->state == self::STATE_INIT )
		{

			$this->state = self::STATE_RESP_PENDING;
			$resp = new CoapResponse();
			$resp->setMessageId( $this->request->getMessageId() );
			if ( $this->request->getType() == CoapRequest::NON )
			{
				$resp->setType( CoapResponse::NON );
			}
			elseif( $this->request->getType() == CoapRequest::CON )
			{
				$resp->setType( CoapResponse::ACK );
			}
			else
			{
				$this->sendAck( $this->request, '5.00' );
				$this->emit( 'error', array( $this->request ) );
				return;
			}

			$resp->setCode( '2.00' );
			$this->emit( 'request', array( $this->request, $resp, $this ) );
			return;
		}

		if ( $this->request->getType() == CoapRequest::ACK && $this->state == self::STATE_WAITING_ACK )
		{
			$this->close();
			$this->state = self::STATE_WAITING_CLOSE;
			return;
		}

	}

    private function sendAck( CoapResponse $resp, $code = '2.00' )
	{
		$ack = new CoapPdu();
		$ack->setType( CoapPdu::ACK );
		$ack->setCode( $code );
		$ack->setMessageId( $resp->getMessageId() );

		$this->sock->send( $ack->getMessage() );
	}

		public function close()
		{
			$this->state = self::STATE_END;
			$this->emit( 'complete' );
		}
		
		public function getPeer()
		{
			return $this->peer;
		}
		
		public function getPeerHost()
		{
			if ($this->peer)
				return substr($this->peer,0,strpos($this->peer,':'));
			return null;
		}
		
		public function getPeerPort()
		{
			if ($this->peer)
				return substr($this->peer,strpos($this->peer,':')+1,strlen($this->peer));
			return null;
		}
}