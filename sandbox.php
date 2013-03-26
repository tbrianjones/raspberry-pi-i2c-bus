<?php
	
	require_once( 'peripherals/lsm303_accelerometer.php' );
	
	$Accel = new lsm303_accelerometer();
	
	$Accel->set_resolution( 2 );
	echo "\nAccelerometer Resolution: " . $Accel->get_resolution();
	
	// read accelerometer
	$i = 0;
	while( $i < 10000 ) {
		$i++;
		$accel = $Accel->get_acceleration();
		echo "\nACCEL: " . number_format( $accel['x'], 2 ) . ', ' . number_format( $accel['y'], 2 ) . ', ' . number_format( $accel['z'], 2 );
		//echo " - About X-AXIS: " . number_format( atan2( $accel['y'], $accel['x'] ) * 180 / pi(), 2 );
		echo " - ROLL (x): " . number_format( $Accel->get_roll(), 2 );
		echo " - PITCH (y): " . number_format( $Accel->get_pitch(), 2 );
	}

	echo "\n\n";

?>