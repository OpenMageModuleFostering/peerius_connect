<?php

/**
 * syncing the products to the peerius server
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_InfoController extends Mage_Core_Controller_Front_Action {

  /**
   * when is the cron executed?
   * @return int
   */
  public function createtimeAction() {
    if ($this->checkToken()) {
      $response = (int) Mage::getStoreConfig('peerius/general/cronjob');
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /**
   * what registration status has the shop
   * @return int
   */
  public function registrationstatusAction() {
    if ($this->checkToken()) {
      $response = (int) Mage::getStoreConfig('peerius/general/registrationstatus');
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /**
   * what registration status has the shop
   * @return int
   */
  public function registrationerrorAction() {
    if ($this->checkToken()) {
      $response = (int) Mage::getStoreConfig('peerius/general/registrationerror');
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /*
   * returns the parameters of the created peerius SMARTrecs widgets
   * @return string
   */
  public function widgetsAction() {
    if ($this->checkToken()) {
      $response = '';
      $collection = Mage::getModel('widget/widget_instance')->getCollection();
      $collection->addFieldToFilter('instance_type', 'smartrecs/recommendations');

      foreach($collection as $item)
      {
        $response .= $item->title.'<hr>';
        $response .= $item->widget_parameters;
        $response .= '<br><br><br>';
      }
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /*
   * returns the parameters of the created peerius SMARTrecs widgets
   * @return string
   */
  public function allconfigAction() {
    if ($this->checkToken()) {
      $response = '';
      
      $response .= '<h1>Standard Widgets</h1>';
      $configs = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path',array('like'=>'peerius%'))->setOrder('path');
      
      foreach ($configs as $config) {
        $path = str_replace('max','number_of_products', $config->getPath());
        $value = $config->getValue();
        $response .= 'Config: ' . $path.'<br>';
        $response .= 'Value: ' . $value;
        $response .= '<hr>';
      }
      
      $response .= '<h1>Widget Instance Manager</h1>';
      
      
      $collection = Mage::getModel('widget/widget_instance')->getCollection();
      $collection->addFieldToFilter('instance_type', 'smartrecs/recommendations');

      foreach($collection as $item)
      {
        $response .= $item->title.'<hr>';
        $response .= $item->widget_parameters;
        $response .= '<br><br><br>';
      }
      
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /*
   * resets the registration status
   * @return string
   */
  public function registrationstatusresetAction() {
    if ($this->checkToken()) {
      Mage::getModel('core/config')->saveConfig('peerius/general/registrationstatus', '0');
      Mage::getModel('core/config')->saveConfig('peerius/general/registrationerror', '0');
    }
  }
  
  
  /*
   * deletes the configuration
   * @return string
   */
  public function resetconfigAction() {
    if ($this->checkToken()) {
      $configs = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path',array('like'=>'peerius%'));
      
      foreach ($configs as $config) {
        $config->delete($config);
      }
      $response = count($configs) . ' entries deleted';
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /*
   * sets the admin IP to preview recommendations in test mode
   * @return string
   */
  public function adminipAction() {
    if ($this->checkToken()) {
      if (!filter_var($this->getRequest()->getParam('ip'), FILTER_VALIDATE_IP) === false) {
        Mage::getModel('core/config')->saveConfig('peerius/general/adminip', $this->getRequest()->getParam('ip'));
      }
    }
  }
  
  /*
   * reads the admin IP
   * @return string
   */
  public function adminipreadAction() {
    if ($this->checkToken()) {
      $response = Mage::getStoreConfig('peerius/general/adminip');
    
      $this->getResponse()
              ->setHeader('Content-Type', 'text/html')
              ->setBody($response);
    }
  }
  
  /*
   * checks for the valid token to prevent URL manipulation
   * @return bool
   */
  public function checkToken() {
    $token = Mage::getStoreConfig('peerius/general/token');

    if (!$token OR !$this->getRequest()->getParam('token') OR ($this->getRequest()->getParam('token') != sha1($token))) {
      $this->log("token error");
      return false;
    }
    return true;
  }

  /**
   * @param string $message
   */
  protected function log($message) {
    Mage::helper('peerius_smartrecs')->log(Zend_Log::DEBUG, $message);
  }

}
