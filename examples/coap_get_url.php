<?php

require( __DIR__ . '/../vendor/autoload.php' );

$loop = React\EventLoop\Factory::create();

$client = new PhpCoap\Client\Client( $loop );

$client->get( $argv[1], function( $data ) {
	var_dump( json_decode( $data ));
} );

$loop->run();

?>