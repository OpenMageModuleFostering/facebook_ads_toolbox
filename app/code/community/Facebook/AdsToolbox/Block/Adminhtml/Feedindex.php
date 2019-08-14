<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

require_once 'app/Mage.php';
require_once __DIR__.'/../../lib/fb.php';
require_once __DIR__.'/../../Model/FacebookProductFeed.php';

class Facebook_AdsToolbox_Block_Adminhtml_Feedindex
  extends Mage_Adminhtml_Block_Template {

  public function getBaseUrl() {
    return FacebookAdsToolbox::getBaseUrlMedia();
  }

  public function getAjaxRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/facebookadstoolboxfeed/ajax');
  }

  public function getAjaxGenerateNowRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/facebookadstoolboxfeedgeneratenow/ajax');
  }

  public function getAjaxLastRunLogsRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/facebookadstoolboxfeedlastrunlogs/ajax');
  }

  public function fetchFeedSetupEnabled() {
    $setup = FacebookProductFeed::getCurrentSetup();
    return $setup['enabled'];
  }

  public function fetchFeedSetupFormat() {
    $setup = FacebookProductFeed::getCurrentSetup();
    return $setup['format'];
  }
}
