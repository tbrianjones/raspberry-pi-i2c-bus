<?php
	
	require_once( 'peripherals/bmp085.php' );
	
	$Bmp085 = new bmp085();
	var_dump( $Bmp085->get_readings() );

?>