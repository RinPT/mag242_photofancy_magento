<?php

namespace Photofancy\TemporaryChanges\Plugins\Catalog\Model\Layer;

class FilterList
{
    const CATALOG_CATEGORY_FILTER = 'category';
    const CATALOG_SEARCH_CATEGORY_FILTER = 'catalogsearch_category';

    /**
     * @var array
     */
    protected $categoryFilterTypes = [
        self::CATALOG_SEARCH_CATEGORY_FILTER => \Magento\CatalogSearch\Model\Layer\Filter\Category::class,
        self::CATALOG_CATEGORY_FILTER        => \Magento\Catalog\Model\Layer\Filter\Category::class
    ];

    /**
     * FilterList constructor.
     *
     * @param array $categoryFilters
     */
    public function __construct(array $categoryFilters)
    {
        $this->categoryFilterTypes = array_merge($this->categoryFilterTypes, $categoryFilters);
    }

    /**
     * Remove Category Filter from FilterList
     *
     * @param \Magento\Catalog\Model\Layer\FilterList $subject
     * @param                                         $result
     * @param \Magento\Catalog\Model\Layer            $layer
     * @return array
     */
    public function afterGetFilters(
        \Magento\Catalog\Model\Layer\FilterList $subject,
        $result,
        \Magento\Catalog\Model\Layer $layer
    ) {
        foreach ($result as $idx => $filter) {
            if ($this->isCategoryFilter($filter)) {
                unset($result[$idx]);
                break;
            }
        }

        return array_values($result);
    }

    /**
     * Determine if given Filter is one of the declared Category Filter types
     *
     * @param $filter
     * @return bool
     */
    private function isCategoryFilter($filter)
    {
        return \in_array(get_class($filter), $this->categoryFilterTypes);
    }
}
