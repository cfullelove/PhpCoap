<?php

namespace PhpCoap;

class CoapRequest extends CoapPdu
{
	
	private $uriParts;
	private $ack = false;

	function __construct( $uri, $method, $data )
	{
		$parts = parse_url( $uri );

		if ( $parts['scheme'] != 'coap' )
		{
			throw \Exception( 'Bad Uri: ' . $uri );
		}

		$this->uriParts = $parts;

		$this->addOption( new CoapOption( 3, $this->getHost() ) );
		$this->addOption( new CoapOption( 11, substr( $this->uriParts['path'], 1 ) ) );
		if ( isset( $this->uriParts['query'] ) )
		{
			$this->addOption( new CoapOption( 15, $this->uriParts['query'] ) );
		}

		$this->setPayload( $data );
		parent::__construct();
	}

	function getHost()
	{
		return $this->uriParts['host'];
	}

	function getPort()
	{
		if ( isset( $this->uriParts['port'] ) )
		{
			return $this->uriParts['port'];
		}
		else
		{
			return 5683;
		}
	}

}