<?php

namespace Omitsis\ImportAddon\Model\Import\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;

/**
 * Importing configurable products
 * @package Magento\ConfigurableImportExport\Model\Import\Product\Type
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Configurable extends \Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable
{
    /**
     * In case we've dynamically added new attribute option during import we need to add it to our cache
     * in order to keep it up to date.
     *
     * @todo Try an optimal solution in order to update only the need part of the cache (Check AddAttributeOption)
     *
     */
    public function refreshCacheAttributes()
    {
        // Need to force reload attribute cache
        self::$commonAttributesCache = [];
        $this->_initAttributes();
    }
}