<?php
	
	// *** PRESSURE AND ALTITUDE ARE BROKEN ( see dev notes below )
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the BMP085 pressure, temperature, and altitude sensor
	//
	//	- datasheet: http://www.adafruit.com/datasheets/BMP085_DataSheet_Rev.1.0_01July2008.pdf
	//	- embedded on the adafruit BMP085 board: http://www.adafruit.com/products/391
	//
	//	DEV NOTES
	//		- this class should be rewritten to get temp seperately from pressure as the
	//			datasheet says temp should only be requested once a second, when measuring
	//			pressure at a high rate.
	//		- uses default operation mode ( standard = 1 ), not sure how to change this
	//		- temperature works
	//		- pressure does not work ( and, as a result, altitude )
	//			- all data validates when pushing the datasheet values through the math operations
	//			- not sure what to do here??
	//
	class bmp085 {
				
		// debug mode
		const debug_mode = 1;
		
		// address of this device on the i2c bus
		const i2c_address = 0x77;
		
		// operation modes
		const ultra_low_power_mode			= 0;
		const standard_mode					= 1;
		const high_resolution_mode			= 2;
		const ultra_high_resolution_mode	= 3;
		
		// i2c connection
		private $I2c;
		
		// operating mode
		private $operating_mode;
		
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
						
			// instantiate i2c communication class and pass the default i2c bus address for the BMP085
			$this->I2c = new i2c_bus( self::i2c_address );
			
			// set operating mode ( don't know how to change on chip, so use default 'standard mode' )
			$this->operating_mode = self::standard_mode;
			
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
			
			// return data in an array
			return array(
				'temperature'	=> $this->t,
				'pressure'		=> $this->p,
				'altitude'		=> $this->a
			);
		}
	
		
	// --- READERS --------------------------------------------------------------
				
		
		private function read_calibration_data()
		{
			
			$this->ac1 = $this->I2c->read_signed_short( 0xaa );
			$this->ac2 = $this->I2c->read_signed_short( 0xac );
			$this->ac3 = $this->I2c->read_signed_short( 0xae );
			$this->ac4 = $this->I2c->read_unsigned_short( 0xb0 );
			$this->ac5 = $this->I2c->read_unsigned_short( 0xb2 );
			$this->ac6 = $this->I2c->read_unsigned_short( 0xb4 );
			$this->b1 = $this->I2c->read_signed_short( 0xb6 );
			$this->b2 = $this->I2c->read_signed_short( 0xb8 );
			$this->mb = $this->I2c->read_signed_short( 0xba );
			$this->mc = $this->I2c->read_signed_short( 0xbc );
			$this->md = $this->I2c->read_signed_short( 0xbe );
			
			if( self::debug_mode ) {
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
			$this->I2c->write_register( 0xf4, 0x2e );
			usleep( 4500 );
			$this->ut = $this->I2c->read_unsigned_short( 0xf6 );
			if( self::debug_mode )
				echo "\nut:  " . $this->ut;
		}
		
		private function read_uncompensated_pressure() {
			$this->I2c->write_register( 0xf4, 0x34 + ( $this->operating_mode << 6 ) );
			usleep( 4500 );
			$this->up = $this->I2c->read_unsigned_long( 0xf6 ) >> ( 8 - $this->operating_mode );
			if( self::debug_mode )
				echo "\nup:  " . $this->up;
		}
		
		private function calculate_readings() {
			
			// set to true to use datasheet sample calibration data to test math operations
			if( false ) {
				$this->ac1 = 408;
				$this->ac2 = -72;
				$this->ac3 = -14383;
				$this->ac4 = 32741;
				$this->ac5 = 32757;
				$this->ac6 = 23153;
				$this->b1 = 6190;
				$this->b2 = 4;
				$this->mb = -32767;
				$this->mc = -8711;
				$this->md = 2868;
				$this->operating_mode = self::ultra_low_power_mode;
				$this->ut = 27898;
				$this->up = 23843;
			}
						
			// calculate true temperature
			$x1 = ( $this->ut - $this->ac6 ) * $this->ac5 >> 15;
			$x2 = ( $this->mc << 11 ) / ( $x1 + $this->md );
			$b5 = $x1 + $x2;
			$this->t = ( $b5 + 8 ) >> 4;
			$this->t = $this->t * 0.1; // convert to celcius
			if( self::debug_mode ) {
				echo "\nx1:  $x1";
				echo "\nx2:  $x2";
				echo "\nb5:  $b5";
				echo "\nt:   " . $this->t;
			}
			
			// calculate true pressure
			$b6 = $b5 - 4000;
			$x1 = ( $this->b2 * ( $b6 * $b6 ) >> 12 ) >> 11;
			$x2 = ( $this->ac2 * $b6 ) >> 11;
			$x3 = $x1 + $x2;
			$b3 = ( ( ( $this->ac1 * 4 + $x3 ) << $this->operating_mode ) + 2 ) / 4;
			if( self::debug_mode ) {
				echo "\nb6:  $b6";
				echo "\nx1:  $x1";
				echo "\nx2:  $x2";
				echo "\nx3:  $x3";
				echo "\nb3:  $b3";
			}
			$x1 = ( $this->ac3 * $b6 ) >> 13;
			$x2 = ( $this->b1 * ( ( $b6 * $b6 ) >> 12 ) ) >> 16;
			$x3 = ( ( $x1 + $x2 ) + 2 ) >> 2;
			$b4 = ( $this->ac4 * ( $x3 + 32768 ) ) >> 15;
			$b7 = ( $this->up - $b3 ) * ( 50000 >> $this->operating_mode );
			if( $b7 < 0x80000000 )
				$p = ( $b7 * 2 ) / $b4;
			else
				$p = ( $b7 / $b4 ) * 2;
			if( self::debug_mode ) {
				echo "\nx1:  $x1";
				echo "\nx2:  $x2";
				echo "\nx3:  $x3";
				echo "\nb4:  $b4";
				echo "\nb7:  $b7";
				echo "\np:   $p";
			}
			$x1 = ( $p >> 8 ) * ( $p >> 8 );
			if( self::debug_mode )
				echo "\nx1:  $x1";
			$x1 = ( $x1 * 3038 ) >> 16;
			$x2 = ( -7357 * $p ) >> 16;
			$this->p = $p + ( ( $x1 + $x2 + 3791 ) >> 4 );
			if( self::debug_mode ) {
				echo "\nx1:  $x1";
				echo "\nx2:  $x2";
				echo "\np:   " . $this->p;
			}
			
			// calculate altitude
			$this->a = 44330 * ( 1 - pow( ( $this->p / 101325 ), ( 0.1903 ) ) );
			if( self::debug_mode )
				echo "\na:   " . $this->a;
				
		}		
				
	}

?>