<?php
	
	require_once( 'peripherals/lsm303_accelerometer.php' );
	
	$Accel = new lsm303_accelerometer();
	
	$Accel->set_resolution( 2 );
	echo "\nAccelerometer Resolution: " . $Accel->get_resolution();
	
	// read accelerometer
	while( 1 ) {
		$accel = $Accel->get_acceleration();
		echo "\nACCEL: " . number_format( $accel['x'], 2 ) . ', ' . number_format( $accel['y'], 2 ) . ', ' . number_format( $accel['z'], 2 );
		echo " - ROLL (x): " . number_format( $Accel->get_roll(), 2 );
		echo " - PITCH (y): " . number_format( $Accel->get_pitch(), 2 );
	}

?>