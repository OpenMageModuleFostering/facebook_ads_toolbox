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

class FacebookProductFeed {

  const ATTR_ID = 'id';
  const ATTR_TITLE = 'title';
  const ATTR_DESCRIPTION = 'description';
  const ATTR_LINK = 'link';
  const ATTR_IMAGE_LINK = 'image_link';
  const ATTR_BRAND = 'brand';
  const ATTR_CONDITION = 'condition';
  const ATTR_AVAILABILITY = 'availability';
  const ATTR_PRICE = 'price';
  const ATTR_GOOGLE_PRODUCT_CATEGORY = 'google_product_category';
  const ATTR_SHORT_DESCRIPTION = 'short_description';

  const PATH_FACEBOOK_ADSTOOLBOX_FEED_GENERATION_ENABLED =
    'facebook_adstoolbox/feed/generation/enabled';
  const PATH_FACEBOOK_ADSTOOLBOX_FEED_GENERATION_FORMAT =
    'facebook_adstoolbox/feed/generation/format';

  public static function log($info) {
    Mage::log($info, Zend_Log::INFO, FacebookAdsToolbox::FEED_LOGFILE);
  }

  public static function getCurrentSetup() {
    return array(
      'format' => Mage::getStoreConfig(
        self::PATH_FACEBOOK_ADSTOOLBOX_FEED_GENERATION_FORMAT) ?: 'TSV',
      'enabled' => Mage::getStoreConfig(
        self::PATH_FACEBOOK_ADSTOOLBOX_FEED_GENERATION_ENABLED) ?: false,
    );
  }

  protected function isValidCondition($condition) {
    return ($condition &&
              ( $condition === 'new' ||
                $condition === 'used' ||
                $condition === 'refurbished')
           );
  }

  protected function defaultBrand() {
    return $this->buildProductAttr(self::ATTR_BRAND, 'original');
  }

  protected function defaultCondition() {
    return $this->buildProductAttr(self::ATTR_CONDITION, 'new');
  }

  protected function buildProductAttrText(
    $attr_name,
    $attr_value,
    $escapefn = null
  ) {
    // Facebook Product Feed attributes
    // ref: https://developers.facebook.com/docs/marketing-api/ \
    //   dynamic-product-ads/product-catalog
    switch ($attr_name) {
      case self::ATTR_ID:
      case self::ATTR_LINK:
      case self::ATTR_IMAGE_LINK:
      case self::ATTR_IMAGE_LINK:
      case self::ATTR_CONDITION:
      case self::ATTR_AVAILABILITY:
      case self::ATTR_PRICE:
        if ((bool)$attr_value) {
          $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
          return trim($attr_value);
        }
        break;
      case self::ATTR_BRAND:
        if ((bool)$attr_value) {
          $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
          $attr_value = trim($attr_value);
          // brand max size: 70
          if (strlen($attr_value) > 70) {
            $attr_value = substr($attr_value, 0, 70);
          }
          return $attr_value;
        }
        break;
      case self::ATTR_TITLE:
        if ((bool)$attr_value) {
          $attr_value = trim($this->htmlDecode($attr_value));
          // title max size: 100
          if (strlen($attr_value) > 100) {
            $attr_value = substr($attr_value, 0, 100);
          }
          return $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        }
        break;
      case self::ATTR_DESCRIPTION:
        if ((bool)$attr_value) {
          $attr_value = trim($this->htmlDecode($attr_value));
          // description max size: 5000
          if (strlen($attr_value) > 5000) {
            $attr_value = substr($attr_value, 0, 5000);
          }
          return $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        }
        break;
      case self::ATTR_GOOGLE_PRODUCT_CATEGORY:
        // google_product_category max size: 250
        if ((bool)$attr_value) {
          if (strlen($attr_value) > 250) {
            $attr_value = substr($attr_value, 0, 250);
          }
          return $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        }
        break;
      case self::ATTR_SHORT_DESCRIPTION:
        if ((bool)$attr_value) {
          $attr_value = trim($this->htmlDecode($attr_value));
          // max size: 1000
          // and replacing the last 3 characters with '...' if it's too long
          $attr_value = strlen($attr_value) >= 1000 ?
            substr($attr_value, 0, 995).'...' :
            $attr_value;
          return $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        }
        break;
    }
    return '';
  }

