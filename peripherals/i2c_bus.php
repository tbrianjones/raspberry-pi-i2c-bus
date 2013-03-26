<?php

	// class to deal with i2c communications on the raspberry pi
	//
	//	- this class makes use of i2c-tools ( see readme for installation instructions )
	//		- http://www.acmesystems.it/i2c
	//

	class i2c_bus
	{
		
		private $block = 1; 				// the i2c block ( 0 on first gen rpi's, 1 on subsequnet rpi's )
		protected $slave_i2c_register;		// the i2c bus address of the unit being communicated with ( set in a child class )
		
		function __construct() {
						
			///$this->i2c = fopen( '/dev/i2c-' . $this->block, "w+b" );
			
			/*	// read
				$address = ($address | 0x01) << 8 & $registry;
				$i2c = fopen("/dev/i2c-2", "w+b");
				fseek($i2c, $address)
				$rtn = fread($i2c, $length)
				fclose($i2c);
			
				// write to chip
				$address = ($address & 0xFE) << 8 & $registry;
				$i2c = fopen("/dev/i2c-2", "w+b");
				fseek($i2c, $address)
				fwrite($i2c, $data, $length)
				fclose($i2c);
			*/
			
		}
		
		function __destruct() {
			//pclose( $this->i2c );
		}


	// --- READERS --------------------------------------------------------------------
	
		
		protected function read_register(
			$register	// register location ( eg. 0x29 )
		) {
			return trim( shell_exec( 'i2cget -y ' . $this->block . ' ' . $this->slave_i2c_register . ' ' . $register ) );
		}
				
		protected function read_16_bit_signed(
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
		
		protected function read_16_bit_unsigned(
			$lsb_register,	// least significant byte ( register location )
			$msb_register	// most significant byte
		) {
			$lsb = intval( $this->read_register( $lsb_register ), 16 );
			$msb = intval( $this->read_register( $msb_register ), 16 );
			$val = ( $msb << 8 ) + $lsb;
			$array = unpack( 'S', pack( 'v', $val ) );
			$decimal_value = $array[1];
			return $decimal_value;
		}
		
		
	// --- WRITERS --------------------------------------------------------------------
	
		
		protected function set_register(
			$register,	// register address ( eg. 0x29 )
			$value		// value to set at register address ( must be a decimal value, eg. 10001001 should be passed as 
		) {
			shell_exec( 'i2cset -y ' . $this->block . ' ' . $this->slave_i2c_register . ' ' . $register . ' ' . $value );
		}

		
	}
	
?>