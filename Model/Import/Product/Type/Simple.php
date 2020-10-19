<?php

namespace Omitsis\ImportAddon\Model\Import\Product\Type;

/**
 * Import entity simple product type
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Simple extends \Magento\CatalogImportExport\Model\Import\Product\Type\Simple
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