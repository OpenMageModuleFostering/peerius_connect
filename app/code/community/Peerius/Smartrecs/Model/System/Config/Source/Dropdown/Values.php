<?php

class Peerius_Smartrecs_Model_System_Config_Source_Dropdown_Values {

  public function toOptionArray() {
    for($i = 0;$i <= 18;$i++) {
      $arr[] = array(
      'value' => $i,
      'label' => str_pad($i, 2, '0', STR_PAD_LEFT).':00'
      );
    }
    return $arr;
  }

}