<?php

require( __DIR__ . '/../vendor/autoload.php' );

$loop = new PhpCoap\StreamSocketSelectLoop();

$client = new PhpCoap\Client( $loop );

$client->post( 'coap://skynet.im/devices', 'type=test', function( $data ) {
	var_dump( json_decode( $data ) );
});

$loop->run();

?>