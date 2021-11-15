<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class ProductCollectionFusion extends AbstractFusionObject {

    /**
     * @return array
     */
    public function evaluate():array
    {
        $items = $this->fusionValue('items');
        $fullPrice = 0;
        $fullTax = [];
        $images = [];
        $result = [];
        $stock = false;
        $hasTax = false;
        $taxes = [];
        $taxClasses = [];
        $fullWeight = 0;
        if($items) {
            if(!empty($items)) {
                foreach ($items as $item) {
                    $itemPrice = $item->getProperty('price') ? (float) str_replace(',', '.', $item->getProperty('price')) : false;
                    $fullTax[] = $item->getProperty('tax') ? (float) str_replace(',', '.', $item->getProperty('tax')) : false;

                    if($item->hasProperty('images')) {
                        foreach ($item->getProperty('images') as $image) {
                            $images[] = $image;
                        }
                    }

                    if($item->hasProperty('stock')) {
                        if($item->getProperty('stock')) {
                            $stock = true;
                        }
                    }

                    if($item->hasProperty('tax')) {
                        if($item->getProperty('tax')) {
                            $hasTax = true;
                            $taxes[] = (float) $item->getProperty('tax');
                        }
                    }

                    if($item->hasProperty('taxClass')) {
                        if($item->getProperty('taxClass')) {
                            $taxClasses[] = $item->getProperty('taxClass');
                        }
                    }

                    if($item->hasProperty('weight')) {
                        if($item->getProperty('weight')) {
                            $fullWeight = $fullWeight + ((float) $item->getProperty('weight'));
                        }
                    }

                    $fullPrice = $fullPrice + $itemPrice;
                }
                $result = [
                    'fullPrice' => $fullPrice,
                    'maxTax' => max($fullTax),
                    'images' => $images,
                    'stock' => $stock,
                    'weight' => $fullWeight,
                    'tax' => $hasTax ? max($taxes) : false
                ];
            }
        }
//        \Neos\Flow\var_dump($taxClasses);
//        \Neos\Flow\var_dump($taxes);
//        \Neos\Flow\var_dump($result);
        return $result;
    }

}