  protected function getFileName() {
    return '';
  }

  protected function buildHeader() {
    return '';
  }

  protected function buildFooter() {
    return '';
  }

  protected function buildProductAttr($attribute, $value) {
    return $this->buildProductAttrText($attribute, $value);
  }

  protected function buildProductEntry($product) {
    $items = array();
    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    $title = $product->getName();

    $items[self::ATTR_ID] = $this->buildProductAttr(self::ATTR_ID, $product->getId());
    $items[self::ATTR_TITLE] = $this->buildProductAttr(self::ATTR_TITLE, $title);

    // 'Description' is required by default but can be made
    // optional through the magento admin panel.
    // Try using the short description and title if it doesn't exist.
    $description = $this->buildProductAttr(
      self::ATTR_DESCRIPTION,
      $product->getDescription()
    );
    if (!$description) {
      $description = $this->buildProductAttr(
        self::ATTR_DESCRIPTION,
        $product->getShortDescription()
      );
    }
    $items[self::ATTR_DESCRIPTION] = ($description) ? $description : $items[self::ATTR_TITLE];

    $items[self::ATTR_LINK] = $this->buildProductAttr(self::ATTR_LINK,
      FacebookAdsToolbox::getBaseUrl().
      $product->getUrlPath());
    $items[self::ATTR_IMAGE_LINK] = $this->buildProductAttr(self::ATTR_IMAGE_LINK,
      FacebookAdsToolbox::getBaseUrlMedia().
      'catalog/product'.$product->getImage());

    $brand = null;
    if ($product->getData('brand')) {
      $brand = $this->buildProductAttr(self::ATTR_BRAND, $product->getAttributeText('brand'));
    }
    if (!$brand && $product->getData('manufacturer')) {
      $brand = $this->buildProductAttr(self::ATTR_BRAND, $product->getAttributeText('manufacturer'));
    }
    $items[self::ATTR_BRAND] = ($brand) ? $brand : $this->defaultBrand();

    $condition = null;
    if ($product->getData('condition')) {
      $condition = $this->buildProductAttr(self::ATTR_CONDITION, $product->getAttributeText('condition'));
    }
    $items[self::ATTR_CONDITION] = ($this->isValidCondition($condition)) ? $condition : $this->defaultCondition();

    $items[self::ATTR_AVAILABILITY] = $this->buildProductAttr(self::ATTR_AVAILABILITY,
      $stock->getData('is_in_stock') ? 'in stock' : 'out of stock');
    $items[self::ATTR_PRICE] = $this->buildProductAttr('price',
      sprintf('%s %s',
        Mage::getModel('directory/currency')->format(
          $product->getFinalPrice(),
          array('display'=>Zend_Currency::NO_SYMBOL),
          false),
        Mage::app()->getStore()->getDefaultCurrencyCode()));

    $items[self::ATTR_SHORT_DESCRIPTION] = $this->buildProductAttr(self::ATTR_SHORT_DESCRIPTION,
      $product->getShortDescription());
    return $items;
  }

  protected function htmlDecode($attr_value) {
    return strip_tags(html_entity_decode(($attr_value)));
  }

  public function save() {
    $io = new Varien_Io_File();
    $feed_file_path =
      Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/';

    $io->open(array('path' => $feed_file_path));
    if ($io->fileExists($feed_file_path) &&
        !$io->isWriteable($feed_file_path)) {
      Mage::throwException(Mage::helper('Facebook_AdsToolbox')->__(
        'File "%s" cannot be saved. Please make sure the path "%s" is '.
        'writable by web server.',
        $feed_file_path));
    }

    $io->streamOpen($this->getFileName());
    self::log('going to generate file:'.$this->getFileName());

    $io->streamWrite($this->buildHeader()."\n");

    $collection = Mage::getModel('catalog/product')->getCollection();
    $total_number_of_products = $collection->getSize();
    unset($collection);

    $this->writeProducts($io, $total_number_of_products, true);

    $footer = $this->buildFooter();
    if ($footer) {
      $io->streamWrite($footer."\n");
    }
  }

