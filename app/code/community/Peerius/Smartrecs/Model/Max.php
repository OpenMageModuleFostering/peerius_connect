<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Model_Max {

  protected $_options;
  protected $_default = 3;

  public function toOptionArray() {
    if (!$this->_options) {
      for ($i = 1; $i <= 10; $i++) {
        $this->_options[$i] = $i;
      }
    }

    return $this->_options;
  }

}