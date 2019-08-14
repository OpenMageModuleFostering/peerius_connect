<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Model_SearchLayer extends Mage_CatalogSearch_Model_Layer {

  /**
   * get the found product for the tracking
   * @return $this
   */
  public function prepareProductCollection($collection) {
    parent::prepareProductCollection($collection);
    $sku = array();
    $coll = clone $collection;
    foreach ($coll as $item) {
      $data = $item->getData();
      $sku[] = array('refCode' => $data['sku']);
    }
    Mage::register('peerius_smartrecs_searchresults', $sku);
    return $this;
  }
}