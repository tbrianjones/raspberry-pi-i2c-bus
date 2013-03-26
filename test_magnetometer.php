<?php
	
	require_once( 'peripherals/lsm303_magnetometer.php' );
	
	$Magnet = new lsm303_magnetometer();
	
	$Magnet->set_resolution( 2 );
	echo "\nMagnetometer Resolution: " . $Magnet->get_resolution();
	
	// read magnetometer
	while( 1 ) {
		$fields = $Magnet->get_fields();
		echo "\nFIELDS: " . number_format( $fields['x'], 2 ) . ', ' . number_format( $fields['y'], 2 ) . ', ' . number_format( $fields['z'], 2 );
		echo " - HEADING: " . number_format( $Magnet->get_heading(), 2 );
	}

?>