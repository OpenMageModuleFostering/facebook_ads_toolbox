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
require_once 'FacebookProductFeedTSV.php';

class FacebookProductFeedSamples extends FacebookProductFeedTSV {

  protected function buildProductEntry($product) {
    $items = array();
    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

    $items[self::ATTR_ID] =
      $this->buildProductAttr(self::ATTR_ID, $product->getId());
    $items[self::ATTR_TITLE] =
      $this->buildProductAttr(self::ATTR_TITLE, $product->getName());
    $items[self::ATTR_DESCRIPTION] =
      $this->buildProductAttr(self::ATTR_DESCRIPTION,
        $product->getDescription());
    $items[self::ATTR_LINK] =
      $this->buildProductAttr(self::ATTR_LINK,
        FacebookAdsToolbox::getBaseUrl().
        $product->getUrlPath());
    $items[self::ATTR_IMAGE_LINK] =
      $this->buildProductAttr(self::ATTR_IMAGE_LINK,
        FacebookAdsToolbox::getBaseUrlMedia().
        'catalog/product'.$product->getImage());
    if ($product->getData('brand')) {
      $items[self::ATTR_BRAND] =
        $this->buildProductAttr(self::ATTR_BRAND, $product->getData('brand'));
    } else {
      $items[self::ATTR_BRAND] =
        $this->buildProductAttr(self::ATTR_BRAND, 'original');
    }
    if ($product->getData('condition')) {
      $items[self::ATTR_CONDITION] =
        $this->buildProductAttr(self::ATTR_CONDITION,
          $product->getData('condition'));
    } else {
      $items[self::ATTR_CONDITION] =
        $this->buildProductAttr(self::ATTR_CONDITION, 'new');
    }
    $items[self::ATTR_AVAILABILITY] =
      $this->buildProductAttr(self::ATTR_AVAILABILITY,
        $stock->getData('is_in_stock') ? 'in stock' : 'out of stock');
    $items[self::ATTR_PRICE] = $this->buildProductAttr(self::ATTR_PRICE,
      sprintf('%s %s',
        Mage::getModel('directory/currency')->format(
          $product->getFinalPrice(),
          array('display'=>Zend_Currency::NO_SYMBOL),
          false),
        Mage::app()->getStore()->getDefaultCurrencyCode()));
    if ($product->getData('google_product_category')) {
      $items[self::ATTR_GOOGLE_PRODUCT_CATEGORY] =
        $this->buildProductAttr(self::ATTR_GOOGLE_PRODUCT_CATEGORY,
          $product->getData('google_product_category'));
    }
    $items[self::ATTR_SHORT_DESCRIPTION] =
      $this->buildProductAttr(self::ATTR_SHORT_DESCRIPTION,
        $product->getShortDescription());

    return $items;
  }

  public function generate() {
    $MAX = 12;

    $results = array();

    $products = Mage::getModel('catalog/product')->getCollection()
      ->addAttributeToSelect('*')
      ->addAttributeToFilter('visibility',
          array(
            'neq' =>
              Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
          )
      )
      ->addAttributeToFilter('status',
          array(
            'neq' =>
              Mage_Catalog_Model_Product_Status::STATUS_DISABLED
          )
      )
      ->setPageSize($MAX)
      ->setCurPage(0);

    foreach ($products as $product) {
      $results[] = $this->buildProductEntry($product);
    }

    return $results;
  }
}
