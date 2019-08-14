<?php

/**
 * admin configuration controller
 * @category Peerius
 * @package Peerius_Smartrecs
 * @author Peerius Ltd
 */
class Peerius_Smartrecs_Adminhtml_PeeriusController extends Mage_Adminhtml_Controller_Action {

  public $_publicActions = array('config');

  const EMAIL_TRIES = 5;

  public function configAction() {
    $token = Mage::getStoreConfig('peerius/general/token');

    if (!$token) {
      $session = Mage::getSingleton('admin/session');
      $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
      //Mage::getSingleton('adminhtml/session')->addSuccess($this->__('ACL reloaded'));
    }
    $this->_redirect("adminhtml/system_config/edit", array("section" => "peerius"));
  }

  public function registerAction() {
    $registrationStatus = Mage::getStoreConfig('peerius/general/registrationstatus');
    $registrationError = Mage::getStoreConfig('peerius/general/registrationerror');
    $body = "Hi there, 
        
a new client (" . Mage::app()->getRequest()->getParam('email') . ") wants to register for SMART-recs.

Shop-URL:
" . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "
        
Name of the Store: 
" . Mage::getStoreConfig('general/store_information/name') . "

Phone Number: 
" . Mage::getStoreConfig('general/store_information/phone') . "
  
Address: 
" . Mage::getStoreConfig('general/store_information/address') . "

The message: 
" . Mage::app()->getRequest()->getParam('message');
    $mail = Mage::getModel('core/email');
    $mail->setToName('Admin');
    $mail->setToEmail('extensions@peerius.com');
    $mail->setBody($body);
    $mail->setSubject('New Magento Connect Registration');
    $mail->setFromEmail(Mage::app()->getRequest()->getParam('email'));
    $mail->setFromName(Mage::app()->getRequest()->getParam('email'));
    $mail->setType('text'); // You can use 'html' or 'text'

    try {
      $mail->send();
      $this->_getSession()->addNotice('Thank you for registering your Peerius SMART-recs extension. The Peerius Extensions Team will get back to you within the next 24-48 hours with information you require to progress with your setup.');
//          Mage::getSingleton('core/session')->addSuccess('Your request has been sent');
//          $this->_redirect('');
      Mage::getModel('core/config')->saveConfig('peerius/general/registrationstatus', 1);
    } catch (Exception $e) {
      Mage::getModel('core/config')->saveConfig('peerius/general/registrationerror', ++$registrationError);
      if ($registrationError <= self::EMAIL_TRIES) {
        $message = 'Unable to send.';
      } else {
        $message = 'Unfortunately, there is some problem with sending an email to Peerius. Please contact 
the <a href="mailto:extensions@peerius.com">Peerius Extensions Team</a> about this issue 
and they will contact you to help you complete the registration process.';
      }
      Mage::getSingleton('core/session')->addError($message);
      $this->_redirect('');
    }

    $this->_redirect("adminhtml/system_config/edit", array("section" => "peerius", "register" => "done"));
  }

  public function aclreloadAction() {
    $session = Mage::getSingleton('admin/session');
    $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('ACL reloaded'));
    $this->_redirectReferer();
  }

  protected function _isAllowed()
  {
      return true;
  }

}