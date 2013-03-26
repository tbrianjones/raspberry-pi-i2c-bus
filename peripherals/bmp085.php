<?php
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the BMP085 pressure, temperature, and altitude sensor
	//
	//	- datasheet: http://www.adafruit.com/datasheets/BMP085_DataSheet_Rev.1.0_01July2008.pdf
	//	- embedded on the adafruit BMP085 board: http://www.adafruit.com/products/391
	//
	//	DEV NOTES
	//		- only works with default oversampling ( 0 )
	//		- temperature is working
	//		- pressure needs to be debugged
	//		- altitude is broken ( may only be because of pressure )
	//
	class bmp085 extends i2c_bus {
		
		// debug mode
		private $debug_mode = 1;
		
		// calibration data
		private $ac1;
		private $ac2;
		private $ac3;
		private $ac4;
		private $ac5;
		private $ac6;
		private $b1;
		private $b2;
		private $mb;
		private $mc;
		private $md;
		
		// readings
		private $ut;	// uncompensated temperature
		private $t;		// true temperature
		private $up;	// uncompensated pressure
		private $p;		// true pressure
		private $a;		// altitude ( calculated using true pressure )
		
		function __construct() {
			
			parent::__construct();
			
			// set the default i2c bus location for the BMP085
			$this->slave_i2c_register = 0x77;
			
			// get calibration data
			$this->read_calibration_data();
			
		}
	
	
	// --- GETTERS --------------------------------------------------------------
	
		
		// returns an array with temperature, pressure, and altitude
		//
		//	- temperature is in celcius ( c ) 
		//	- pressure is in pascals ( Pa )
		//	- altitude is in meters ( m )
		//
		public function get_readings() {
			
			// get data and make calculations
			$this->read_uncompensated_temperature();
			$this->read_uncompensated_pressure();
			$this->calculate_readings();

			// calculate altitude and re
			return array(
				'temperature'	=> $this->t * 0.1,
				'pressure'		=> $this->p,
				'altitude'		=> $this->a
			);
		}
	
		
	// --- READERS --------------------------------------------------------------
				
		
		private function read_calibration_data()
		{
			
			$this->ac1 = $this->read_signed_short( 0xab, 0xaa );
			$this->ac2 = $this->read_signed_short( 0xad, 0xac );
			$this->ac3 = $this->read_signed_short( 0xaf, 0xae );
			$this->ac4 = $this->read_unsigned_short( 0xb1, 0xb0 );
			$this->ac5 = $this->read_unsigned_short( 0xb3, 0xb2 );
			$this->ac6 = $this->read_unsigned_short( 0xb5, 0xb4 );
			$this->b1 = $this->read_signed_short( 0xb7, 0xb6 );
			$this->b2 = $this->read_signed_short( 0xb9, 0xb8 );
			$this->mb = $this->read_signed_short( 0xbb, 0xba );
			$this->mc = $this->read_signed_short( 0xbd, 0xbc );
			$this->md = $this->read_signed_short( 0xbf, 0xbe );
			
			if( $this->debug_mode ) {
				echo "\nac1: " . $this->ac1;
				echo "\nac2: " . $this->ac2;
				echo "\nac3: " . $this->ac3;
				echo "\nac4: " . $this->ac4;
				echo "\nac5: " . $this->ac5;
				echo "\nac6: " . $this->ac6;
				echo "\nb1:  " . $this->b1;
				echo "\nb2:  " . $this->b2;
				echo "\nmb:  " . $this->mb;
				echo "\nmc:  " . $this->mc;
				echo "\nmd:  " . $this->md;
			}
			
		}
		
		private function read_uncompensated_temperature() {
			$this->write_register( 0xf4, 0x2e );
			usleep( 4500 );
			$this->ut = $this->read_unsigned_short( 0xf7, 0xf6 );
			if( $this->debug_mode )
				echo "\nut:  " . $this->ut;
		}
		
		private function read_uncompensated_pressure() {
			$this->write_register( 0xf4, 0x34 );
			usleep( 4500 );
			$this->up = $this->read_unsigned_long( 0xf8, 0xf7, 0xf6 );
			if( $this->debug_mode )
				echo "\nut:  " . $this->up;
		}
		
		private function calculate_readings() {

			// calculate true temperature
			$x1 = ( $this->ut - $this->ac6 ) * $this->ac5 / pow( 2, 15 );
			$x2 = $this->mc * pow( 2, 11 ) / ( $x1 + $this->md );
			$b5 = $x1 + $x2;
			$this->t = ( $b5 + 8 ) / pow( 2, 4 );
			if( $this->debug_mode )
				echo "\nt:   " . $this->t;
				
			// calculate true pressure
			$b6 = $b5 - 4000;
			$x1 = ( $this->b2 * ( $b6 * $b6 / pow( 2, 12 ) ) ) / pow( 2, 11 );
			$x2 = $this->ac2 * $b6 / pow( 2, 11 );
			$x3 = $x1 + $x2;
			$b3 = ( ( $this->ac1 * 4 + $x3 ) << 2 ) / 4;
			$x1 = $this->ac3 * $b6 / pow( 2, 13 );
			$x2 = ( $this->b1 * ( $b6 * $b6 / pow( 2, 12 ) ) ) / pow( 2, 16 );
			$x3 = ( ( $x1 + $x2 ) + 2 ) / pow( 2, 2 );
			$b4 = $this->ac4 * ( $x3 + 32768 ) / pow( 2, 15 );
			$b7 = ( $this->up - $b3 ) * ( 50000 );
			if( $b7 < 0x80000000 )
				$p = ( $b7 * 2 ) / $b4;
			else
				$p = ( $b7 / $b4 ) * 2;
			$x1 = ( $p / pow( 2, 8 ) ) * ( $p / pow( 2, 8 ) );
			$x2 = ( -7357 * $p ) / pow( 2, 16 );
			$this->p = $p + ( $x1 + $x2 + 3791 ) / pow( 2, 4 );
			if( $this->debug_mode )
				echo "\np:   " . $this->p;
			
			// calculate altitude
			$this->a = 44330 * ( 1 - pow( ( $this->p / 101325 ), ( 1 / 5.255 ) ) );
			if( $this->debug_mode )
				echo "\na:   " . $this->a;
				
		}		
				
	}

?>