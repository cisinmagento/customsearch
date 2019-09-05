<?php
/**
 * Customsearch Rewrite Product ListProduct Block
 *
 * @category    Cisin
 * @package     Cisin_Customsearch
 *
 */
namespace Cisin\Customsearch\Block\Rewrite\Product;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    public function _getProductCollection()
    {	
    	$q = '';
        $search_term = $this->getRequest()->getParam('q'); //parameter "name"

		if(!empty(trim($search_term))){
            $q = $search_term;
        }

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$category = $objectManager->get('\Magento\Catalog\Model\Category');
		$cateColl = $category->getCollection()->addAttributeToFilter('name',array('like' => '%'.$q.'%'));

		if (count($cateColl)) {
			foreach ($cateColl as $cate) {
				$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
			    $categoryHelper = $objectManager->get('\Magento\Catalog\Helper\Category');
			    $categoryRepository = $objectManager->get('\Magento\Catalog\Model\CategoryRepository');

			    $categoryId = $cate->getId(); // YOUR CATEGORY ID
			    $category = $categoryFactory->create()->load($categoryId);

			    $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');
				foreach ($categoryProducts as $product) {
				    $categoryProductIds[] = $product->getId();
				}
			}
		
		}else{
			return parent::_getProductCollection();
		}

		$productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$collection = $productCollectionFactory->create();
		$collection = $collection->addAttributeToSelect('*');

        if ($cate->getId()) {
        	$allSearchableAttributes = $this->getAllSearchableAttributes($objectManager);
        	$count = 0;
        	foreach ($allSearchableAttributes as $attribute_code) {
        		$attribute_filter[$count]['attribute'] = $attribute_code;
        		$attribute_filter[$count]['like'] = "%".$q."%";

        		$count++;
        	}
			$attribute_filter[$count]['attribute'] = 'entity_id';
    		$attribute_filter[$count]['in'] = $categoryProductIds;
    			
			$filterBuilder = $objectManager->get('\Magento\Framework\Api\FilterBuilder');
			$searchCriteriaBuilder = $objectManager->get('\Magento\Framework\Api\Search\SearchCriteriaBuilder');
			$searchInterface = $objectManager->get('\Magento\Search\Api\SearchInterface');

			$filterBuilder->setField('search_term');
			$filterBuilder->setValue($q);
			$searchCriteriaBuilder->addFilter($filterBuilder->create());
			$searchCriteria = $searchCriteriaBuilder->create();
			$searchCriteria->setRequestName("quick_search_container");
			$searchResult = $searchInterface->search($searchCriteria);

			$searchItems = $searchResult->getItems();
	        foreach ($searchItems as $searchItem) {
	        	$categoryProductIds[] = $searchItem->getId();
	        }
    		$collection->addAttributeToFilter(array(
	                array('attribute' => 'entity_id', 'in' => $categoryProductIds),
	            ));
        }else{

			$collection->addAttributeToFilter(
			array(
			    array('attribute' => 'sku', 'like' => "%".$q."%") ,
			)
			);  
        }
		
		$this->_productCollection = $collection;
        return $this->_productCollection;
    }


    public function getAllSearchableAttributes($objectManager)
	{
		$collectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory::class);
		$collection = $collectionFactory->create();
		$attributeInfo = $collection->setItemObjectClass('Magento\Catalog\Model\ResourceModel\Eav\Attribute')
	                 ->addFieldToFilter('is_searchable',1);
		foreach($attributeInfo as $attributes) {		    
		    $attribute_data[] = $attributes->getAttributeCode();
		}
		return $attribute_data;
	}
}