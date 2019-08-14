<?php

/**
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Block_Tracking extends Mage_Core_Block_Template {

  protected $_pageType;
  protected $_debugMode;
  /*
   * getter for page type
   */
  public function getPageType() {
    return $this->_pageType;
  }
  
  /*
   * guess the page type and call the method to generate the pixel
   * @return string
   */
  public function getTrackingPixel() {
    $fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
	$debuMode = $this->setDebugMode();
    switch ($fullActionName) {
      case 'cms_index_index':
        $routeName = Mage::app()->getRequest()->getRouteName(); 
        $identifier = Mage::getSingleton('cms/page')->getIdentifier();
        if($routeName == 'cms' && $identifier == 'home') {
          $this->_pageType = 'home';
          return $this->getHomePixel();
        } else {
          return $this->getOtherPixel();
        }
        break;
      case 'catalog_category_view':
        $this->_pageType = 'category';
        return $this->getCategoryPixel();
      case 'catalog_product_view':
        $this->_pageType = 'product';
        return $this->getProductPixel();
      case 'checkout_cart_index':
        $this->_pageType = 'basket';
        return $this->getBasketPixel();
      case 'checkout_onepage_index':
        $this->_pageType = 'checkout';
        return $this->getCheckoutPixel();
      case 'checkout_onepage_success':
        $this->_pageType = 'order';
        return $this->getOrderPixel();
      default:
        if (0 === strpos($fullActionName, 'catalogsearch_')) {
          $this->_pageType = 'search';
          return $this->getSearchResultsPixel();
        }
        if (0 === strpos($fullActionName, 'wishlist_')) {
          $this->_pageType = 'wishlist';
          return $this->getWishlistPixel();
        }
        $this->_pageType = 'other';
        return $this->getOtherPixel();
    }
  }
  
  protected function _getLang() {
    return substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);
  }
  
  /**
   * make the pixel for the home page
   * 
   * @return string
   */
  public function getHomePixel() {
    $pixel = array(
      'type' => 'home',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    return json_encode($pixel);
  }
  
  /**
   * make the pixel for the product page
   * 
   * @return string
   */
  public function getProductPixel() {
    $pixel = array(
      'type' => 'product',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $product = Mage::registry('current_product');
    if ($product AND $product->getId()) {
      $pixel['product'] = array(
          'refCode' => $product->getSku());
    }
    return json_encode($pixel);
  }
  
  /**
   * make the pixel for the category page
   * 
   * @return string
   */
  public function getCategoryPixel() {
    $pixel = array(
      'type' => 'category',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $category = $this->escapeHtml(Mage::registry('current_category'));
    if ($category AND $category->getId()) {
      $pixel['category'] = $this->_getCategoryBreadcrumb($category->getId());
    }

    return json_encode($pixel);
  }
  
  
  /**
   * make the pixel for the basket page
   * 
   * @return string
   */
  public function getBasketPixel() {
    $pixel = array(
      'type' => 'basket',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $cart = Mage::getModel('checkout/cart')->getQuote();
    $items = $cart->getAllItems();
    $pixel['basket']['items'] = array();
    foreach ($items as $item) {
      //code pre v 1.0.8
	  //if ($item->getParentItem())	continue;
    
      //if ($item->getProduct()->getData('visibility')==1) {
	  	//Mage::log($item->getParentItem()->getProduct()->getData('sku'), null, 'peerius_smartrec.log');
	    //continue;
      //}
      
      // ignore skus that are not visible as product pages 
      if(!$item->getProduct()->isVisibleInSiteVisibility()) continue;
	  $dL = $this->_dLog('BASKET TRACKING: '.$item->getProduct()->getData('sku').' is '.strtoupper($item->getProductType()).' & '.($item->getProduct()->isVisibleInSiteVisibility()? "VISIBLE": "NOT VISIBLE"));
	  
	  $priceIncTax = Mage::helper('tax')->getPrice($item, $item->getPrice(), true);
      $newItem = array(
          'refCode' => $item->getProduct()->getData('sku'), 
          'qty'     => $item->getQty(),
          'price'   => $priceIncTax
      );
      $pixel['basket']['items'][] = $newItem;
    }
    $pixel['basket']['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
    return json_encode($pixel);
  }
  
  /**
   * makes the pixel for the checkout page
   * 
   * @return string
   */
  public function getCheckoutPixel() {
    $pixel = array(
      'type' => 'checkout',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $cart = Mage::getModel('checkout/cart')->getQuote();
    $items = $cart->getAllItems();
    foreach ($items as $item) {
      //code pre v 1.0.8
	  //if ($item->getParentItem())	continue;
	  
	  // ignore skus that are not visible as product pages 
	  if(!$item->getProduct()->isVisibleInSiteVisibility()) continue;
	  $dL = $this->_dLog('CHECKOUT TRACKING: '.$item->getProduct()->getData('sku').' is '.strtoupper($item->getProductType()).' & '.($item->getProduct()->isVisibleInSiteVisibility()? "VISIBLE": "NOT VISIBLE"));

	  $priceIncTax = Mage::helper('tax')->getPrice($item, $item->getPrice(), true);
      $newItem = array(
          'refCode' => $item->getProduct()->getData('sku'),
          'qty'     => (int) $item->getQty(),
          'price'   => $priceIncTax
      );
      $pixel['checkout']['items'][] = $newItem;
    }
    $pixel['checkout']['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
//    $totals = $cart->getTotals();
    if ($cart->getSubtotal()) {
      $pixel['checkout']['subtotal'] = $cart->getSubtotal();
    }
    if ($cart->getShippingAmount()) {
      $pixel['checkout']['shipping'] = $cart->getShippingAmount();
    }
    $pixel['checkout']['total'] = $cart->getGrandTotal();
    return json_encode($pixel);
  }
    

    /**
   * makes the order confirmation pixel
   *
   * @return string
   */
  public function getOrderPixel() {
    $pixel = array(
      'type' => 'order',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
    $order = Mage::getModel('sales/order')->load($orderId);
    $pixel['order']['orderNo'] = $order->getIncrementId();
    $items = $order->getAllItems();
    foreach ($items as $item) {
      //code pre v 1.0.8
	  //if ($item->getParentItem())	continue;
	  
	  // ignore skus that are not visible as product pages 
	  if(!$item->getProduct()->isVisibleInSiteVisibility()) continue;
	  $dL = $this->_dLog('ORDER TRACKING: '.$item->getProduct()->getData('sku').' is '.strtoupper($item->getProductType()).' & '.($item->getProduct()->isVisibleInSiteVisibility()? "VISIBLE": "NOT VISIBLE"));

	  $priceIncTax = Mage::helper('tax')->getPrice($item, $item->getPrice(), true);
      $newItem = array(
          'refCode' => $item->getProduct()->getData('sku'), 
          'qty'     => (int) $item->getQtyOrdered(),
          'price'   => $priceIncTax
      );
      $pixel['order']['items'][] = $newItem;
    }
    $pixel['order']['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
//    $totals = $order->getTotals();
    if ($order->getSubtotal()) {
      $pixel['order']['subtotal'] = $order->getSubtotal();
    }
    if ($order->getShippingAmount()) {
      $pixel['order']['shipping'] = $order->getShippingAmount();
    }
    $pixel['order']['total'] = $order->getGrandTotal();
    return json_encode($pixel);
  }
  
  /**
   * makes the pixel for the search page
   * 
   * @return string
   */
  public function getSearchResultsPixel() {
    $pixel = array(
      'type' => 'searchresults',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $term = Mage::helper('catalogsearch')->getQueryText();
    $pixel['searchResults']['term'] = $this->escapeHtml($term);
    $searchResult = array_slice(Mage::registry('peerius_smartrecs_searchresults'), 0, 10);
    $dL = $this->_dLog('SEARCH TRACKING: '.print_r($searchResult,1));

    $pixel['searchResults']['results'] = $searchResult;
    return json_encode($pixel);
  }
  
  /**
   * makes the pixel for the wishlist page
   * 
   * @return string
   */
  public function getWishlistPixel() {
    $pixel = array(
      'type' => 'wishlist',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    $collection = Mage::helper('wishlist')->getWishlistItemCollection();
    $pixel['wishList']['items'] = array();
    foreach ($collection as $item) {
      $product = $item->getProduct();
      $newProduct = array('refCode' => $product->getSku());
      $pixel['wishList']['items'][] = $newProduct;
    }
    return json_encode($pixel);
  }
  
  /**
   * makes the pixel for other pages
   * 
   * @return string
   */
  public function getOtherPixel() {
    $pixel = array(
      'type' => 'other',
      'lang' => $this->_getLang(),
      'recContent' => 'refCodeOnly'
    );
    $user = $this->_getUser();
    if ($user) {
      $pixel['user'] = $user;
    }
    return json_encode($pixel);
  }
    

  /**
   * generate breadcrumb style category
   * @param array $currentCatId
   * @return string
   */
  protected function _getCategoryBreadcrumb($currentCatId) {

    if ($currentCatId) {
      $categoryCollection = Mage::getResourceModel('catalog/category_collection')
              ->addAttributeToSelect('path')
              ->addAttributeToFilter('entity_id', $currentCatId)
              ->addIsActiveFilter();

      foreach ($categoryCollection as $cat) {
        $p = explode('/', $cat->getPath());

        $fullPath = array();
        foreach ($p as $treeCat) {
          if ($treeCat <= 2)
            continue;
          $category = Mage::getModel('catalog/category')
                  ->setStoreId(Mage::app()->getStore()->getId())
                  ->load($treeCat);
          $fullPath[] = $category->getName();
        }
        return implode('>', $fullPath);
      }
    }
    return false;
  }
  
  /*
   * if available the user name and email is returned
   * @return array
   * 
   */
  protected function _getUser() {
    $session = Mage::getSingleton('customer/session');
    $user = array('email'=>'');
    if($session->isLoggedIn()) {
       $customer = $session->getCustomer();
       //$user['name'] = $customer->getName();
       $user['email'] = $customer->getEmail();
       $user['name'] = $customer->getEmail();
    } else {
      $checkout = Mage::getSingleton('checkout/session')->getQuote();
      $billAddress = $checkout->getBillingAddress();
      if ($billAddress->getEmail()) {
        $user['email'] = $billAddress->getEmail();
        //$user['name'] = $billAddress->getFirstname() . ' ' . $billAddress->getLastname();
        $user['name'] = $billAddress->getEmail();
      }
    }
    if (!$user['email']) {
      $lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId() ?:
                     Mage::getSingleton('checkout/session')->getRealOrderId();
      $order = Mage::getModel('sales/order')->load($lastOrderId);
      
      if ($order->getBillingAddress()) {
        $custname = $order->getBillingAddress()->getName();
        $email = $order->getBillingAddress()->getEmail();
        $user['email'] = $email;
        //$user['name'] = $custname;
        $user['name'] = $email;
      }
    }
    return $user['email']?$user:false;
  }
  
  /**
   * return the peerius tracking tag depending on mode (test or live) 
   * 
   * @return string html tracking tag
   */
  public function getTrackingTag() {
    if (Mage::helper('peerius_smartrecs')->isTestMode()) {
      return '<script type="text/JavaScript" src="//uat.peerius.com/tracker/peerius.page" charset="UTF-8"></script>';
    }
    return '<script type="text/JavaScript" src="//'.$this->escapeHtml(Mage::getStoreConfig('peerius/general/client_name')).'.peerius.com/tracker/peerius.page" charset="UTF-8"></script>';
  }
  
  private function setDebugMode()
  {
	$this->_debugMode = false;
	if(Mage::app()->getRequest()->getParam('pbug') == 1 && Mage::getSingleton('core/session')->getPeeriusDebug()== null)
	{
		Mage::getSingleton('core/session')->setPeeriusDebug(true); 
	} 
	$this->_debugMode = Mage::getSingleton('core/session')->getPeeriusDebug() != null ? true : false;	

	if(Mage::app()->getRequest()->getParam('pbug') != null && Mage::app()->getRequest()->getParam('pbug') == 0 && Mage::getSingleton('core/session')->getPeeriusDebug()!= null)
	{
		Mage::getSingleton('core/session')->unsPeeriusDebug(); 
		$this->_debugMode = false;
	}
	if(Mage::app()->getRequest()->getParam('pbug') != null)
		Mage::log('PEERIUS DEBUG '.( Mage::app()->getRequest()->getParam('pbug')==1 ? 'EN' : 'DIS').'ABLED', null, 'peerius_smartrec.log');
	return $this->_debugMode;
  }
  
  private function _dLog($msg)
  {

  	if($this->_debugMode == true) Mage::log($msg, null, 'peerius_smartrec.log');
  	return true;
  }

}