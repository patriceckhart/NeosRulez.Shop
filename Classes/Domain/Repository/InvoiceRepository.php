<?php
namespace NeosRulez\Shop\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class InvoiceRepository extends Repository {

    public function countInvoices($start) {
        $class = '\NeosRulez\Shop\Domain\Model\Invoice';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->execute();
        $count_result = count($result);
        $new_result = $start + intval($count_result);
        return $new_result;
    }

}