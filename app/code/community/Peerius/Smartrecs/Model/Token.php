<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Model_Token extends Mage_Core_Model_Config_Data {

  public function save() {
    $token = $this->getValue(); //get the value from our config
    if ($token AND !preg_match("/^[a-zA-Z0-9]+$/", $token)) {
      Mage::throwException("Token allows only alphanumeric characters.");
    }

    return parent::save();  //call original save method so whatever happened 
    //before still happens (the value saves)
  }

  /**
   * Retrieve adminhtml session model object
   *
   * @return Mage_Adminhtml_Model_Session
   */
  protected function _getSession() {
    return Mage::getSingleton('adminhtml/session');
  }


}