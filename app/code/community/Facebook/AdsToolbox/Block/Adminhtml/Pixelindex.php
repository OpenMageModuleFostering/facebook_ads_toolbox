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

class Facebook_AdsToolbox_Block_Adminhtml_Pixelindex
  extends Mage_Adminhtml_Block_Template {

  public function fetchPixelId() {
    return Mage::getStoreConfig('facebook_ads_toolbox/fbpixel/id');
  }

  public function fetchBaseCurrency() {
    return Mage::app()->getStore()->getBaseCurrencyCode();
  }

  public function fetchStoreName() {
    // In order to fetch the actual website store name not the default 'Admin'
    // store, we have to do this. -StackOverflow
    $frontendName = Mage::app()->getStore()->getFrontendName();
    $defaultStoreId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
    $defaultStoreName = Mage::getModel('core/store')->load($defaultStoreId)->getGroup()->getName();
    return $frontendName . ' ; ' . $defaultStoreName;
  }



  public function fetchTimezone() {
    return $this->determineFbTimeZone(
      Mage::getStoreConfig('general/locale/timezone')
    );
  }

  public function getAjaxRoute() {
    return Mage::helper("adminhtml")
      ->getUrl("adminhtml/fbpixel/ajax");
  }

  public function getDiaSettingId() {
    return Mage::getStoreConfig('facebook_ads_toolbox/dia/setting/id');
  }

  public function determineFbTimeZone($magentoTimezone) {
    return FacebookAdsToolbox::determineFbTimeZone($magentoTimezone);
  }
}
