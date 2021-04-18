<?php

namespace Speakeasyco\MagezonFixes\Model;

class ProductList extends \Magezon\Core\Model\ProductList {

    protected $_storeManager;
    protected $_catalogConfig;
    protected $sqlBuilder;
    protected $productCollectionFactory;
    protected $catalogProductVisibility;
    protected $rule;
    protected $_resource;
    protected $_eventTypeFactory;
    protected $_localeDate;
    protected $conditionsHelper;
    protected $categoryFactory;

    public function __construct (
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Reports\Model\Event\TypeFactory $eventTypeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Widget\Helper\Conditions $conditionsHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ){
        $this->_storeManager            = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->_catalogConfig           = $catalogConfig;
        $this->rule                     = $rule;
        $this->sqlBuilder               = $sqlBuilder;
        $this->_resource                = $resource;
        $this->_eventTypeFactory        = $eventTypeFactory;
        $this->_localeDate              = $localeDate;
        $this->conditionsHelper         = $conditionsHelper;
        $this->categoryFactory          = $categoryFactory;
    }

    public function getProductCollection($source = 'latest', $numberItems = 8, $order = 'newestfirst', $conditions = '', $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID)
    {
        $store = $this->_storeManager->getStore();
        $catID = $store->getRootCategoryId();
        if ($source == 'category' || $source == 'categoryfeatured'){
            $collection = $this->categoryFactory->create()->load($catID)->getProductCollection();
            $collection->addAttributeToSort('position');
        } else {
            $collection = $this->productCollectionFactory->create();
        }
 
        $collection->addAttributeToFilter('visibility', $this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection = $this->_addProductAttributesAndPrices($collection)->addStoreFilter($store);
        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
            foreach ($conditions as $key => $condition) {
                    if (!empty($condition['attribute'])
                        && in_array($condition['attribute'], ['special_from_date', 'special_to_date'])
                    ) {
                        $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
                }
            }
            $this->rule->loadPost(['conditions' => $conditions]);
            $conditions = $this->rule->getConditions();
            $conditions->collectValidatedAttributes($collection);
            $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        }
        $collection->setPageSize($numberItems);

        switch ($source) {
            case 'latest':
            $collection->getSelect()->order('created_at DESC');
            break;

            case 'new':
            $this->_getNewProductCollection($collection);
            break;

            case 'bestseller':
            $this->_getBestSellerProductCollection($collection, $store->getId());
            break;

            case 'onsale':
            $this->_getOnsaleProductCollection($collection, $store->getId());
            break;

            case 'mostviewed':
            $this->_getMostViewedProductCollection($collection, $store->getId());
            break;

            case 'wishlisttop':
            $this->_getWishlisttopProductCollection($collection, $store->getId());
            break;

            case 'free':
            $collection->getSelect()->where('price_index.price = ?', 0);
            break;

            case 'featured':
            $collection->addAttributeToFilter('featured', ['eq' => 1]);
            break;

            case 'categoryfeatured':
            $collection->addAttributeToFilter('featured', ['eq' => 1]);
            break;

            case 'toprated':
            $this->_getTopRatedProductCollection($collection, $store->getId());
            break;

            case 'random':
            $collection->getSelect()->order('RAND()');
            break;
        }

        if ($order!='default') {
            switch ($order) {
                case 'alphabetically':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->setOrder('name', 'ASC');
                    break;

                case 'price_low_to_high':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->setOrder('price', 'ASC');
                    break;

                case 'price_high_to_low':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->setOrder('price', 'DESC');
                    break;

                case 'random':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->getSelect()->order('RAND()');
                    break;

                case 'newestfirst':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->setOrder('entity_id', 'DESC');
                    break;

                case 'oldestfirst':
                    $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                    $collection->setOrder('entity_id', 'ASC');
                    break;

                case 'product_attr':
                    $collection->setOrder('product_position', 'ASC');
                    break;
            }
        } 

        $items = $collection->getItems();
        return $items;
    }
}
