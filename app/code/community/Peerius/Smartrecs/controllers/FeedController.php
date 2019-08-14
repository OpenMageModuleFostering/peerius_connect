<?php

/**
 * create the product feed
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_FeedController extends Mage_Core_Controller_Front_Action {

  /**
   * Smartrecs helper
   * @return Peerius_Smartrecs_Helper_Confighelper
   */
  protected function _helper() {
    return Mage::helper("peerius_smartrecs/feedhelper");
  }

  /**
   * create the product feed
   * @return null
   */
  public function createAction() {
    if (Mage::app()->getRequest()->getParam('version') AND Mage::app()->getRequest()->getParam('version') == 3) {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creatorsql');
    } else {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creator');
    }

    if ($creator->checkToken()) {
      $website = $this->_checkSite();
      if (is_null($website)) {
        $website = Mage::app()->getWebsite(1);
      }
      set_time_limit(0);
      ignore_user_abort(true);
      if ($this->getRequest()->getParam('debug')==1) {
        error_reporting(E_ALL);
        ini_set('display_errors','on');
      }

      $creator->process($website);
    }
  } 

  /**
   * return the product feed
   * @return null
   */
  public function showAction() {
    if (Mage::app()->getRequest()->getParam('version') AND Mage::app()->getRequest()->getParam('version') == 3) {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creatorsql');
    } else {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creator');
    }

    if ($creator->checkToken()) {
      $website = $this->_checkSite();
      if (is_null($website)) {
        $website = Mage::app()->getWebsite(1);
      }
      $this->fileName = Mage::getBaseDir('tmp') . DS . str_replace(' ', '_', $website->getName()) .
              "_PeeriusFeed" . ".xml";

      $this->getResponse()->setHeader('Cache-Control', 'no-cache, must-revalidate');
      $this->getResponse()->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
      $this->getResponse()->setHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

      $file = new Varien_Io_File();
      $this->_prepareDownloadResponse($this->fileName, $file->read($this->fileName), 'application/rss+xml');
    }
  }

  /**
   * create and show the product feed
   * @return null
   */
  public function createAndShowAction() {
    $website = $this->_checkSite();
    if (is_null($website)) {
      $website = Mage::app()->getWebsite(1);
    }
    set_time_limit(0);
    ignore_user_abort(true);
    if ($this->getRequest()->getParam('debug')==1) {
      error_log(E_ALL);
      ini_set('display_errors','on');
    }
    if (Mage::app()->getRequest()->getParam('version') AND Mage::app()->getRequest()->getParam('version') == 3) {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creatorsql');
    } else {
      $creator = Mage::getSingleton('peerius_smartrecs/feed_creator');
    }

    if ($creator->checkToken()) {
      $creator->process($website);

      // ---


      $this->fileName = Mage::getBaseDir('tmp') . DS . str_replace(' ', '_', $website->getName()) .
              "_PeeriusFeed" . ".xml";

      $this->getResponse()->setHeader('Cache-Control', 'no-cache, must-revalidate');
      $this->getResponse()->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
      $this->getResponse()->setHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
      
      $file = new Varien_Io_File();
      $this->_prepareDownloadResponse($this->fileName, $file->read($this->fileName), 'application/rss+xml');
    }
  }

  /**
   * @param $websiteName
   * @return mixed
   */
  protected function _getWebsiteByName($websiteName) {
    return Mage::getResourceModel('core/website_collection')
                    ->addFieldToFilter('name', $websiteName)
                    ->getFirstItem();
  }

  /**
   * the website to create the feed for, e.g. Main Website
   * @return Mage_Core_Model_Website
   */
  protected function _checkSite() {
    if ($this->getRequest()->getParam('site')) {
      $website = $this->_getWebsiteByName($this->getRequest()->getParam('site'));
    }

    if (!isset($website) || !$website->hasData("website_id")) {
      return null;
    }
    return $website;
  }
  
  public function unlockAction() {
    $creator = Mage::getSingleton('peerius_smartrecs/feed_creator');
    if ($creator->checkToken()) {
      Mage::getModel('core/config')->saveConfig('peerius/general/creatingfeed', 0);
    }
  }

}
