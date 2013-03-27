TBJs Raspberry Pi PHP Tools
===========================
- php software to interface a raspberry pi with various board level peripherals


problems, bugs, and dev notes
-----------------------------
- not sure how to trigger the accelerometer to start producing data. i currently need to trigger it with a third party c app ( ../LSM303DLHC/LSM303 ).


notes about using this library
------------------------------
- applications need to be run as root to access the i2c bus


required libraries
------------------

### i2c-tools for i2c bus communication
- installation: http://learn.adafruit.com/adafruits-raspberry-pi-lesson-4-gpio-setup/configuring-i2c
- documentation: http://www.acmesystems.it/i2c


suggested hardware used with this repository
--------------------------------------------

### Raspberry Pi Model B Revision 2.0 (512MB)
- purchase: http://www.amazon.com/gp/product/B009SQQF9C/ref=as_li_ss_tl?ie=UTF8&camp=1789&creative=390957&creativeASIN=B009SQQF9C&linkCode=as2&tag=induinteinc-20
	
### LSM303 Triple-axis Accelerometer & Magnetometer Board
- purchase: http://www.adafruit.com/products/1120
- datasheet: http://www.pololu.com/file/download/LSM303DLHC.pdf?file_id=0J564
- project files
	- peripherals/lsm303_accelerometer.php
	- peripherals/lsm303_magnetometer.php

	
### BMP085 Barometric Pressure / Temperature / Altitude Sensor
- purchase: http://www.adafruit.com/products/391
- datasheet: http://www.adafruit.com/datasheets/BMP085_DataSheet_Rev.1.0_01July2008.pdf