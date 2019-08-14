<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Helper_Data extends Mage_Core_Helper_Abstract {

    const LOG_FILE = "peerius_smartrec.log";
    
    /**
     * is the module enabled?
     *
     * @return bool isActive
     */
    public function isActive()
    {
        return ! Mage::getStoreConfig('peerius/general/disabled');
    }
    
    /**
     * are we running in test mode=?
     *
     * @return bool isTestMode
     */
    public function isTestMode()
    {
        return Mage::getStoreConfig('peerius/general/testmode');
    }
    
    /**
     * is it the admin lookin at the page?
     *
     * @return bool isAdminIP
     */
    public function isAdminIP()
    {
        return (Mage::getStoreConfig('peerius/general/adminip') == Mage::helper('core/http')->getRemoteAddr(true));
    }

    /**
     * Method to log
     *
     * @param int    $level
     * @param string $message
     */
    public function log($level, $message) {
      Mage::log('PEERIUS: ' . $message, $level, static::LOG_FILE, true);
    }
  
    /*
     * guess the page type
     * @return string
     */
    public static function getPageType() {
      $fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();

      switch ($fullActionName) {
        case 'cms_index_index':
//          if (Mage::getModel('core/url')->getUrl('') == Mage::getModel('core/url')->getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true))) {
          $routeName = Mage::app()->getRequest()->getRouteName(); 
          $identifier = Mage::getSingleton('cms/page')->getIdentifier();

          if($routeName == 'cms' && $identifier == 'home') {
            $_pageType = 'home';
          } else {
            $_pageType = 'other';
          }
          break;
        case 'catalog_category_view':
          $_pageType = 'category';
          break;
        case 'catalog_product_view':
          $_pageType = 'product';
          break;
        case 'checkout_cart_index':
          $_pageType = 'basket';
          break;
        case 'checkout_onepage_index':
          $_pageType = 'checkout';
          break;
        case 'checkout_onepage_success':
          $_pageType = 'order';
          break;
        default:
          if (0 === strpos($fullActionName, 'catalogsearch_')) {
            $_pageType = 'search';
          } else if (0 === strpos($fullActionName, 'wishlist_')) {
            $_pageType = 'wishlist';
          } else {
            $_pageType = 'other';
          }
          break;
      }

      return $_pageType;
    }
    
    /*
     * read the plugin settings to see if recommendations are disabled
     * @return bool
     */
    public function getDisabled() {
      if ($this->isTestMode() AND (null !== Mage::app()->getRequest()->getParam('peeriuspreview')) AND (null !== Mage::app()->getRequest()->getParam('peeriuslocalpreview'))) {
        return true;
      }
  
      if (Mage::getStoreConfig('peerius/general/enableall') != 1) {
        return true;
      }

      return false;
      
    }
    
    /*
     * read the plugin settings to see if recommendations are disabled for this page
     * @return bool
     */
    public function getPageDisabled($page = null) {
      
      $pageType = $page ?:$this->getPageType();
     
//      if ($pageType == 'order' OR $pageType == 'basket') {
//        $pageType = 'checkout';
//      }

      if (Mage::getStoreConfig('peerius/'.$pageType.'/disable') == 1) {
        return true;
      }

      return false;
      
    }
    
	/*
	 * read the plugin settings to see if recommendations are disabled
	 * @return bool
     */
	public function getRecsAsSKUsOnly() {
		
		if (Mage::getStoreConfig('peerius/general/enableall') == 1 && Mage::getStoreConfig('peerius/general/getrecsasskusonly') == 1) {
			return true;
		}
		return false;
	}
    
}