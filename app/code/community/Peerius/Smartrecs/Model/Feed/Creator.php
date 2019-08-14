<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Model_Feed_Creator {

  protected $fileName;
  protected $secretKey;
  protected $clientName;
  protected $token;

  /**
   * method to create the feed
   * */
  public function process(Mage_Core_Model_Website $website) {
    //$this->fileName = Mage::getBaseDir('tmp') . DS . str_replace(' ', '_', $website->getName()) . "_PeeriusFeed" . ".xml";
    
    $clientName = Mage::getStoreConfig('peerius/general/client_name');
    $this->secretKey = Mage::getStoreConfig('peerius/general/secret_key');
    $this->clientName = Mage::getStoreConfig('peerius/general/client_name');
    $this->token = Mage::getStoreConfig('peerius/general/token');
    $this->fileName = $clientName . "_PeeriusFeed" . ".xml"; 

    if ($this->_createFile()) {
      if (!$this->_writeFeed($website)) {
        return false;
      }
    } else {
      return false;
    }
    return true;
  }

  /**
   * @param Mage_Core_Model_Website $website
   * @return bool
   */
  protected function _writeFeed(Mage_Core_Model_Website $website) {
	try {
		  $f = new Varien_Io_File();
		  $f->write($this->fileName, $this->_getHeader($website) . $this->_getProducts($website) . $this->_getFooter());
		  $f->close();
		  return true;
    } catch (Exception $ex) {
		  $this->log("error writing the feed");
		  $this->log($ex->getMessage());
		  return false;
    }
  }

  /**
   * @param $file
   * @return bool
   */
  protected function _createFile() {
    return true;
//    try {
//      $f = new Varien_Io_File();
//      $validator = new Zend_Validate_File_Exists();
//
//      if (!$validator->isValid($this->fileName)) {
//        $this->log("could not create the smartrec file");
//        return false;
//      }
//      $this->log("SMART-recs file created");
//
//      fclose($f);
//      return true;
//    } catch (Exception $ex) {
//      $this->log("error during file creation");
//      $this->log($ex->getMessage());
//      return false;
//    }
  }

  /**
   * generate the rss file header
   * @param Mage_Core_Model_Website $website
   * @return string
   */
  protected function _getHeader(Mage_Core_Model_Website $website) {
    $clientName = $this->_getClientName();
    $rss = '';
    $rss .= '<?xml version="1.0" encoding="UTF-8"?>';
    $rss .= '<rss version="2.0" xmlns:p="http://www.peerius.com/feeds/PeeriusRSS20.xsd">';
    $rss .= '<channel>';
    $rss .= '<title><![CDATA[Rss 2.0 ' . $clientName . ' product catalogue feed]]></title>';
    $rss .= '<link>' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . '</link>';
    $rss .= '<description><![CDATA[The latest product catalogue feed of ' . $clientName . '.]]></description>';
    return $rss;
  }

  /**
   * generate the rss file footer
   * @return string
   */
  protected function _getFooter() {
    $rss = '';
    $rss .= '</channel>';
    $rss .= '</rss>';
    return $rss;
  }

  /**
   * sets the layout
   * @param string $packageName
   * @param string $themeName
   */
  protected function _changeTheme($packageName, $themeName) {
    Mage::getDesign()->setArea('frontend')
            ->setPackageName($packageName)
            ->setTheme($themeName);
  }

  /**
   * write the products
   * @param Mage_Core_Model_Website $website
   * @return string
   */
  protected function _getProducts(Mage_Core_Model_Website $website) {
    $profilingStart = microtime(true);
    $adapter = Mage::getSingleton("core/resource");
    $visiblityCondition = array('in' => array(2, 3, 4));
    $_catalogInventoryTable = method_exists($adapter, 'getTableName') ? $adapter->getTableName('cataloginventory_stock_item') : 'catalog_category_product_index';

    //###################### DEBUG CODE :MG #####################
	$debugMode = Mage::app()->getRequest()->getParam('debug') == 1 ? true : false;
	$debugProducts = Mage::app()->getRequest()->getParam('items') ? Mage::app()->getRequest()->getParam('items') : 10;
	$this->log('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
	$this->log('DEBUG MODE : '.($debugMode == true ? 'enabled. Creating feed for '. $debugProducts. ' products.' :'disabled'));
	
	// Read Attributes to exclude from query string of feed url - Quick hack as some attribute names may cause issues.
	$attribsToExclude = Mage::app()->getRequest()->getParam('attribsToExclude') ? Mage::app()->getRequest()->getParam('attribsToExclude') : "" ; 
	if($attribsToExclude && $attribsToExclude!="") $this->log('Attributes to exclude: '.$attribsToExclude, null, 'peerius_smartrec.log');	
	//###################### DEBUG CODE :MG #####################

    $this->log('Feed creation STARTED');
    $this->_changeTheme(Mage::getStoreConfig('design/package/name', $website->getDefaultStore()->getCode()), Mage::getStoreConfig('design/package/theme', $website->getDefaultStore()->getCode()));

    $rss = '';

    $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addWebsiteFilter($website->getWebsiteId())
            ->joinField("qty", $_catalogInventoryTable, 'qty', 'product_id=entity_id', null, 'left')
            ->addAttributeToSelect('*')
            ->addCategoryIds()
            //->addAttributeToFilter('type_id', array('eq' => 'configurable'))
            ->addAttributeToFilter('visibility', $visiblityCondition)
//          ->joinTable('catalog/product_relation', 'child_id=entity_id', array('parent_id' => 'parent_id'), null, 'left')
            ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $website->getWebsiteId());
//      	->load();

    $productsCount = $productCollection->getSize();
    $this->log('Total products found : ' . $productsCount);
    
	$ctr = 0;
	$perc = 0;
	$ind = 1;
	
	// check if site has prices in multiple currencies - if so, store the currency conversion rates.
	$baseCode = Mage::app()->getBaseCurrencyCode();      
	$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies(); 
	$rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
	$this->log('CURRENCY RATES : '.($rates ? http_build_query($rates) : 'NO RATES - SINGLE CURRENCY'), null, 'peerius_smartrec.log');	
	
    try {
      
      foreach ($productCollection as $product) {
      	
      	//###################### DEBUG CODE :MG #####################
      	if ($debugMode && $ctr > $debugProducts) break;
      	//###################### DEBUG CODE :MG #####################

        $categoryIds = $product->getCategoryIds();
        $parentCategoryBreadcrumbs = false;
        $parent = false;

        // don't export unavailable products
        if (!$product->isAvailable()) {
          $productsCount--;
          continue;
        }
		
        // if a product is not in a category skip it also
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
          $parent = $this->_getParent($product);
          if ($parent) {
            $parentCategoryBreadcrumbs = $this->_getParentCategoryBreadcrumbs($product);
            $categoryBreadcrumbs = $parentCategoryBreadcrumbs;
          } else {
            $categoryBreadcrumbs = $this->_getCategoryBreadcrumbs($product->getCategoryIds());
          }
        }

        // is the product or it's product in any category? If not, skip it
        if (!$categoryIds AND !$parentCategoryBreadcrumbs) {
          $productsCount--;
          continue;
        }

        $rss .= '<item>';
        $rss .= '<title><![CDATA[' . $product->getName() . ']]></title>';
        $rss .= '<link>' . $product->getProductUrl() . '</link>';
        $rss .= '<guid><![CDATA[' . $product->getSku() . ']]></guid>';
        if ($product->getThumbnail())
          $rss .= '<p:imageLink>' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail() . '</p:imageLink>';

		// if site has prices in multiple currencies
		if($rates) {
			foreach ($rates as $cur => $rate) {
				$unitPrice  = number_format($product->getPrice(), 2, '.', '') * $rate;
				$salePrice  = number_format($product->getFinalPrice(), 2, '.', '') * $rate;
				$rss .= '<p:price><p:unitPrice>' . number_format($unitPrice, 2, '.', '') . '</p:unitPrice>
				<p:salePrice>' . number_format($salePrice, 2, '.', '') . '</p:salePrice>
				<p:currency>' . $cur . '</p:currency></p:price>';
			}
		}else{ // single currency site
			$rss .= '<p:price><p:unitPrice>' . number_format($product->getPrice(), 2, '.', '') . '</p:unitPrice>
					<p:salePrice>' . number_format($product->getFinalPrice(), 2, '.', '') . '</p:salePrice>
					<p:currency>' . Mage::app()->getStore($website->getDefaultStore())->getCurrentCurrencyCode() . '</p:currency></p:price>';
		}

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
          $rss .= $this->_getCategoryBreadcrumbs($categoryIds);
          $rss .= '<p:attribute name="minimal_price">' . number_format($product->getMinimalPrice(), 2, '.', '') . '</p:attribute>';

          $attrArr = Mage::getModel('catalog/product_type_configurable')->getConfigurableAttributesAsArray($product);

          $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProductCollection($product)
                  ->addAttributeToSelect('thumbnail')
                  ->addAttributeToSelect('name')
                  ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', 1);
          foreach ($attrArr as $attr) {
            $childProducts->addAttributeToSelect($attr['attribute_code']);
          }

          foreach ($childProducts as $childProduct) {
            if ($childProduct->getData('visibility') == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
              //$p = Mage::getModel('catalog/product')->load($childProduct->getId());
              $rss .= '<p:variant>';
              //$rss .= '<p:title><![CDATA[' . $childProduct->getName() . ']]></p:title>';
              foreach ($attrArr as $attr) {
                $rss .= '<p:attribute name="' . $attr['attribute_code'] . '"><![CDATA[' . $childProduct->getAttributeText($attr['attribute_code']) . ']]></p:attribute>';
              }
              $rss .= '<p:attribute name="link">' . $childProduct->getProductUrl() . '</p:attribute>';
              $rss .= '<p:attribute name="guid"><![CDATA[' . $childProduct->getSku() . ']]></p:attribute>';
              $rss .= '<p:attribute name="magento_id">' . $childProduct->getId() . '</p:attribute>';
              if ($product->getThumbnail())
                $rss .= '<p:imageLink>' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $childProduct->getThumbnail() . '</p:imageLink>';
              $stockItem = $childProduct->getStockItem();
              $stockQty = ($stockItem instanceof Mage_CatalogInventory_Model_Stock_Item) ?
                      ((int) $stockItem->getQty() > 0 ? (int) $stockItem->getQty() : 0) : 0;
              $rss .= '<p:stock>' . $stockQty . '</p:stock>';
              $rss .= '</p:variant>';
            }
          }
        } else {
          if ($parent) {
            $rss .= $parent;
            $rss .= $categoryBreadcrumbs;
          } else {
            $rss .= $categoryBreadcrumbs;
          }
        }

        $rss .= '<pubDate>' . $product->getcreated_at() . '</pubDate>';
        $rss .= '<description><![CDATA[' . $product->getShortDescription() . ']]></description>';

        if ($product->getAttribute('manufacturer') instanceof Mage_Eav_Model_Entity_Attribute_Abstract) {
          $rss .= '<p:brand><![CDATA[' . $product->getAttributeText('manufacturer') . ']]></p:brand>';
        }
        $rss .= '<p:inStock>' . $this->_getStockStatus($product) . '</p:inStock>';
        $rss .= '<p:stock>' . ((int) $product->getQty() > 0 ? (int) $product->getQty() : 0) . '</p:stock>';
        $rss .= '<p:recommend>Y</p:recommend>';
        $rss .= '<p:recommended><![CDATA[' . $this->_getRecommended($product) . ']]></p:recommended>';
        $rss .= '<p:attribute name="magento_id">' . $product->getId() . '</p:attribute>';
        $rss .= $this->_getAttributes($product,$attribsToExclude);
        $rss .= $this->_getTags($product);
        $rss .= '</item>';
        
        $iperc = round($productsCount/10) ;
        $perc = $perc == 0 ? $iperc : $perc;
        if($ctr == $perc) {
        	$this->log('Processed ' . $ind++ * 10 . '% of the products...' . $ctr);
        	$perc = $iperc * $ind;
			//Mage::throwException('Some Message');
        }
        $ctr++;
      }

      $this->log('Total entries created : ' . ($ctr - 1) );
    } catch (Exception $e) {
      //echo $this->getPrettyDebugBacktrace(); 
      $this->log("### ERROR ### : " . $e->getMessage());
    }


    if ($productsCount == 0) {
      $this->log("no products found");
      throw new Exception("### ERROR ### : NO products found");
    }

    $now = microtime(true);
    $this->log("Feed creation COMPLETED in " . date("H:i:s", ($now - $profilingStart)));
    $this->log('Memory used : ' . round(memory_get_peak_usage() / 1048576) . 'MB');
    $this->log('===================================================');
    return $rss;
  }

  /**
   * generate breadcrumb style categories
   * @param array $currentCatIds
   * @return string
   */
  protected function _getCategoryBreadcrumbs($currentCatIds) {
    $rss = '';

    if ($currentCatIds) {
      $categoryCollection = Mage::getResourceModel('catalog/category_collection')
              ->addAttributeToSelect('path')
              ->addAttributeToFilter('entity_id', $currentCatIds)
              ->addIsActiveFilter();

      foreach ($categoryCollection as $cat) {
        $p = explode('/', $cat->getPath());

        $fullPath = array();
        foreach ($p as $treeCat) {
          // Root-Kategorie 
          if ($treeCat <= 2)
            continue;
          $category = Mage::getModel('catalog/category')
                  ->setStoreId(Mage::app()->getStore()->getId())
                  ->load($treeCat);
          $fullPath[] = $category->getName();
        }
        $rss .= '<category><![CDATA[' . implode('>', $fullPath) . ']]></category>';
      }
    }
    return $rss;
  }

  /**
   * generate breadcrumb style categories
   * @param product $product
   * @return string
   */
  protected function _getParentCategoryBreadcrumbs($product) {
    $rss = '';
    $parentIds = $this->_getParent($product, true);
    $categoryIds = array();

    foreach ($parentIds as $parentId) {
      $product = Mage::getModel('catalog/product')->load($parentId);
      $categoryIds = array_merge($categoryIds, $product->getCategoryIds());
    }

    $rss = $this->_getCategoryBreadcrumbs($categoryIds);
    return $rss;
  }

  /**
   * retreive the product stock statuss
   * @param Mage_Core_Catalog_Product $product
   * @return string
   */
  protected function _getStockStatus($product) {
    $stockStatus = Mage::getModel('cataloginventory/stock_item')
            ->getCollection()
            ->addFieldToFilter('product_id', $product->getId())
            ->getFirstItem();

    return $stockStatus ? 'Y' : 'N';
  }

  /**
   * write the products
   * @param Mage_Core_Catalog_Product $product
   * @return string
   */
  protected function _getRecommended($product) {
	 $all = array();
	 $related = $product->getRelatedProductIds();
	 foreach ($related as $id) {
	   $all[$id] = Mage::getModel('catalog/product')->load($id)->getSku();
	   //$this->log("Related Product is :".$all[$id]);
	 }
	 
	 $crosssell = $product->getCrossSellProducts();
	 foreach ($crosssell as $_item) {
	   $all[$_item->getId()] = $_item->getSku();
	   //$this->log("Cross Selling Product is :".$all[$_item->getId()]);
	 }
	 $upsell = $product->getUpSellProductCollection();
	 foreach ($upsell as $_item) {
	   $all[$_item->getId()] = $_item->getSku();
	   //$this->log("Up Selling Product is :".$all[$_item->getId()]);
	 }
	 return implode(',', $all);
  }

  /**
   * generate comma-separated list of product tags
   * @param Mage_Core_Catalog_Product $product
   * @return string
   */
  protected function _getTags($product) {
    $tag_model = Mage::getModel('tag/tag');
    $tag_collection = $tag_model->getResourceCollection()
            ->addPopularity()
//            ->addStatusFilter($model->getApprovedStatus())
            ->addProductFilter($product->getId())
            ->setFlag('relation', true)
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->setActiveFilter()
            ->load();

    $mytags = $tag_collection->getItems();

    $tagArr = array();
    if (!$tag_collection->getSize()) {
      return '';
    }

    foreach ($mytags as $tag) {
      $tagArr[] = $tag->getName();
    }
    return '<p:tags>' . implode(',', $tagArr) . '</p:tags>';
  }

  /**
   * crate front-end attributes rss string
   * @param Mage_Core_Catalog_Product $product
   * @return string
   */
  protected function _getAttributes($product, $attribsToExclude) {
    $attributes = $product->getAttributes();
    $rss = '';
	$attribsToExcludeArray = explode('|',$attribsToExclude);
	foreach ($attributes as $attribute) {
      if ($attribute->getIsVisibleOnFront()) {
        $attributeCode = $attribute->getAttributeCode();
        if (in_array($attributeCode,$attribsToExcludeArray))
        	continue;
        else
        {
        	$label = $attribute->getFrontend()->getLabel($product);
  			$value = $attribute->getFrontend()->getValue($product);
			$rss .= '<p:attribute name="' . $attributeCode . '"><![CDATA[' . $value . ']]></p:attribute>';
        }
      }
    }
    return $rss;
  }

  /**
   * returns parent product ID if available
   * @param Mage_Core_Catalog_Product $product
   * @param bool return the ids
   * @return int
   */
  protected function _getParent($product, $ids = false) {
    $rss = '';
    $parentIds = false;
    if ($product->getTypeId() == "simple") {
      $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId()); // check for grouped product
      if (!$parentIds)
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId()); //check for config product
    }
    if ($ids) {
      return $parentIds;
    }
    if ($parentIds) {
      $rss = '<p:attribute name="parent_magento_id">' . implode(',', $parentIds) . '</p:attribute>';
    } else {
      return false;
    }
    return $rss;
  }

  /**
   * the client name used in the rss feed
   * @return string
   */
  protected function _getClientName() {
    return Mage::getStoreConfig('peerius/general/client_name');
  }

  /**
   * @param $content
   * @return bool
   */
  protected function _appendToFile($content) {
    try {
      $f = new Varien_Io_File();
      $validator = new Zend_Validate_File_Exists();

      if (!$validator->isValid($this->fileName)) {
        $this->log("could not create the smartrec file");
        return false;
      }
      $this->log("SMART-recs file created");

      fclose($f);
      return true;
    } catch (Exception $ex) {
      $this->log("error during file creation");
      $this->log($ex->getMessage());
      return false;
    }
    try {
      $f = new Varien_Io_File();
      $f->write($this->fileName, $content);
      //if (file_put_contents($this->fileName, $content, FILE_APPEND)) {
      return true;
    } catch (Exception $ex) {
      $this->log("error while writing to the feed file");
      $this->log($ex->getMessage());
      return false;
    }
  }

  public function checkToken() {
    $token = Mage::getStoreConfig('peerius/general/token');

    if (!$token OR !Mage::app()->getRequest()->getParam('token') OR (Mage::app()->getRequest()->getParam('token') != sha1($token))) {
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
	
	protected function getPrettyDebugBacktrace()
	{
	  $d = debug_backtrace();
	  array_shift($d);
	  $siteName = Mage::getStoreConfig('peerius/general/client_name',Mage::app()->getStore()); 
	  $response = '';
	  $response .= '<!doctype html><html lang=\'en-gb\'><head><meta charset=\'utf-8\'><title> CONFIG: '.$siteName.'</title>'.$this->getCSS().'</head><body style="font-family:Arial;">';
	  $response .= '<section><h1>Peerius Connect Debug : '. $siteName . '</h1>';
	  $c1width = strlen(count($d) + 1);
	  $c2width = 0;
	  foreach ($d as &$f) {
		  if (!isset($f['file'])) $f['file'] = '';
		  if (!isset($f['line'])) $f['line'] = '';
		  if (!isset($f['class'])) $f['class'] = '';
		  if (!isset($f['type'])) $f['type'] = '';
		  $f['file_rel'] = str_replace(BP . DS, '', $f['file']);
		  $thisLen = strlen($f['file_rel'] . ':' . $f['line']);
		  if ($c2width < $thisLen) $c2width = $thisLen;
	  }
	  foreach ($d as $i => $f) {
		  $args = '';
		  if (isset($f['args'])) {
			  $args = array();
			  foreach ($f['args'] as $arg) {
				  if (is_object($arg)) {
					  $str = get_class($arg);
				  } elseif (is_array($arg)) {
					  $str = 'Array';
				  } elseif (is_numeric($arg)) {
					  $str = $arg;
				  } else {
					  $str = "'$arg'";
				  }
				  $args[] = $str;
			  }
			  $args = implode(', ', $args);
		  }
		  $response .= "<div class='boxA'>".$i."</div><div class='boxB'>".$f['file_rel']." : <span class='lnText'>".$f['line'] . "</span></div><div class='boxC'> ". $f['class'].$f['type'].$f['function']." </div><div class='boxB'> ".$args."</div>";
	  	  
	  }
	  $response .= '</section></body></html>';
	  return $response;
	}
	
	 /**
	     * return CSS
	  */
	  protected function getCSS() {
	      return "<style type='text/css'> 
	         .secTitle {clear:both;display:block;float:left;padding:5px;margin:10px 2px 5px 0;width:100%;color:#808080;font-weight:bold; font-size:20px;}
	         .boxA { clear:left;display:block;float:left;padding:2px 2px 0 4px;margin:1px;background-color: #60b346 ; width:20px; height:22px;  }
	         .boxB { float:left;display:in-line;padding:2px 2px 0 4px;margin:1px;background-color: #A9Ae99; width:60%px; height:22px;color:#000; }
	         .boxC { float:left;display:in-line;padding:2px 0 0 4px;margin:1px;background-color: #A0b3A6; width:30%px; height:22px;color:#000; }
	         .lnText { background-color: #A9Ae99; font-weight:bold; color:#FF0; }
	      </style>";
	  }
}