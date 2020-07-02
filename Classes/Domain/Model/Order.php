<?php
namespace NeosRulez\Shop\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Order
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
     * @ORM\Column(type="text")
     * @ORM\Column(length=4000)
     */
    protected $invoicedata;

    /**
     * @return string
     */
    public function getInvoicedata()
    {
        return $this->invoicedata;
    }

    /**
     * @param string $invoicedata
     * @return void
     */
    public function setInvoicedata($invoicedata)
    {
        $this->invoicedata = $invoicedata;
    }

    /**
     * @var string
     */
    protected $payment;

    /**
     * @return string
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param string $payment
     * @return void
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @var integer
     */
    protected $paid = 0;

    /**
     * @return integer
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * @param integer $paid
     * @return void
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    /**
     * @var string
     * @ORM\Column(type="text")
     * @ORM\Column(length=4000)
     */
    protected $cart;

    /**
     * @return string
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param string $cart
     * @return void
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
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
