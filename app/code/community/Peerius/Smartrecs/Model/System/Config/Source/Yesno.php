<?php

class Peerius_Smartrecs_Model_System_Config_Source_Yesno {

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray() {
    return array(
        array('value' => 0, 'label' => Mage::helper('adminhtml')->__('Yes')),
        array('value' => 1, 'label' => Mage::helper('adminhtml')->__('No')),
    );
  }

  /**
   * Get options in "key-value" format
   *
   * @return array
   */
  public function toArray() {
    return array(
        1 => Mage::helper('adminhtml')->__('No'),
        0 => Mage::helper('adminhtml')->__('Yes'),
    );
  }

}