<?php
 
namespace Speakeasyco\MagezonFixes\Plugin;
 
class CustomDataSources
{    
    public function afterGetSourceOptions(\Magezon\Builder\Data\Element\ProductList $subject, $result)
    {
        $newSources = [
            [
                'label' => __('Category'),
                'value' => 'category'
            ],
			[
                'label' => __('Category Featured'),
                'value' => 'categoryfeatured'
            ]
        ];
        $result = array_merge($newSources, $result);
        return $result;
    }
      
}
?>