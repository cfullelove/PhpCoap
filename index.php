<?php

require( __DIR__ . '/vendor/autoload.php' );

function print_packet( $string )
{
	foreach( unpack( 'C*', $string ) as $byte )
	{
		printf( "%08b 0x%02x %03d %c\n", $byte, $byte, $byte, $byte );
	}
}

$loop = new PhpCoap\StreamSocketSelectLoop();


// $stdin = new React\Stream\Stream( fopen( 'php://stdin', 'r' ), $loop );

// $stdin->on( 'data', function( $data ) {
// 	printf( "%s\n", $data );
// });


$client = new PhpCoap\Client( $loop );

$client->post( 'coap://skynet.im/data/1012BB013284_0_0_1', 'token=0k634shgh527kqpvic1tynbxuyqlg14i&temp=12', function( $data ) {
	printf( "%s\n", $data );
} );

$client = new PhpCoap\Client( $loop );

$client->get( 'coap://skynet.im/status', function( $data ) {
	printf( "%s\n", $data );
} );

// $req = $client->request( PhpCoap\CoapRequest::POST, 'coap://skynet.im/data/1012BB013284_0_0_1' );
// $req->setPayload( 'token=0k634shgh527kqpvic1tynbxuyqlg14i&temp=12' );
// $req->on( 'response', function( $response ) {
// 	printf( "%s\n", $response->getPayload() );
// });

// $req->send();

$loop->run();