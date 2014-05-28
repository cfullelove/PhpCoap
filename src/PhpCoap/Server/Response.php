<?php

namespace PhpCoap\Server;

class Response extends \Evenement\EventEmitter, 
{
	function __construct( $messageId )
	{
		$this->coapResponse = newPhpCoap\CoapResponse();
		$this->coapResponse->setMessageId( $messageId );
	}

	function setPayload( $payload )
	{
		$this->coapResponse->setPayload( $payload );
	}

	function send()
	{
		$this
	}
}