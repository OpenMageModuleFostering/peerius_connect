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
    $cnt = 1;
    foreach ($coll as $item) {
      $data = $item->getData();
      $sku[] = array('refCode' => $data['sku']);
      if($cnt++==1) break;
    }
    Mage::unregister('peerius_smartrecs_searchresults');
    if(Mage::registry('peerius_smartrecs_searchresults') == null) Mage::register('peerius_smartrecs_searchresults', $sku);
    //Mage::log('==========================SEARCH DEBUG=========================', null, 'peerius_smartrec.log');
    //Mage::log(print_r(Mage::registry('peerius_smartrecs_searchresults'), 1), null, 'peerius_smartrec.log');
    //Mage::log('==========================SEARCH DEBUG END=========================', null, 'peerius_smartrec.log');
    //$c = Mage::registry('peerius_smartrecs_searchresults');
    //foreach ($c as $itm => $value) {
    //	foreach ($value as $vi => $vv) {
	//      Mage::log('peerius_smartrecs_searchresults registry value is '.$vi.'::'.$vv);
	//      }
    //}
    return $this;
  }
}