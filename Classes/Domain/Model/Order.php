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
     * @var boolean
     */
    protected $canceled = false;

    /**
     * @return boolean
     */
    public function getCanceled()
    {
        return $this->canceled;
    }

    /**
     * @param boolean $canceled
     * @return void
     */
    public function setCanceled($canceled)
    {
        $this->canceled = $canceled;
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
     * @var string
     * @ORM\Column(type="text")
     * @ORM\Column(length=4000)
     */
    protected $summary;

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     * @return void
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $user;

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return void
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @var boolean
     */
    protected $done = false;

    /**
     * @return boolean
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * @param boolean $done
     * @return void
     */
    public function setDone($done)
    {
        $this->done = $done;
    }

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $coupons = '';

    /**
     * @return string|null
     */
    public function getCoupons(): string|null
    {
        return $this->coupons;
    }

    /**
     * @param string $coupons
     * @return void
     */
    public function setCoupons(string $coupons): void
    {
        $this->coupons = $coupons;
    }

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $siteNode = '';

    /**
     * @return string|null
     */
    public function getSiteNode(): string|null
    {
        return $this->siteNode;
    }

    /**
     * @param string $siteNode
     * @return void
     */
    public function setSiteNode(string $siteNode): void
    {
        $this->siteNode = $siteNode;
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
