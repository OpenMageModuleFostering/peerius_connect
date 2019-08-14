<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Model_Templates {

  protected $_options;
  protected $_default = 'horizontal.phtml';
  
  protected $_file = array(
      'home.phtml',
      'search.phtml',
      'product-1.phtml',
      'product-2.phtml',
      'basket.phtml',
      'category.phtml',
      'custom-1.phtml',
      'custom-2.phtml',
      'custom-3.phtml',
      'custom-4.phtml',
      'custom-5.phtml'
      );

  public function toOptionArray() {
    if (!$this->_options) {

      $this->_options[] = '---';
      $this->_options[$this->_default] = $this->_default;
      foreach ($this->_file as $entry) {
        $this->_options[$entry] = $entry;
      }
    }
    return $this->_options;
  }

}