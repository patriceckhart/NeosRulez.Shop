<?php
namespace NeosRulez\Shop\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class InvoiceRepository extends Repository {

    public function countInvoices($start, $fiscalYearStart, $fiscalYearEnd) {

        $now = new \DateTime();
        $next = new \DateTime();

        if($fiscalYearStart == '') {
            $fiscalYearStart = '01-01';
        }

        if($fiscalYearEnd == '') {
//            $fiscalYearEnd = new \DateTime();
//            $fiscalYearEnd = $fiscalYearEnd->modify('+1 year');
            $fiscalYearEnd = '12-31';
        }

        $fiscalYearStartNew = $now->format('Y') . '-' . (is_string($fiscalYearStart) ? $fiscalYearStart : $fiscalYearStart->format('m-d'));
        $fiscalYearEndNew = $next->modify('+1 year')->format('Y') . '-' . (is_string($fiscalYearEnd) ? $fiscalYearEnd : $fiscalYearEnd->format('m-d'));

        $fiscalYearStartDate = new \DateTime($fiscalYearStartNew);
        $fiscalYearEndDate = new \DateTime($fiscalYearEndNew);

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->greaterThanOrEqual('created', $fiscalYearStartDate),
                $query->lessThanOrEqual('created', $fiscalYearEndDate)
            )
        );
        $result = $query->execute();
        $resultCount = count($result);

        $count = ((int)$start + $resultCount);

        return $count;
    }

}
