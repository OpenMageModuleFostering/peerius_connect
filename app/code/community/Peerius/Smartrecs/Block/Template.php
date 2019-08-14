<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Block_Template extends Mage_Catalog_Block_Product_List {

  const DEFAULT_ITEM_NUM = 3;
  
  const MAX_ITEM_NUM = 10;

  protected $_columnCount = 4;
  
  protected $_i = 0;
  
  protected $_debugMode;

  protected function _prepareData() {
  	
    $debugMode = $this->setDebugMode();
    
	$num = false;
	$skus = false;
	$max = false;

	if ($this->getRequest()->getParam('skus')) {
	  $skus = explode(',',$this->getRequest()->getParam('skus'));
	}

	if ($this->getRequest()->getParam('recs')) {
	  $recs = json_decode($this->getRequest()->getParam('recs'));
	  $num = count($recs);
	  $skus = array();
	  for ($i = 0; $i < $num; $i++) {
		$skus[] = $recs[$i]->refCode;
	  }
	  $dL = $this->_dLog('Template.php => RECS count is '.count($recs).' & SKUS count is '.count($skus));
	  $dL = $this->_dLog('Template.php ALL PARAMS => '.print_r($this->getRequest()->getParams(),1));

	}
	else {
	  $dL = $this->_dLog('Template.php => $this->getRequest()->getParam(recs) is empty');
	  $dL = $this->_dLog('Template.php ALL PARAMS =>'.print_r($this->getRequest()->getParams(),1));
	}

	 $max = !$max ? (int) $this->getRequest()->getParam('max') : self::DEFAULT_ITEM_NUM;
	 $max = $max > self::MAX_ITEM_NUM ? self::MAX_ITEM_NUM : $max;

    if($this->isRecContentRefCodeOnly())
    {
	
		//This is a possible fix for JosephJoseph
		//Enable EAV for JosephJoseph
		$process = Mage::helper('catalog/product_flat')->getProcess();
		//get current setting
		$status = $process->getStatus();
		$process->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
		$dL = $this->_dLog('Template.php => EAV ENABLED');
		//EAV enabled

		$this->_productCollection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*');

		if ($skus) {
		  $this->_productCollection->addAttributeToFilter('SKU', array('in'=>$skus));
		  $this->_productCollection->getSelect()->order("find_in_set(SKU,'".implode(',',$skus)."')");
		}

		$this->_productCollection->getSelect()->limit($max);
		$dL = $this->_dLog($this->_productCollection->getSelect()->__toString());

		$this->_productCollection->load();

		//Reset to flat catalog
		$process->setStatus($status);
		$dL = $this->_dLog('Template.php => EAV DISABLED');
	}
    else
    {
    	$dL = $this->_dLog('Template.php => RECS RETURNED WITH FULL DETAILS');
    }
	
    return $this;
  }

  protected function _beforeToHtml() {
    $this->_prepareData();
    parent::_beforeToHtml();
  }
  
  public function getItems() {
    return $this->_productCollection ? $this->_productCollection : null;
  }

  /**
   * Count items
   *
   * @return int
   */
  public function getItemCount()
  {
      return count($this->getItems());
  }
  
  public function getItemCollection() {
    return $this->_productCollection;
  }
  
  public function isRecContentRefCodeOnly()
  {
  	return Mage::helper('peerius_smartrecs')->getRecsAsSKUsOnly()? true : false;
  }
  

  public function getRowCount()
  {
      return ceil(count($this->getItemCollection()->getItems())/$this->getColumnCount());
  }

  public function setColumnCount($columns)
  {
      if (intval($columns) > 0) {
          $this->_columnCount = intval($columns);
      }
      return $this;
  }

  public function getColumnCount()
  {
      return $this->_columnCount;
  }

  public function resetItemsIterator()
  {
      $this->getItems();
      reset($this->_productCollection);
  }

  public function getIterableItem()
  {
    if (is_array($this->_productCollection)) {
      $item = current($this->_productCollection);
      next($this->_productCollection);
      return $item;
    }
    
    // the iterator on an object doesn't work like this
    $i = 0;
    foreach ($this->_productCollection as $item) {
      if ($this->_i == $i++) {
        $this->_i++;
        return $item;
      }
    }
  }
  
  	public function getCurrencyCode()
  	{
  		//$rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
  		return Mage::app()->getStore()->getCurrentCurrencyCode();
  	}
  	
  	public function getCurrencySymbol()
  	{
  		return Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
  	}
  	
  	private function setDebugMode()
	{
		$this->_debugMode = Mage::getSingleton('core/session')->getPeeriusDebug() != null ? true : false;	
		if($this->_debugMode) Mage::log('PEERIUS DEBUG FOR RECS ENABLED', null, 'peerius_smartrec.log');
		return $this->_debugMode;
	}

	private function _dLog($msg)
	{
		if($this->_debugMode == true) Mage::log($msg, null, 'peerius_smartrec.log');
		return true;
	}

}

