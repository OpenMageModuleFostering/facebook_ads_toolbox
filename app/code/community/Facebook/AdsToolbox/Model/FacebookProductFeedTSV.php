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

class FacebookProductFeedTSV extends FacebookProductFeed {

  const TSV_FEED_FILENAME = 'facebook_adstoolbox_product_feed.tsv';

// full row should be
// id\ttitle\tdescription\tgoogle_product_category\tproduct_type\tlink\timage_link\tbrand\tcondition\tavailability\tprice\tsale_price\tsale_price_effective_date\tgtin\tbrand\tmpn\titem_group_id\tgender\tage_group\tcolor\tsize\tshipping\tshipping_weight\tcustom_label_0
// ref: https://developers.facebook.com/docs/marketing-api/dynamic-product-ads/product-catalog
  const TSV_HEADER = "id\ttitle\tdescription\tlink\timage_link\tbrand\tcondition\tavailability\tprice\tshort_description";

  protected function tsvescape($t) {
    // replace newlines as TSV does not allow multi-line value
    return str_replace(array("\r", "\n", "&nbsp;", "\t"), ' ', $t);
  }

  protected function buildProductAttr($attr_name, $attr_value) {
    return $this->buildProductAttrText($attr_name, $attr_value, 'tsvescape');
  }

  protected function getFileName() {
    return self::TSV_FEED_FILENAME;
  }

  protected function buildHeader() {
    // shame that we can not insert any comments in TSV
    return self::TSV_HEADER;
  }

  protected function buildFooter() {
    return null;
  }

  protected function isValidCondition($condition) {
    return ($condition &&
              ( $condition === 'new' ||
                $condition === 'used' ||
                $condition === 'refurbished')
           );
  }

  protected function buildProductEntry($product) {
    $items = array();
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
    if ($product->getData('brand')) {
      $items[] = $this->buildProductAttr(self::ATTR_BRAND,
        $product->getAttributeText('brand'));
    } else if ($product->getData('manufacturer')) {
      $items[] = $this->buildProductAttr(self::ATTR_BRAND,
        $product->getAttributeText('manufacturer'));
    } else {
      $items[] = $this->buildProductAttr(self::ATTR_BRAND, 'original');
    }
    if ($product->getData('condition')
        && $this->isValidCondition($product->getAttributeText('condition'))) {
      $items[] = $this->buildProductAttr(
        self::ATTR_CONDITION,
        $product->getAttributeText('condition')
      );
    } else {
      $items[] = $this->buildProductAttr('condition', 'new');
    }
    $items[] = $this->buildProductAttr('availability',
      $stock->getData('is_in_stock') ? 'in stock' : 'out of stock');
    $items[] = $this->buildProductAttr('price',
      sprintf('%s %s',
        Mage::getModel('directory/currency')->format(
          $product->getFinalPrice(),
          array('display'=>Zend_Currency::NO_SYMBOL),
          false),
        Mage::app()->getStore()->getDefaultCurrencyCode()));

    $items[] = $this->buildProductAttr(self::ATTR_SHORT_DESCRIPTION,
      $product->getShortDescription());

    // TODO : Enable by adding 'google product category' in the header.
    // if ($product->getData('google_product_category')) {
    //  $items[] = $this->buildProductAttr(self::ATTR_GOOGLE_PRODUCT_CATEGORY,
    //    $product->getData('google_product_category'));
    // }
    return implode("\t", $items);
  }

}
