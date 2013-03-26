<?php
	
	// include parent class
	require_once( 'i2c_bus.php' );
	
	// i2c communication with the BMP085 pressure, temperature, and altitude sensor
	//
	//	- datasheet: http://www.adafruit.com/datasheets/BMP085_DataSheet_Rev.1.0_01July2008.pdf
	//	- embedded on the adafruit BMP085 board: http://www.adafruit.com/products/391
	//
	//
	//
	class bmp085 extends i2c_bus {
		
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
		
		function __construct() {
			
			parent::__construct();
			
			// set the default i2c bus location for the BMP085
			$this->slave_i2c_register = 0x77;
			
			// calibrate
			$this->get_calibration_data();
			
		}
		
		private function get_calibration_data()
		{
			
			$this->ac1 = $this->read_16_bit_signed( 0xab, 0xaa );
			$this->ac2 = $this->read_16_bit_signed( 0xad, 0xac );
			$this->ac3 = $this->read_16_bit_signed( 0xaf, 0xae );
			$this->ac4 = $this->read_16_bit_unsigned( 0xb1, 0xb0 );
			$this->ac5 = $this->read_16_bit_unsigned( 0xb3, 0xb2 );
			$this->ac6 = $this->read_16_bit_unsigned( 0xb5, 0xb4 );
			$this->b1 = $this->read_16_bit_signed( 0xb7, 0xb6 );
			$this->b2 = $this->read_16_bit_signed( 0xb9, 0xb8 );
			$this->mb = $this->read_16_bit_signed( 0xbb, 0xba );
			$this->mc = $this->read_16_bit_signed( 0xbd, 0xbc );
			$this->md = $this->read_16_bit_signed( 0xbf, 0xbe );
			
			echo "\n" . $this->ac1;
			echo "\n" . $this->ac2;
			echo "\n" . $this->ac3;
			echo "\n" . $this->ac4;
			echo "\n" . $this->ac5;
			echo "\n" . $this->ac6;
			echo "\n" . $this->b1;
			echo "\n" . $this->b2;
			echo "\n" . $this->mb;
			echo "\n" . $this->mc;
			echo "\n" . $this->md;
			
		}
		
		public function get_fields() {
			$this->fields['x'] = $this->read_16_bit_signed( $this->out_x_l, $this->out_x_h );
			$this->fields['y'] = $this->read_16_bit_signed( $this->out_y_l, $this->out_y_h );
			$this->fields['z'] = $this->read_16_bit_signed( $this->out_z_l, $this->out_z_h );
			return $this->fields;
		}
		
		public function get_heading() {
			return atan2( $this->fields['y'], $this->fields['y'] );
		}
		
	}

?>