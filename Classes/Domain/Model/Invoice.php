<?php
namespace NeosRulez\Shop\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Invoice
{

    /**
     * @var integer
     */
    protected $ordernumber = 0;

    /**
     * @return integer
     */
    public function getOrdernumber()
    {
        return $this->ordernumber;
    }

    /**
     * @param integer $ordernumber
     * @return void
     */
    public function setOrdernumber($ordernumber)
    {
        $this->ordernumber = $ordernumber;
    }

    /**
     * @var string
     */
    protected $invoicenumber = 0;

    /**
     * @return string
     */
    public function getInvoicenumber()
    {
        return $this->invoicenumber;
    }

    /**
     * @param string $invoicenumber
     * @return void
     */
    public function setInvoicenumber($invoicenumber)
    {
        $this->invoicenumber = $invoicenumber;
    }

    /**
     * @var \DateTime
     */
    protected $created;


    public function __construct() {
        $this->created = new \DateTime();
    }

    /**
     * @return string
     */
    public function getCreated() {
        return $this->created;
    }

}
