<?php

namespace Commerce\Carts;

use Commerce\Interfaces\Cart;

class ProductsCart extends CustomTableCart implements Cart
{
    public function __construct($modx, $instance = 'products', $store = 'session')
    {
        parent::__construct($modx, $instance, 'site_content', $store);
    }

    protected function validateItem(array $item)
    {
        if (parent::validateItem($item) === false) {
            return false;
        }

        $templates = array_filter(\APIhelpers::cleanIDs($this->modx->commerce->getSetting('product_templates', '')));

        if (!empty($templates)) {
            $doc = $this->modx->getDocument($item['id']);

            if (empty($doc) || !in_array($doc['template'], $templates)) {
                $this->modx->logEvent(0, 3, 'Item not added, template mismatch.<br><pre>Request: ' . htmlentities(print_r($item, true)) . '<br>Product: ' . htmlentities(print_r($doc, true)) . '<br>Allowed templates: ' . htmlentities(print_r($templates, true)) . '</pre>', 'Commerce Cart');
                return false;
            }
        }

        return true;
    }

    protected function getItemName($id)
    {
        if (is_numeric($id) && $id > 0) {
            if (!in_array($this->titleField, ['pagetitle', 'longtitle', 'description', 'introtext', 'menutitle'])) {
                $tv = $this->modx->getTemplateVar($this->titleField, '*', $id);

                if (!empty($tv)) {
                    return $tv['value'];
                }
            }

            return parent::getItemName($id);
        }

        return $this->defaults['name'];
    }

    protected function getItemPrice($id)
    {
        if (is_numeric($id) && $id > 0) {
            $tv = $this->modx->getTemplateVar($this->priceField, '*', $id);

            if (!empty($tv)) {
                return ci()->currency->convertFromDefault($tv['value']);
            }
        }

        return $this->defaults['price'];
    }
}