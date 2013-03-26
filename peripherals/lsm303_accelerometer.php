<?php
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the LSM303DLHC Acceleromter ( probably works with the entire LSM303 family )
	//
	//	- LSM303DLHC accelerometer ic documentation: http://www.pololu.com/file/download/LSM303DLHC.pdf?file_id=0J564
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
	//
	class lsm303_accelerometer extends i2c_bus {
		
		// raw acceleration registers
		private $out_x_l = 0x28;
		private $out_x_h = 0x29;
		private $out_y_l = 0x2a;
		private $out_y_h = 0x2b;
		private $out_z_l = 0x2c;
		private $out_z_h = 0x2d;
		
		// control registers
		private $ctrl_reg4 = 0x23;
		
		private $raw_acceleration = array();	// array containing raw acceleration data
		private $acceleration = array();		// array containing acceleration in gs
		
		// resolution
		private $resolution = 2;				// resolution, chip defaults to +/- 2Gs
		private $resolution_marks = 32768;
		
		function __construct() {
			
			parent::__construct();
			
			// set the default i2c bus location for the LSM303
			$this->slave_i2c_register = 0x19;
			
		}
		
		public function get_resolution() {
			
			// read settings from register
			$settings = str_pad( base_convert( $this->read_register( $this->ctrl_reg4 ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			
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
			$settings = str_pad( base_convert( $this->read_register( $this->ctrl_reg4 ), 16, 2 ), 8, 0, STR_PAD_LEFT );
			$settings = substr( $settings, 0, 2 ) . $value . substr( $settings, 4, 4 );
			$this->set_register( $this->ctrl_reg4, base_convert( $settings, 2, 10 ) );
			
		}
		
		public function get_acceleration() {
			$accel['x'] = $this->get_reading( $this->out_x_h, $this->out_x_h ) / ( $this->resolution_marks / $this->resolution );
			$accel['y'] = $this->get_reading( $this->out_y_h, $this->out_y_h ) / ( $this->resolution_marks / $this->resolution );
			$accel['z'] = $this->get_reading( $this->out_z_h, $this->out_z_h ) / ( $this->resolution_marks / $this->resolution );
			$this->acceleration = $accel;
			return $this->acceleration;
		}
		
		private function get_reading(
			$lsb_register,	// least significant byte ( register location )
			$msb_register	// most significant byte
		) {
			$lsb = intval( $this->read_register( $lsb_register ), 16 );
			$msb = intval( $this->read_register( $msb_register ), 16 );
			$val = ( $msb << 8 ) + $lsb;
			$array = unpack( 's', pack( 'v', $val ) );
			$decimal_value = $array[1];
			return $decimal_value;
		}
		
		public function get_roll() {
			return atan2( $this->acceleration['y'], sqrt( pow( $this->acceleration['x'], 2 ) + pow( $this->acceleration['z'], 2 ) ) ) * 180 / pi();
		}
		
		public function get_pitch() {
			return -1 * atan2( $this->acceleration['x'], sqrt( pow( $this->acceleration['y'], 2 ) + pow( $this->acceleration['z'], 2 ) ) ) * 180 / pi();
		}
		
		
		
	}

?>