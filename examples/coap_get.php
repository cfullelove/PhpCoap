<?php

require( __DIR__ . '/../vendor/autoload.php' );

$loop = new PhpCoap\StreamSocketSelectLoop();

$client = new PhpCoap\Client( $loop );

$client->get( 'coap://skynet.im/status', function( $data ) {
	var_dump( json_decode( $data ));
} );

$loop->run();

?>