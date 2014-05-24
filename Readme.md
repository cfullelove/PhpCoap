# PhpCoap

Asynchonous Coap client in PHP

## Basic usage

Requests are prepared using the ``Client::request()`` method. The payload can be set with the ``Request::setPayload()`` method.

## Example

```php

<?php

$loop = new PhpCoap\StreamSocketSelectLoop();

$client = new PhpCoap\Client( $loop );

$client->get( 'coap://skynet.im/status', function( $data ) {
	var_dump( json_decode( $data ));
} );

$loop->run();

?>
```

## Notes

While PhpCoap utilises the ReactPhp components, it currently requires a modified event loop (``StreamSocketSelectLoop``) so that PhpCoap can work with Streams and Sockets

## Credits

This component leverages the patterns and components from the [ReactPhp](http://reactphp.org).