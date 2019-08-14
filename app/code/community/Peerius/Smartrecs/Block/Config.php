<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Block_Config extends Mage_Core_Block_Template {
    
  protected function _toHtml() {
    if (strpos(Mage::helper('core/url')->getCurrentUrl(), '/section/peerius/') !== false) {
      Mage::getModel('core/config')->saveConfig('peerius/general/adminip', Mage::helper('core/http')->getRemoteAddr(true));
      return parent::_toHtml();
    }
  }
}

