# PhpCoap

Asynchonous Coap client and server in PHP


## Example Client

```php

<?php

$loop = React\EventLoop\Factory::create();

$client = new PhpCoap\Client\Client( $loop );

$client->get( 'coap://skynet.im/status', function( $data ) {
	var_dump( json_decode( $data ));
} );

$loop->run();

?>
```

## Example Server

```php

<?php

$loop = React\EventLoop\Factory::create();

$server = new PhpCoap\Server\Server( $loop );

$server->receive( 5683, '0.0.0.0' );

$server->on( 'request', function( $req, $res, $handler ) {
	$res->setPayload( json_encode( 'test' ) );
	$handler->send( $res );
});

$loop->run();

?>
```

## Notes & ToDo's
* TODO: Implement message tokens (only Message Id's currently used)
* Only single packet messages are supported currently
* TODO: Create message router for server component

## Credits

This component leverages the patterns and components from the [ReactPhp](http://reactphp.org).
