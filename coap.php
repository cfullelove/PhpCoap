<?php

function print_packet( $string )
{
	foreach( unpack( 'C*', $string ) as $byte )
	{
		printf( "%08b 0x%02x %03d %c\n", $byte, $byte, $byte, $byte );
	}
}

use PhpCoap\CoapPdu;
use PhpCoap\CoapOption;




$message = new CoapPdu();
$message->setCode( '0.02');
$message->addOption( new CoapOption( 3, 'skynet.im' ) );
$message->addOption( new CoapOption( 11, 'data/1012BB013284_0_0_1' ) );
//$message->addOption( new CoapOption( 15, 'token=0k634shgh527kqpvic1tynbxuyqlg14i' ) );
$message->setPayload( 'token=0k634shgh527kqpvic1tynbxuyqlg14i&temp=30.1' );

$pkt = '';
foreach( $message->getMessage() as $byte )
{
	$pkt .= pack( 'C', $byte );
}

print_packet( $pkt );

echo PHP_EOL;

//exit();

$sock = socket_create( AF_INET, SOCK_DGRAM, getprotobyname( 'udp' ) );

socket_connect( $sock, 'skynet.im', 5683 );

socket_send( $sock, $pkt, strlen( $pkt ), null );

socket_recv( $sock, $resp, 1024, null );

print_packet( $resp );

$resp = CoapPdu::fromBinString( $resp );

echo $resp->getPayload() . PHP_EOL;

if ( $resp->isAck() )
{
socket_recv( $sock, $resp, 1024, null );

print_packet( $resp );

$resp = CoapPdu::fromBinString( $resp );

echo $resp->getPayload() . PHP_EOL;
}




