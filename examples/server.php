<?php

// server

require( __DIR__ . '/../vendor/autoload.php' );

$loop = React\EventLoop\Factory::create();

$server = new PhpCoap\Server\Server( $loop );

$server->receive( 5683, '0.0.0.0' );

$server->on( 'request', function( $req, $res, $handler ) {
	$res->setPayload( json_encode( 'test' ) );
	$handler->send( $res );
});

$loop->run();