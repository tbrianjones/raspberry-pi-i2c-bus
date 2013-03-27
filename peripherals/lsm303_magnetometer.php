<?php
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the LSM303DLHC Magnetometer ( probably works with the entire LSM303 family )
	//
	//	- datasheet: http://www.pololu.com/file/download/LSM303DLHC.pdf?file_id=0J564
	//	- embedded on the adafruit LSM303 board: http://www.adafruit.com/products/1120
	//
	//	DEV NOTES:
	//		- this is very incomplete
	//		- writing and reading the resolution doesn't work
	//		- not sure the magnetometer data is correct ... not sure how to test
	//		- heading does not work
	//
	//
	class lsm303_magnetometer extends i2c_bus {
		
		// address of this device on the i2c bus
		const i2c_address = 0x1e;
		
		// raw magnetometer registers
		const out_x_l = 0x04;
		const out_x_h = 0x03;
		const out_y_l = 0x08;
		const out_y_h = 0x07;
		const out_z_l = 0x06;
		const out_z_h = 0x05;
		
		// gain setting registers ( resolution )
		const crb_reg = 0x01;
		
		private $raw_fields = array(); // array containing raw magnetic field data
		private $fields = array(); // array containing magnetic field data in gauss
		
		// resolution
		private $resolution = 1.3; // resolution, chip defaults to +/- 1.3 Gauss
		
		function __construct() {
			
			// instantiate i2c communication class and pass the default i2c bus address for this device
			$this->I2c = new i2c_bus( self::i2c_address );
			
		}
		
		public function get_resolution() {
			
			// read settings from register
			$settings = str_pad( base_convert( $this->I2c->read_register( self::crb_reg ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			
			// get resolution bits and translate them
			$resolution = substr( $settings, 0, 3 );
			switch( $resolution ) {
				case '001':
					$this->resolution = 1.3;
				case '010':
					$this->resolution = 1.9;
				case '011':
					$this->resolution = 2.5;
				case '100':
					$this->resolution = 4.0;
				case '101':
					$this->resolution = 4.7;
				case '110':
					$this->resolution = 5.6;
				case '111':
					$this->resolution = 8.1;
			}
						
			// return resolution ( +/- Gauss )
			return $this->resolution;
			
		}
		
		public function set_resolution(
			$resolution // ( +/- Gs, options are 2, 4, 8, 16 )
		) {
			
			// convert resolution to binary value
			if( $resolution == 2 )
				$value = 00;
			else if( $resolution == 4 )
				$value = 01;
			else if( $resolution == 8 )
				$value = 10;
			else if( $resolution == 16 )
				$value = 11;
			else
				throw new Exception( 'invalid resolution value for accelerometer' );
			
			// update resolution class value
			$this->resolution = $resolution;
			
			// update the settings on the lsm303
			$settings = str_pad( base_convert( $this->I2c->read_register( self::crb_reg ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			$settings = substr( $settings, 0, 2 ) . $value . substr( $settings, 4, 4 );
			$this->write_register( self::crb_reg, base_convert( $settings, 2, 10 ) );
			
		}
		
		public function get_fields() {
			$this->fields['x'] = $this->I2c->read_signed_short( self::out_x_h );
			$this->fields['y'] = $this->I2c->read_signed_short( self::out_y_h );
			$this->fields['z'] = $this->I2c->read_signed_short( self::out_z_h );
			return $this->fields;
		}
		
		public function get_heading() {
			return atan2( $this->fields['y'], $this->fields['y'] );
		}
		
	}

?>