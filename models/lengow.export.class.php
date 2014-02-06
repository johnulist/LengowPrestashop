<?php

/**
 * Copyright 2013 Lengow.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
require_once 'lengow.core.class.php';
require_once 'lengow.product.class.php';
require_once 'lengow.cache.class.php';
if (_PS_VERSION_ >= '1.5') {
    require_once 'lengow.specificprice.class.php';
}

/**
 * The Lengow Export Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowExportAbstract {

    /**
     * Version.
     */
    const VERSION = '1.0.1';

    /**
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
        'id_product' => 'id',
        'name_product' => 'name',
        'reference_product' => 'reference',
        'supplier_reference' => 'supplier_reference',
        'manufacturer' => 'manufacturer',
        'category' => 'breadcrumb',
        'description' => 'description',
        'description_short' => 'short_description',
        'price_product' => 'price',
        'wholesale_price' => 'wholesale_price',
        'price_ht' => 'price_duty_free',
        'price_reduction' => 'price_sale',
        'pourcentage_reduction' => 'price_sale_percent',
        'quantity' => 'quantity',
        'weight' => 'weight',
        'ean' => 'ean',
        'upc' => 'upc',
        'ecotax' => 'ecotax',
        'available_product' => 'available',
        'url_product' => 'url',
        'image_product' => 'image_1',
        'fdp' => 'price_shipping',
        'id_mere' => 'id_parent',
        'delais_livraison' => 'delivery_time',
        'image_product_2' => 'image_2',
        'image_product_3' => 'image_3',
        'reduction_from' => 'sale_from',
        'reduction_to' => 'sale_to',
        'meta_keywords' => 'meta_keywords',
        'meta_description' => 'meta_description',
        'url_rewrite' => 'url_rewrite',
        'product_type' => 'type',
        'product_variation' => 'variation',
        'currency' => 'currency',
        'condition' => 'condition',
        'supplier' => 'supplier',
        'minimal_quantity' => 'minimal_quantity',
        'is_virtual' => 'is_virtual',
        'available_for_order' => 'available_for_order',
        'available_date' => 'available_date',
        'show_price' => 'show_price',
        'visibility' => 'visibility',
        'available_now' => 'available_now',
        'available_later' => 'available_later',
        'stock_availables' => 'stock_availables',
    );

    /**
     * CSV separator.
     */
    public static $CSV_SEPARATOR = '|';

    /**
     * CSV protection.
     */
    public static $CSV_PROTECTION = '"';

    /**
     * CSV End of line.
     */
    public static $CSV_EOL = "\r\n";

    /**
     * Additional head attributes export.
     */
    private $head_attributes_export;

    /**
     * Additional head image export.
     */
    private $head_images_export;

    /**
     * Format to return.
     */
    private $format = 'csv';

    /**
     * Full export products + attributes.
     */
    private $full = true;

    /**
     * Export all products.
     */
    private $all = true;

    /**
     * Max images.
     */
    private $max_images = 0;

    /**
     * File ressource
     */
    private $handle;

    /**
     * File name
     */
    private $filename;

    /**
     * File name temp
     */
    private $filename_temp;

    /**
     * File ressource
     */
    private $fields;

    /**
     * Attributes to export.
     */
    private $attributes = array();

    /**
     * Features to export.
     */
    private $features = array();

    /**
     * Stream return.
     */
    private $stream = true;

    /**
     * Product data.
     */
    private $data = array();

    /**
     * Product data.
     */
    public static $full_title = true;

    /**
     * Export active product.
     */
    private $all_product = false;

    /**
     * Export active product.
     */
    private $export_features = false;

    /**
     * Export limit
     */
    private $limit = null;

    /**
     * Export only specified products
     */
    private $product_ids = null;

    /**
     * Construc new Lengow export.
     *
     * @param string $format The format used to export
     *
     * @return Exception Error
     */
    public function __construct($format = null, $fullmode = null, $all = null, $stream = null, $full_title = null, $all_product = null, $export_features = null, $limit = null, $product_ids = null) {
        try {
            $this->setFormat($format);
            $this->setFullmode($fullmode);
            $this->setExportFeatures($export_features);
            $this->setProducts($all);
            $this->setStream($stream);
            $this->setTitle($full_title);
            $this->setAllProduct($all_product);
            $this->setLimit($limit);
            $this->setIdsProduct($product_ids);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Make fields to export.
     */
    private function _makeFields() {
        if (is_array(json_decode(Configuration::get('LENGOW_EXPORT_FIELDS')))) {
            foreach (json_decode(Configuration::get('LENGOW_EXPORT_FIELDS')) as $field) {
                $this->fields[] = $field;
            }
        } else {
            foreach (LengowCore::$DEFAULT_FIELDS as $field) {
                $this->fields[] = $field;
            }
        }
        
        //Features
        if ($this->features) {
            foreach ($this->features as $feature) {
                if(in_array($this->_toFieldname($feature['name']), $this->fields)) {
                    $this->fields[] = $this->_toFieldname($feature['name']) . '_1';
                } else {
                    $this->fields[] = $this->_toFieldname($feature['name']);
                }
            }
        }
        // Attributes
        if ($this->attributes) {
            foreach ($this->attributes as $attribute) {
                if(!in_array( $this->_toFieldname($attribute['name']), $this->fields)) {
                    $this->fields[] = $this->_toFieldname($attribute['name']);
                } else {
                    $this->fields[] = $this->_toFieldname($attribute['name']) . '_2';
                }
            }
        }
        // Images
        if ($this->max_images > 3) {
            for ($i = 3; $i <= ($this->max_images - 1); $i++) {
                $this->fields[] = 'image_' . ($i + 1);
            }
        }
    }

    /**
     * Get header.
     *
     * @return varchar The header.
     */
    private function _getHeader() {
        $head = '';
        switch ($this->format) {
            case 'csv' :
                foreach ($this->fields as $name) {
                    $head .= self::$CSV_PROTECTION . $this->_toUpperCase($name) . self::$CSV_PROTECTION . self::$CSV_SEPARATOR;
                }
                return rtrim($head, self::$CSV_SEPARATOR) . self::$CSV_EOL;
            case 'xml' :
                return '<?xml version="1.0" ?>' . "\r\n"
                        . '<catalog>' . "\r\n";
            case 'json' :
                return '{"catalog":[';
            case 'yaml' :
                return '"catalog":' . "\r\n";
        }
    }

    /**
     * Get footer.
     *
     * @return varchar The footer.
     */
    public function getFooter() {
        switch ($this->format) {
            case 'csv' :
                return '';
            case 'xml' :
                return '</catalog>';
            case 'json' :
                return ']}';
            case 'yaml' :
                return '';
        }
    }

    /**
     * Set format to export.
     *
     * @param string $format The format to export
     *
     * @return boolean.
     */
    public function setFormat($format) {
        if ($format !== null) {
            $available_formats = LengowCore::getExportFormats();
            $array_formats = array();
            foreach ($available_formats as $f) {
                $array_formats[] = $f->name;
            }
            if (in_array($format, $array_formats)) {
                $this->format = $format;
                return true;
            }
            throw new Exception('Illegal export format');
        } else {
            $this->format = LengowCore::getExportFormat();
        }
        return false;
    }

    /**
     * Set format to export.
     *
     * @param string $format The format to export
     *
     * @return boolean.
     */
    public function setFullmode($fullmode) {
        if ($fullmode !== null)
            $this->full = $fullmode;
        else
            $this->full = LengowCore::isExportFullmode();
    }

    /**
     * Set export features.
     *
     * @param string $format The format to export
     *
     * @return boolean.
     */
    public function setExportFeatures($export_features) {
        if ($export_features !== null)
            $this->export_features = $export_features;
        else
            $this->export_features = LengowCore::isExportFeatures();
    }

    /**
     * Set format to export.
     *
     * @param string $format The format to export
     *
     * @return boolean.
     */
    public function setProducts($all) {
        if ($all !== null)
            $this->all = $all;
        else
            $this->all = LengowCore::isExportAllProducts();
    }

    /**
     * Set stream export.
     *
     * @param string $format The format to export
     *
     * @return boolean.
     */
    public function setStream($stream) {
        if ($stream !== null)
            $this->stream = $stream;
        else
            $this->stream = LengowCore::exportInFile() ? false : true;
    }

    /**
     * Set title param export.
     *
     * @param boolean $title False for only title, True for title + attribute
     *
     * @return boolean.
     */
    public function setTitle($title) {
        if ($title !== null)
            self::$full_title = $title;
        else
            self::$full_title = Configuration::get('LENGOW_EXPORT_FULLNAME');
    }

    /**
     * Set active param export.
     *
     * @param boolean $active True for all product, False for only enabled product
     *
     * @return boolean.
     */
    public function setAllProduct($all_product) {
        if ($all_product !== null)
            $this->all_product = $all_product;
        else
            $this->all_product = LengowCore::exportAllProduct() ? true : false;
    }

    public function setLimit($limit) {
        $this->limit = $limit;
    }

    public function setIdsProduct($product_ids) {
        if(!is_array($product_ids))
            return;
        $this->product_ids = $product_ids;
    }

    static function isFullName() {
        return self::$full_title ? true : false;
    }

    /**
     * Execute the export.
     *
     * @return mixed.
     */
    public function exec() {
        try {
            LengowCore::log('export : init');
            if (LengowCore::countExportAllImages() == 'all')
                $this->max_images = LengowProduct::getMaxImages();
            else
                $this->max_images = LengowCore::countExportAllImages();
            // Is full export
            if ($this->full) {
                $this->attributes = AttributeGroup::getAttributesGroups(LengowCore::getContext()->language->id);
                $this->features = Feature::getFeatures(LengowCore::getContext()->language->id);
            } else if($this->export_features) {
                $this->features = Feature::getFeatures(LengowCore::getContext()->language->id);
            }
            $this->_makeFields();
            // Init fields to export
            $this->_write('header');
            $products = LengowProduct::exportIds($this->all, $this->all_product, $this->product_ids);
            if (!$products) {
                // Force export all products if no have selected product
                $products = LengowProduct::exportIds(true);
            }
            $last = end($products);
            LengowCore::log('export : find ' . count($products) . ' products');
            $i = 0;
            foreach ($products as $p) {
                $product = new LengowProduct($p['id_product'], LengowCore::getContext()->language->id);

                $combinations = $product->getCombinations();
                if($p['id_product'] == $last['id_product'] && empty($combinations))
                    $is_last = true;

                $this->_write('data', $this->_make($product), $is_last);
                // Attributes
                if ($this->full) {
                    $total = count($product->getCombinations());
                    $count = 0;
                    $is_last = false;
                    foreach ($product->getCombinations() as $id_product_attribute => $combination) {
                        $count++;
                        if($p['id_product'] == $last['id_product'] && $total ==  $count)
                            $is_last = true;
                        $this->_write('data', $this->_make($product, $id_product_attribute), $is_last);
                    }
                }
                $product = null;
                if ($i % 10 == 0)
                    LengowCore::log('export : ' . $i . ' products');
                $product = null;
                db::getInstance()->queries = array();
                db::getInstance()->uniqQueries = array();
                LengowCache::clear();
                if (function_exists('gc_collect_cycles'))
                    gc_collect_cycles();

                $i++;

                if($this->limit != null)
                    if($i >= $this->limit)
                        break;
            }
            $this->_write('footer');
            LengowCore::log('export : end');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Make the export for a product with current format.
     *
     * @param object $product The product to export
     * @param object $id_product_attribute The id product attribute to export
     *
     * @return array Product data
     */
    public function _make($product, $id_product_attribute = null) {
        $array_product = array();
        // Default fieldss
        if (is_array(json_decode(Configuration::get('LENGOW_EXPORT_FIELDS')))) {
            foreach (json_decode(Configuration::get('LENGOW_EXPORT_FIELDS')) as $field) {
                $array_product[$field] = $product->getData(self::$DEFAULT_FIELDS[$field], $id_product_attribute);
            }
        } else {
            foreach (self::$DEFAULT_FIELDS as $field => $value) {
                $array_product[$field] = $product->getData($value, $id_product_attribute);
            }
        }

        // Features
        if ($this->features) {
            foreach ($this->features as $feature) {
                if(array_key_exists($this->_toFieldname($feature['name']), $array_product))
                    $key = $this->_toFieldname($feature['name']) . '_1';
                else
                    $key = $this->_toFieldname($feature['name']);
                $array_product[$key] = $product->getDataFeature($feature['name']);
            }
        }
        // Attributes
        if ($this->attributes) {
            foreach ($this->attributes as $attribute) {
                $key = '';
                if(array_key_exists($this->_toFieldname($attribute['name']), $array_product))
                    $key = $this->_toFieldname($attribute['name']) . '_2';
                else
                    $key = $this->_toFieldname($attribute['name']);

                if (!$id_product_attribute)
                    $array_product[$key] = '';
                else
                    $array_product[$key] = $product->getDataAttribute($id_product_attribute, $attribute['name']);
            }
        }
        // Is export > 3 images
        if (LengowCore::countExportAllImages() > 3 || LengowCore::countExportAllImages() == 'all') {
            // Export x or all images
            for ($i = 3; $i <= $this->max_images; $i++)
                $array_product['image_' . $i] = $product->getData('image_' . $i, $id_product_attribute);
        }
        return $array_product;
    }

    /**
     * Write the export on file or screen.
     *
     * @return mixed
     */
    private function _write($type, $data = null, $last = false) {
        switch ($type) {
            case 'header' :
                $head = $this->_getHeader();
                if ($this->stream) {
                    switch ($this->format) {
                        case 'csv':
                            header('Content-Type: text/plain; charset=utf-8');
                            echo $head;
                            break;
                        case 'xml':
                            header('Content-Type: text/xml; charset=utf-8');
                            echo $head;
                            break;
                        case 'json':
                            header('Content-Type: application/json; charset=utf-8');
                            echo $head;
                            break;
                        case 'yaml':
                            header('Content-Type: text/x-yaml; charset=utf-8');
                            echo $head;
                            break;
                    }
                }
                if (!$this->stream)
                    $this->_writeOnFile($head);
                break;
            case 'data' :
                if (!$this->stream)
                    $content = '';
                $line = '';
                switch ($this->format) {
                    case 'csv':
                        foreach ($this->fields as $name) {
                            $line .= self::$CSV_PROTECTION . str_replace(array(self::$CSV_PROTECTION, '\\'), '', (isset($data[$name]) ? $data[$name] : '')) . self::$CSV_PROTECTION . self::$CSV_SEPARATOR;
                        }
                        $line = rtrim($line, self::$CSV_SEPARATOR) . self::$CSV_EOL;
                        break;
                    case 'xml' :
                        $line .= '<product>' . "\r\n";
                        foreach ($this->fields as $name) {
                            $line .= '<' . $name . '><![CDATA[' . (isset($data[$name]) ? $data[$name] : '') . ']]></' . $name . '>' . "\r\n";
                        }
                        $line .= '</product>' . "\r\n";
                        break;
                    case 'json' :
                        foreach ($this->fields as $name) {
                            $json_array[$name] = $data[$name];
                        }
                        $line .= Tools::jsonEncode($json_array) . (!$last ? ',' : '');
                        break;
                    case 'yaml' :
                        $line .= '  ' . '"product":' . "\r\n";
                        foreach ($this->fields as $name) {
                            $line .= '    ' . '"' . $name . '":' . $this->_addSpaces($name, 22) . (isset($data[$name]) ? $data[$name] : '') . "\r\n";
                        }
                        break;
                }
                if (!$this->stream)
                    $this->_writeOnFile($line);
                else
                    echo $line;
                flush();
                break;
            case 'footer' :
                $footer = $this->getFooter();
                if (!$this->stream) {
                    $this->_writeOnFile($footer);
                    $this->_closeFile();
                    $lengow = new Lengow();
                    echo $lengow->getFileLink($this->format);
                } else
                    echo $footer;
                break;
        }
    }

    /**
     * Open and write data on file
     *
     * @param string $data The data
     */
    private function _writeOnFile($data) {
        if (!$this->handle) {
            $sep = DIRECTORY_SEPARATOR;
            $context = LengowCore::getContext();
            $id_shop = $context->shop->id;
            $this->filename = _PS_MODULE_DIR_ . 'lengow' . $sep . 'export' . $sep . 'flux-' . $id_shop . '.' . $this->format;
            $this->handle = fopen($this->filename, 'w+');
        }
        fwrite($this->handle, $data);
    }

    /**
     * Close .
     *
     * @param string $str The fieldname
     *
     * @return string The formated header.
     */
    private function _closeFile() {
        if ($this->handle) {
            fclose($this->handle);
        }
    }

    /**
     * For CSV, transform header to uppercase without accent.
     *
     * @param string $str The fieldname
     *
     * @return string The formated header.
     */
    private function _toUpperCase($str) {
        if (_PS_VERSION_ <= '1.4.5')
            return substr(strtoupper(preg_replace('/[^a-zA-Z0-9_]+/', '', str_replace(array(' ', '\''), '_', LengowCore::replaceAccentedChars($str)))), 0, 58);
        else
            return substr(strtoupper(preg_replace('/[^a-zA-Z0-9_]+/', '', str_replace(array(' ', '\''), '_', Tools::replaceAccentedChars($str)))), 0, 58);
    }

    /**
     * For YAML, JSON, XML, transform fieldname without accent and spaces.
     *
     * @param string $str The fieldname
     *
     * @return string The formated fieldname.
     */
    private function _toFieldname($str) {
        if (_PS_VERSION_ <= '1.4.5')
            return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '', str_replace(array(' ', '\''), '_', LengowCore::replaceAccentedChars($str))));
        else
            return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '', str_replace(array(' ', '\''), '_', Tools::replaceAccentedChars($str))));
    }

    /**
     * For YAML, add spaces to have good indentation.
     *
     * @param string $name The fielname
     * @param string $maxsize The max spaces
     *
     * @return string Spaces.
     */
    private function _addSpaces($name, $size) {
        $strlen = strlen($name);
        $spaces = '';
        for ($i = $strlen; $i < $size; $i++) {
            $spaces .= ' ';
        }
        return $spaces;
    }

    /**
     * The export format aivalable.
     *
     * @return array Formats
     */
    public static function getDefaultFields() {
        $array_fields = array();
        foreach (self::$DEFAULT_FIELDS as $fields => $value) {
            $array_fields[] = new LengowOption($fields, $value);
        }
        return $array_fields;
    }

}
