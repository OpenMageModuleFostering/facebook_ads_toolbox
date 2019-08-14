<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

require_once __DIR__.'/../../lib/fb.php';
require_once __DIR__.'/../../Model/FacebookProductFeed.php';

class Facebook_AdsToolbox_Adminhtml_FacebookadstoolboxdiaController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    $this->loadLayout();
    $this->_setActiveMenu('facebook_ads_toolbox');
    $this->renderLayout();
  }

}
