<?php

namespace Omitsis\ImportAddon\Model\Import\Product;

use Magento\Catalog\Model\Product\Attribute\OptionManagement;
use \Magento\CatalogImportExport\Model\Import\Product as Product;
use \Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as RowValidatorInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Stdlib\StringUtils;

class Validator extends \Magento\CatalogImportExport\Model\Import\Product\Validator
{
	/**
	 * @array  OptionManagement
	 */
	protected $dynamicallyOptionAdded = array();
	/**
	 * @var OptionManagement
	 */
	private $optionManagement;
	/**
	 * @var AttributeOptionInterfaceFactory
	 */
	private $optionDataFactory;
	/**
	 * @var DataObjectHelper
	 */
	private $dataObjectHelper;

	/**
	 * @param StringUtils $string
	 * @param RowValidatorInterface[] $validators
	 * @param OptionManagement $optionManagement
	 * @param AttributeOptionInterfaceFactory $optionDataFactory
	 * @param DataObjectHelper $dataObjectHelper
	 */
	public function __construct(
		StringUtils $string,
		$validators = [],
		OptionManagement $optionManagement,
		AttributeOptionInterfaceFactory $optionDataFactory,
		DataObjectHelper $dataObjectHelper
	) {
		$this->optionManagement = $optionManagement;
		$this->optionDataFactory = $optionDataFactory;
		$this->dataObjectHelper = $dataObjectHelper;

		parent::__construct($string, $validators);
	}

	/**
	 * @param $attrCode
	 * @param array $attrParams
	 * @param array $rowData
	 * @return bool
	 * @throws InputException
	 * @throws StateException
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function isAttributeValid($attrCode, array $attrParams, array $rowData)
	{
		$this->_rowData = $rowData;
		if (isset($rowData['product_type']) && !empty($attrParams['apply_to'])
			&& !in_array($rowData['product_type'], $attrParams['apply_to'])
		) {
			return true;
		}

		if (!$this->isRequiredAttributeValid($attrCode, $attrParams, $rowData)) {
			$valid = false;
			$this->_addMessages(
				[
					sprintf(
						$this->context->retrieveMessageTemplate(
							RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
						),
						$attrCode
					)
				]
			);
			return $valid;
		}

		if (!strlen(trim($rowData[$attrCode]))) {
			return true;
		}
		switch ($attrParams['type']) {
			case 'varchar':
			case 'text':
				$valid = $this->textValidation($attrCode, $attrParams['type']);
				break;
			case 'decimal':
			case 'int':
				$valid = $this->numericValidation($attrCode, $attrParams['type']);
				break;
			case 'select':
			case 'boolean':
			case 'multiselect':
				$values = explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]);
				$valid = true;

				// Start custom
				foreach ($values as $value) {
					// If option not exist and not already dynamically added
					if (!empty($value) && !isset($attrParams['options'][strtolower($value)]) && !isset($this->dynamicallyOptionAdded[$attrCode][strtolower($value)])) {
						// Create option value
						$optionDataObject = $this->optionDataFactory->create();
						$this->dataObjectHelper->populateWithArray(
							$optionDataObject,
							array(
								'label' => $value,
								'sort_order' => 100,
								'is_default' => false
							),
							'\Magento\Eav\Api\Data\AttributeOptionInterface'
						);

						// Add option dynamically
						if ($this->optionManagement->add($attrCode, $optionDataObject)) {
							// Add new option value dynamically created to the different entityTypeModel cache
//							$entityTypeModel                = $this->context->retrieveProductTypeByName($rowData['product_type']);
//							$configurableEntityTypeModel    = $this->context->retrieveProductTypeByName('configurable');
//
//							// Refresh attributes cache for entityTypeModel cache
//							if ($entityTypeModel) {
//								$entityTypeModel->refreshCacheAttributes();
//							}
//
//							if ($configurableEntityTypeModel) {
//								$configurableEntityTypeModel->refreshCacheAttributes();
//							}

							$this->dynamicallyOptionAdded[$attrCode][strtolower($value)] = true;
							$attrParams['options'][strtolower($value)] = true;
						}
					}
				}

				if (isset($this->dynamicallyOptionAdded[$attrCode])) {
					foreach ($this->dynamicallyOptionAdded[$attrCode] as $key => $value) {
						$attrParams['options'][$key] = $value;
					}
				}
				// end custom

				foreach ($values as $value) {
					$valid = $valid && isset($attrParams['options'][strtolower($value)]);
				}
				if (!$valid) {
					$this->_addMessages(
						[
							sprintf(
								$this->context->retrieveMessageTemplate(
									RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
								),
								$attrCode
							)
						]
					);
				}

				break;
			case 'datetime':
				$val = trim($rowData[$attrCode]);
				$valid = strtotime($val) !== false;
				if (!$valid) {
					$this->_addMessages([RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE]);
				}
				break;
			default:
				$valid = true;
				break;
		}

		if ($valid && !empty($attrParams['is_unique'])) {
			if (isset($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]])
				&& ($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[Product::COL_SKU])) {
				$this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
				return false;
			}
			$this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[Product::COL_SKU];
		}

		if (!$valid) {
			$this->setInvalidAttribute($attrCode);
		}

		return (bool)$valid;

	}
}