  private function writeProducts($io, $total_number_of_products, $should_log) {
    $count = 0;
    $batch_max = 100;
    while ($count < $total_number_of_products) {
      if ($should_log) {
       self::log(
        sprintf(
          "scanning products [%d -> %d)...\n",
          $count,
          ($count + $batch_max) >= $total_number_of_products ?
            $total_number_of_products :
            ($count + $batch_max)));
      }
      $products = Mage::getModel('catalog/product')->getCollection()
        ->addAttributeToSelect('*')
        ->setPageSize($batch_max)
        ->setCurPage($count / $batch_max + 1);

      foreach ($products as $product) {
        if ($product->getVisibility() !=
              Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE &&
            $product->getStatus() !=
              Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
          $e = $this->buildProductEntry($product);
          $io->streamWrite($e."\n");
        }
      }
      unset($products);
      $count += $batch_max;
    }
  }

  public function estimateGenerationTime() {
    $timestamp =
      Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/last_estimated');
    if ($timestamp && !self::isStale($timestamp)) {
      return
        Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/time_estimate');
    }

    $io = new Varien_Io_File();
    $feed_file_path = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/';
    $io->open(array('path' => $feed_file_path));
    $io->streamOpen('feed_dryrun.txt');

    $collection = Mage::getModel('catalog/product')->getCollection();
    $total_number_of_products = $collection->getSize();
    unset($collection);

    $num_samples =
      ($total_number_of_products <= 500) ? $total_number_of_products : 500;

    $start_time = microtime(true);
    $this->writeProducts($io, $num_samples, false);
    $end_time = microtime(true); // Returns a float in seconds.

    if ($num_samples == 0) {
      return 30;
    }
    $time_spent = ($end_time - $start_time);

    // Estimated Time =
    // 150% of Linear extrapolation of the time to generate 500 products
    // + 30 seconds of buffer time.
    $time_estimate =
      $time_spent * $total_number_of_products / $num_samples * 1.5 + 30;

    Mage::getModel('core/config')->saveConfig(
      'facebook_ads_toolbox/dia/feed/time_estimate',
      $time_estimate
    );
    Mage::getModel('core/config')->saveConfig(
      'facebook_ads_toolbox/dia/feed/last_estimated',
      time()
    );
    return $time_estimate;
  }

  public function read() {
    $feed_file_path = $this->getFullPath();
    return array(
      basename($feed_file_path),
      filesize($feed_file_path),
      file_get_contents($feed_file_path),
    );
  }

  public function saveGZip() {
    self::log(sprintf("generating gzip copy of %s ...", $this->getFileName()));
    $feed_file_path = $this->getFullPath();
    $gz_file_path = $feed_file_path.'.gz';
    $fp = gzopen($gz_file_path, 'w9');
    gzwrite($fp, file_get_contents($feed_file_path));
    gzclose($fp);
    self::log("generated!");
  }

  public function readGZip() {
    $feed_file_path = $this->getFullPath();
    $gz_file_path = $feed_file_path.'.gz';
    return array(
      basename($gz_file_path),
      filesize($gz_file_path),
      file_get_contents($gz_file_path),
    );
  }

  public function getFullPath() {
    return Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/'.$this->getFileName();
  }

  private static function isStale($time_file_modified) {
    return (!$time_file_modified || time() - $time_file_modified > 8*3600);
  }

  public function getTargetFilePath($supportzip) {
    $feed_file_path = $this->getFullPath();
    return $supportzip ? $feed_file_path.'.gz' : $feed_file_path;
  }

  public static function fileIsStale($file_path) {
    $time_file_modified = filemtime($file_path);

    // if we get no file modified time, or the modified time is 8hours ago,
    // we count it as stale
    if (!$time_file_modified) {
      return true;
    } else {
      return self::isStale($time_file_modified);
    }
  }

  public function cacheIsStale($supportzip) {
    $file_path = $this->getTargetFilePath($supportzip);
    $time_now = time();
    return self::fileIsStale($file_path);
  }

}
