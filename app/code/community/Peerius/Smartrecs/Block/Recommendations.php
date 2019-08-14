<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Block_Recommendations extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {
  const DEFAULT_ITEM_NUM = 3;
  
  protected $_fixTemplates = array(
      'peerius-smartrecs-left' => 'sidebar.phtml',
      'peerius-smartrecs-right' => 'sidebar.phtml',
      'peerius-smartrecs-footer' => 'other.phtml'
      );

  protected function _toHtml() {

    $helper = Mage::helper('peerius_smartrecs');
    $headline = '';
    $showDiv = false;
    $page = null;
    
    if ($this->getElementId() and $this->getElementId() == 'peerius-smartrecs-productwidget2') {
      $page = 'product2';
    }
    
    $dontShow = false;
    if (($helper->getPageType() == 'search') AND !Mage::getStoreConfig('peerius/search/success')) {
      // show recommendations on successful search results only if the specific config flag is 'yes'
      $searchResults = Mage::registry('peerius_smartrecs_searchresults');
      $dontShow = count($searchResults) ? true : false;
    }
    
    

    if (!$helper->getPageDisabled($page) OR !$this->getElementId()) {
      // standard recs will not show when disabled per page in configuration, 
      // but widgets created with magento widget instance manager will still show
      if ($this->getElementId()) {
        $id = $this->getElementId();
      } else {
        
        $type = $this->getRectype()?:'widgetinstance';
        $id = 'peerius-smartrecs-'.$type;
        
        // automatically increase widgetinstance id number
//        $widgetCounter = Mage::registry('peerius_smartrecs');
//
//        if (isset($widgetCounter)) {
//          $counter = $widgetCounter + 1;
//          $id = 'peerius-smartrecs-widgetinstance'.$counter;
//        } else {
//          $counter = 0;
//          $id = 'peerius-smartrecs-widgetinstance';
//        }
//
//        Mage::register($id, $counter);
//        Mage::unregister('peerius_smartrecs');
//        Mage::register('peerius_smartrecs', $counter);
      }

      if ($this->getRequest()->getParam('template')) {
        $template = $this->getRequest()->getParam('template');
      } elseif($this->getWtemplate()) {
        $template = $this->getWtemplate();
      } else {
        if (array_key_exists($id, $this->_fixTemplates)) {
          $template = $this->_fixTemplates[$id];
        } else {
          if ($id == 'peerius-smartrecs-productwidget2') {
            $templateSetting = 'peerius/product2/template';
            $headlineSetting = 'peerius/product2/headline';
            $maxSetting = 'peerius/product2/max';
          } else {
            $templateSetting = 'peerius/'.$helper::getPageType(). '/template';
            $headlineSetting = 'peerius/'.$helper::getPageType(). '/headline';
            $maxSetting = 'peerius/'.$helper::getPageType(). '/max';
          }
          $template = Mage::getStoreConfig($templateSetting);
          $headline = Mage::getStoreConfig($headlineSetting);
          $max = Mage::getStoreConfig($maxSetting);
          $template = $template?:Mage::getStoreConfig('peerius/'.$helper::getPageType(). '/template');
        }
      }
      
      $template = $template?:'other.phtml';

      if ($template) {
        $template = (strpos($template, DS) === false) ? 'smartrecs/recs/'.$template : $template;
		
        if ($this->getRequest()->getParam('peeriuslocalpreview') AND !array_key_exists($id, $this->_fixTemplates)) {        	
          return Mage::getSingleton('core/layout')
                          ->createBlock('smartrecs/template')
                          ->setTemplate($template)
                          ->toHtml();
        } else {
          $html = '';

          // store height of recommendations div
//          $configHeight = Mage::getStoreConfig('peerius/general/widgetheight');
//          if ($configHeight) {
//            $configHeight = unserialize($configHeight);
//          }
          //$page = $helper::getPageType();
//          if (isset($configHeight[$id])) {
//            $height = $configHeight[$id].'px';
//          } else {
//            //$html .= '<script type="text/javascript">var peeriusPage = \''.$page.'\';</script>';
//            $height = "'auto'";
//          }
//          
//          // widget type
//          $type = $this->getRectype()?:'widgetinstance';

//          if ($type == 'standard') {
//            $type = $helper::getPageType();
//          }

          $headline = $this->getHeadline()?:$headline;
          if (!$max) {
            $max = (int) $this->getMax()?:self::DEFAULT_ITEM_NUM;
          }
          
          if (!$dontShow) $html .= '<div id="'.$id.'" template="'.$template.'" headline="'.$headline.'" max="'.$max.'" class="block block-recommendations"></div>';
          return $html;
        }
      }
    }
  }
  
  

    /**
     * Return the id of the element. If none is defined in the layout xml,
     * then set a default one.
     *
     * @return string
     */
  public function getElementId() {
    $id = $this->getDivId();
    return $id;
  }

}

