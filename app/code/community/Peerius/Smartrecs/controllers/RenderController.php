<?php

/**
 * show recommendations using a specific theme
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_RenderController extends Mage_Core_Controller_Front_Action {
  protected $_pageType = '';

  /**
   */
  public function indexAction() {
    $myHtml = '';
    
    $helper = Mage::helper('peerius_smartrecs');
    //$template = $this->getRequest()->getParam('template') ?: 'catalog/product/list/related.phtml';
    $template = $this->getRequest()->getParam('template') ?: 'other.phtml';
    $template = (strpos($template, DS) === false) ? 'smartrecs/recs/'.$template : $template;
    
    $this->loadLayout();
    $myBlock = $this->getLayout()->createBlock('smartrecs/template');

    $myBlock->setTemplate($template);
    $myHtml .= $myBlock->toHtml(); //also consider $myBlock->renderView();
    
    if (strpos($template, 'smartrecs/recs/') !== 0) {
      // is not a peerius template!
      
      // do some stuff to add the proper click event to existing templates
      $title = $this->getRequest()->getParam('title') ?: $this->__('Recommended Products');
      $myHtml = str_replace($this->__('Related Products'), $title, $myHtml);
      if ($this->getRequest()->getParam('recs')) {
        $recs = json_decode($this->getRequest()->getParam('recs'));
        $num = count($recs);
        $skuArr = array();
        for ($i = 0; $i < $num; $i++) {
          $skuArr[] = $recs[$i]->refCode;
          $idArr[] = $recs[$i]->id;
          $sku2id[$recs[$i]->refCode] = $recs[$i]->id;
        }
      }
      
      $myHtml .= $myBlock->toHtml(); //also consider $myBlock->renderView();

      // replace the title
      $myHtml = str_replace($this->__('Related Products'), $title, $myHtml);


      $search = '<a\s+(?:[^>]*?\s+)?href="([^"]*)"';
      $doc = new DOMDocument();
      $doc->loadHTML($myHtml);
      $xpath = new DOMXPath($doc);
      $nodeList = $xpath->query('//a');
      for ($i = 0; $i < $nodeList->length; $i++) {
        foreach ($sku2id as $sku => $id) {
          if (strpos($nodeList->item($i)->getAttribute('href'), strtolower($sku)) !== false) {
            $nodeList->item($i)->setAttribute('onClick', 'smartRecsClick('.$id.')');
          }
        }
      }
      $myHtml .= $doc->saveXml();
      
    }
    
    $this->getResponse()
            ->setHeader('Content-Type', 'text/html')
            ->setBody($myHtml);
  }
  
  /*
   * reset the heights of all widgets
   */
  public function resetHeightAction() {
    Mage::getModel('core/config')->saveConfig('peerius/general/widgetheight', '');
    $this->getResponse()
            ->setHeader('Content-Type', 'text/html')
            ->setBody('done');
  }

}
