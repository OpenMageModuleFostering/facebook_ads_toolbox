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
require_once __DIR__.'/../lib/fb.php';
require_once 'FacebookProductFeed.php';

class FacebookProductFeedXML extends FacebookProductFeed {

  const XML_FEED_FILENAME = 'facebook_adstoolbox_product_feed.xml';

  const XML_HEADER = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:g="http://base.google.com/ns/1.0">
  <!-- auto generated by Facebook Marketing Solutions, v%s, time: %s -->
  <title>%s</title>
  <link rel="self" href="%s"/>
EOD;

  const XML_SHIPPINGTMP = <<<EOD
    <g:shipping>
        <g:country>%s</g:country>
        <g:service>%s</g:service>
        <g:price>%s</g:price>
    </g:shipping>
EOD;

  const XML_FOOTER = <<<EOD
</feed>
EOD;

  protected function getFileName() {
    return self::XML_FEED_FILENAME;
  }

  protected function buildHeader() {
    return sprintf(
      self::XML_HEADER,
      FacebookAdsToolbox::version(),
      date('F j, Y, g:i a'),
      Mage::app()->getStore()->getName(),
      Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
  }

  protected function buildFooter() {
    return self::XML_FOOTER;
  }

  protected function xmlescape($t) {
    return htmlspecialchars($t, ENT_XML1);
  }

  private function buildProductAttr($attr_name, $attr_value) {
    $text = $this->buildProductAttrText($attr_name, $attr_value, 'xmlescape');
    if ($text) {
      return sprintf('    <g:%s>%s</g:%s>', $attr_name, $text, $attr_name);
    } else {
      return '';
    }
  }

  protected function buildProductEntry($product) {
    $items = array();
    $items[] = "<entry>";
    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

    $items[] = $this->buildProductAttr(self::ATTR_ID, $product->getId());
    $items[] = $this->buildProductAttr(self::ATTR_TITLE,
      $product->getName());
    $items[] = $this->buildProductAttr(self::ATTR_DESCRIPTION,
      $product->getDescription());
    $items[] = $this->buildProductAttr(self::ATTR_LINK,
      FacebookAdsToolbox::getBaseUrl().
      $product->getUrlPath());
    $items[] = $this->buildProductAttr(self::ATTR_IMAGE_LINK,
      FacebookAdsToolbox::getBaseUrlMedia().
      'catalog/product'.$product->getImage());
    if ($product->hasData('brand')) {
      $items[] = $this->buildProductAttr(self::ATTR_BRAND,
        $product->getData('brand'));
    } else {
      $items[] = $this->buildProductAttr(self::ATTR_BRAND, 'original');
    }
    if ($product->hasData('condition')) {
      $items[] = $this->buildProductAttr(self::ATTR_CONDITION,
        $product->getData('condition'));
    } else {
      $items[] = $this->buildProductAttr(self::ATTR_CONDITION, 'new');
    }
    $items[] = $this->buildProductAttr(self::ATTR_AVAILABILITY,
      $stock->getData('is_in_stock') ? 'in stock' : 'out of stock');
    $items[] = $this->buildProductAttr(self::ATTR_PRICE,
      sprintf('%s %s',
        Mage::getModel('directory/currency')->format(
          $product->getFinalPrice(),
          array('display'=>Zend_Currency::NO_SYMBOL),
          false),
        Mage::app()->getStore()->getDefaultCurrencyCode()));
    if ($product->hasData('google_product_category')) {
      $items[] = $this->buildProductAttr(self::ATTR_GOOGLE_PRODUCT_CATEGORY,
        $product->getData('google_product_category'));
    }
    $items[] = $this->buildProductAttr(self::ATTR_SHORT_DESCRIPTION,
      $product->getDescription());

    $items[] = "</entry>";
    return implode("\n", array_filter($items));
  }

}
