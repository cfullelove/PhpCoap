<?php

namespace PhpCoap;

class Request extends \Evenement\EventEmitter
{

	const STATE_INIT = 0;
    const STATE_SENDING_REQUEST = 1;
    const STATE_REQUEST_SENT = 2;
    const STATE_WAITING_CON = 3;
    const STATE_WAITING_NON = 4;
    const STATE_WAITING_CLOSE = 5;
    const STATE_END = 6;

    private $connector;
    private $coapRequest;

    private $sock;
    private $response;
    private $state = self::STATE_INIT;


	function __construct( Connector $connector, CoapRequest $coapRequest )
	{
		$this->connector = $connector;
		$this->coapRequest = $coapRequest;
	}

	// TODO: Consider removing
	function isWritable()
	{
		return $this->state == self::STATE_INIT;
	}

	function setPayload( $data )
	{
		return $this->coapRequest->setPayload( $data );
	}

	function send()
	{
		$that = $this;
		$requestData = $this->coapRequest;
		$sockRef = &$this->sock;

		$this->state = self::STATE_SENDING_REQUEST;

		$this->connect()
			->then( function( Socket $sock ) use ($requestData, $sockRef, $that ) {
				$that->sock = $sock;

				$sock->on( 'packet', array( $that, 'handlePacket' ) );

				$sock->send( $requestData->getMessage() );

				if ( $requestData->getType() == CoapRequest::NON )
				{
					$that->state = self::STATE_WAITING_NON;
				}
				else
				{
					$that->state = self::STATE_REQUEST_SENT;
				}
				
				$that->emit( 'request_sent' );
			});
	}

	function handlePacket( $pkt )
	{

		$resp = CoapResponse::fromBinString( $pkt );

		// Got something back from a Confirmed Request
		if ( $this->coapRequest->getType() == CoapRequest::CON && $this->state == self::STATE_REQUEST_SENT )
		{
			// Got an Acknowledgement
			if ( $resp->getType() == CoapResponse::ACK )
			{
				$this->emit( 'ack', array( $resp ) );

				// Piggy-backed Response
				if ( $resp->getPayload() != "" )
				{
					$this->emit( 'response', array( $resp ) );
					$this->close();
					return;
				}
				else
				{
					$this->state = self::STATE_WAITING_CON;
					return;
				}
			}

			// Badness!
			// TODO: Handle error
			$this->sendAck( $this->sock, $resp->getMessageId(), '5.00' );
		}

		// Got a Confirmed Response after inital Acknowledgement
		if ( $resp->getType() == CoapResponse::CON && $this->state == self::STATE_WAITING_CON )
		{
			$this->sendAck( $resp );
			$this->emit( 'response', array( $resp ) );
			$this->state = self::STATE_WAITING_CLOSE;
			return;
		}

		// Got something back from a Non-confirmed Request
		if ( $this->coapRequest->getType() == CoapRequest::NON && $this->state == self::STATE_WAITING_NON )
		{
			// Got a Non-confirmed Response
			if ( $resp->getType() == CoapResponse::NON )
			{
				$this->emit( 'response', array( $resp ) );
				$this->close();
				return;
			}

			// Get a Confirmed Response (have to Acknowledge)
			if ( $resp->getType() == CoapResponse::CON )
			{
				$this->state = self::STATE_WAITING_CLOSE;
				$this->sendAck( $resp );
				$this->emit( 'response', array( $resp ) );
				return;
			}
		}
	}

	protected function connect()
    {
        $host = $this->coapRequest->getHost();
        $port = $this->coapRequest->getPort();

        return $this->connector->create($host, $port);
    }

    private function sendAck( CoapResponse $resp, $code = '2.00' )
	{
		$ack = new CoapPdu();
		$ack->setType( CoapPdu::ACK );
		$ack->setCode( $code );
		$ack->setMessageId( $resp->getMessageId() );

		$pkt = $ack->getMessage();

		// TODO: update for async send
		//socket_send( $this->sock, $pkt, strlen( $pkt ), null );
		
		if ( $this->state == self::STATE_WAITING_CLOSE )
		{
			$this->sock->on( 'sent', array( $this, 'close' ) );
		}

		$this->sock->send( $pkt );
	}

    public function close()
    {
    	$this->state = self::STATE_END;
    	$this->emit( 'complete' );
    	// TODO: clean up

    	$this->sock->close();
    }

}