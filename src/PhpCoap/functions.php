<?php

namespace PhpCoap;

function print_packet( $string )
{
	foreach ( unpack( 'C*', $string ) as $b )
	{
		printf( "%08b 0x%02x %03d %c\n", $b, $b, $b, $b );
	}
}

