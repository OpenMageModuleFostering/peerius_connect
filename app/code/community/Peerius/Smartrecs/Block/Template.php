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

  protected function _prepareData() {
    $this->_productCollection = Mage::getModel('catalog/product')
        ->getCollection()
        ->addAttributeToSelect('*');
    
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
    }
    
    if ($skus) {
      $this->_productCollection->addAttributeToFilter('SKU', array('in'=>$skus));
      $this->_productCollection->getSelect()->order("find_in_set(SKU,'".implode(',',$skus)."')");
    }

    
    if (!$max) { 
      $max = (int) $this->getRequest()->getParam('max') ?: self::DEFAULT_ITEM_NUM;
    }
    
    if ($max > self::MAX_ITEM_NUM) {
      $max = self::MAX_ITEM_NUM;
    }
    
    $this->_productCollection->getSelect()->limit($max);

    $this->_productCollection->load();

    return $this;
  }

  protected function _beforeToHtml() {
    $this->_prepareData();
    parent::_beforeToHtml();
  }
  
  public function getItems() {
    return $this->_productCollection;
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
}

