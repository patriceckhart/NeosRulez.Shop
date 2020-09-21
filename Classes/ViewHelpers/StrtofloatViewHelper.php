<?php
namespace NeosRulez\Shop\ViewHelpers;

class StrtofloatViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {

    public function initializeArguments()
    {
        $this->registerArgument('string', 'string', 'string to be converted to float', true);
    }

    /**
     * @return string
     */
    public function render() {
        $string_number = $this->arguments['string'];
        $number = floatval(str_replace(',', '.', str_replace('.', '', $string_number)));
        return $number;
    }

}