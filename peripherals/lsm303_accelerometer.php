<?php
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the LSM303DLHC Acceleromter ( probably works with the entire LSM303 family )
	//
	//	- datasheet: http://www.pololu.com/file/download/LSM303DLHC.pdf?file_id=0J564
	//	- embedded on the adafruit LSM303 board: http://www.adafruit.com/products/1120
	//
	//	ORIENTATION ( right hand rule applies based on arrows on board*** )
	//
	//		*** data is not correct when the board goes past 90 degrees ( will rework later )
	//			- doesn't do 360 degree orientation
	//
	//		- rotation around the boards x axis is considered roll
	//		- rotation around the boards y axis is considered pitch
	//		- rotation around the boards z axis is considered yaw
	//
	//	DEV NOTES
	//		- yaw cannot be calculated without magnetometer data being incorporated
	//		- roll and pitch only seem correct when the other is 0.
	//
	//
	class lsm303_accelerometer {
		
		// address of this device on the i2c bus
		const i2c_address = 0x19;
		
		// raw acceleration registers
		const out_x_l = 0x28;
		const out_x_h = 0x29;
		const out_y_l = 0x2a;
		const out_y_h = 0x2b;
		const out_z_l = 0x2c;
		const out_z_h = 0x2d;
		
		// control registers
		const ctrl_reg4 = 0x23;
		
		private $raw_acceleration = array();	// array containing raw acceleration data
		private $acceleration = array();		// array containing acceleration in gs
		
		// resolution
		private $resolution = 2;				// resolution, chip defaults to +/- 2Gs
		const resolution_marks = 32768;
		
		function __construct() {
			
			// instantiate i2c communication class and pass the default i2c bus address for this device
			$this->I2c = new i2c_bus( self::i2c_address );
			
		}
		
		public function get_resolution() {
			
			// read settings from register
			$settings = str_pad( base_convert( $this->I2c->read_register( self::ctrl_reg4 ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			
			// get resolution bits and translate them
			$resolution = substr( $settings, 2, 2 );
			if( $resolution == '00' )
				$this->resolution = 2;
			else if( $resolution == '01' )
				$this->resolution = 4;
			else if( $resolution == '10' )
				$this->resolution = 8;
			else if( $resolution == '11' )
				$this->resolution = 16;
			
			// return resolution ( +/- Gs )
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
			$settings = str_pad( base_convert( $this->I2c->read_register( self::ctrl_reg4 ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			$settings = substr( $settings, 0, 2 ) . $value . substr( $settings, 4, 4 );
			$this->I2c->write_register( self::ctrl_reg4, base_convert( $settings, 2, 10 ) );
			
		}
		
		public function get_acceleration() {
			$accel['x'] = $this->I2c->read_signed_short( self::out_x_h ) / ( self::resolution_marks / $this->resolution );
			$accel['y'] = $this->I2c->read_signed_short( self::out_y_h ) / ( self::resolution_marks / $this->resolution );
			$accel['z'] = $this->I2c->read_signed_short( self::out_z_h ) / ( self::resolution_marks / $this->resolution );
			$this->acceleration = $accel;
			return $this->acceleration;
		}
				
		public function get_roll() {
			return atan2( $this->acceleration['y'], sqrt( pow( $this->acceleration['x'], 2 ) + pow( $this->acceleration['z'], 2 ) ) ) * 180 / pi();
		}
		
		public function get_pitch() {
			return -1 * atan2( $this->acceleration['x'], sqrt( pow( $this->acceleration['y'], 2 ) + pow( $this->acceleration['z'], 2 ) ) ) * 180 / pi();
		}
		
	}

?>