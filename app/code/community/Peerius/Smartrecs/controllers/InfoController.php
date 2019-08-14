<?php

/**
 * syncing the products to the peerius server
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_InfoController extends Mage_Core_Controller_Front_Action {

   
  /*
   * returns the parameters of the created peerius SMARTrecs widgets
   * @return string
   */
  public function allconfigAction() {
    if ($this->checkToken()) {
      
      $modules = Mage::getConfig()->getNode('modules')->children();
	  $modulesArray = (array)$modules;
	  $connectVersion = 'unknown';
	  //foreach ($modulesArray as $module) {
	  //	$this->log($module->getName()." is on version ".$module->version);
	  //}
	   
	  if($modulesArray['Peerius_Smartrecs']->is('active')) {
	  	$connectVersion = $modulesArray['Peerius_Smartrecs']->version;
	  	$this->log("Peerius_Connect version is ".$connectVersion);
	  } else {
	  	$this->log("Peerius_Connect is not available");
	  }
      
      $configs = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path',array('like'=>'peerius%'))->setOrder('config_id',Varien_Data_Collection::SORT_ORDER_ASC);
      $siteName = Mage::getStoreConfig('peerius/general/client_name',Mage::app()->getStore()); 
      $response = '<!doctype html><html lang=\'en-gb\'><head><meta charset=\'utf-8\'><title> CONFIG: '.$siteName.'</title>'.$this->getCSS().'</head><body style="font-family:Arial;">';
      $response .= '<section><h1>Peerius Connect Configuration : '. $siteName . '</h1> (v ' .$connectVersion. ')';

      $sec1 = "";
      $sec2 = "";
      foreach ($configs as $config) {
      	$path = str_replace('max','number_of_products', $config->getPath());
       
        $name_arr = explode ("/", $path);
        $sec1 =  $name_arr[1];
        $secName =  ($sec1 != $sec2) ? $sec1 : "";
        $sec2 = $sec1;
        if($secName!="")  {
        	$response .= '<div class=\'secTitle\'>'.ucfirst($secName). ' Config: </div>';
        }
        $paramName = $name_arr[2];
        $paramValue = $config->getValue();
        $dispName = $paramName;
        $dispValue = $paramValue;
        
        switch ($paramName) {
		    case "token":
		        $dispValue = $dispValue.' (ENCODED: '.sha1($dispValue).')';
		        break;
		    case "enableall":
		    	$dispName = 'Are Recs anabled for site?';
		        $dispValue = ($dispValue == 1) ? 'Yes' : 'No';
		        break;
		    case "disable":
		    	$dispName = 'Are Recs enabled for page?';
		        $dispValue = ($dispValue == 0) ? 'Yes' : 'No';
		        break;
		    case "cronjob":
				$dispName = 'Feed scheduled at?';
		        break;
		    case "testmode":
				$dispName = 'Is test mode enabled?';
				$dispValue = ($dispValue == 1) ? 'Yes' : 'No';
		        break;
		    case "registrationstatus":
				$dispName = 'Registration status?';
				$dispValue = ($dispValue == 1) ? 'Registered' : 'Something happend that shouldn\'t have!';
		        break;
		    case "headline":
				$dispName = 'Widget title';
		        break;
			case "success":
				$dispName = 'Show recs for all search?';
				$dispValue = ($dispValue == 0) ? 'No, only for zero search' : 'Yes';
		        break;		    
		    default:
		        break;
		}
        
        $response .= '<div class=\'boxA\'>'.ucfirst($dispName).' </div><div class=\'boxB\'> '.$dispValue . "</div><br />";
      }
      
      $collection = Mage::getModel('widget/widget_instance')->getCollection();
      $collection->addFieldToFilter('instance_type', 'smartrecs/recommendations');

	  if($collection->getSize()>0) 
	  {
		$response .= '<h1>Widget Instance Manager</h1>';
		foreach($collection as $item)
		{
			$response .= $item->title.'<hr>';
			$response .= $item->widget_parameters;
			$response .= '<br><br><br>';
		}
      }
      
      $response .= '</section></body></html>';
      $this->getResponse()->setHeader('Content-Type', 'text/html')->setBody($response);
      
    }
  }
  
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
  
  /**
     * return CSS
  */
  protected function getCSS() {
      return "<style type='text/css'> 
         .secTitle {clear:both;display:block;float:left;padding:5px;margin:10px 0 5px 0;width:100%;color:#808080;font-weight:bold; font-size:20px;}
         .boxA { clear:left;display:block;float:left;padding:2px 0 0 4px;margin:1px;background-color: #60b346 ; width:200px; height:22px;  }
         .boxB { float:left;display:in-line;padding:2px 0 0 4px;margin:1px;background-color: #A9Ae99; width:650px; height:22px;color:#000; }
      </style>";
  }

}
