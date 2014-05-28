<?php

require( __DIR__ . '/../vendor/autoload.php' );

$loop = React\EventLoop\Factory::create();

$client = new PhpCoap\Client( $loop );

$client->post( 'coap://skynet.im/devices', 'type=test', function ( $data ) use ($loop, $client) {
	$data = json_decode( $data );
	$uuid = $data->uuid;
	$token = $data->token;

	printf( "UUID: %s\nToken: %s\n", $uuid, $token );

	$stdin = new React\Stream\Stream( fopen( 'php://stdin', 'r' ), $loop );

	$stdin->on( 'data', function ($data) use ($client, $uuid, $token) {

		$query = http_build_query( array(
			'token' => $token,
			'value' => trim( $data )
		));

		$client->post( sprintf( 'coap://skynet.im/data/%s', $uuid ), $query, function( $data ) {
			var_dump( json_decode( $data ));
		} );
	});
});


$loop->run();