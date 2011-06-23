<?php
/**
 * Php.php
 *
 * @package  A_Config
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD
 * @link	 http://skeletonframework.com/
 */

/**
 * A_Config_Php
 * 
 * Support storing configuration data in a PHP.  File must contain a variable named $config containing config data
 */
class A_Config_Php extends A_Config_Base {
   protected function _loadFile() {
      include $this->_filename;
      return $config;
   }
}