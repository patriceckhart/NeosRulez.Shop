<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Mpdf\Mpdf;

/**
 * Class Invoice
 *
 * @Flow\Scope("singleton")
 */
class InvoiceService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\PaymentService
     */
    protected $paymentService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Repository\InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }


    /**
     * @param string $args
     * @return void
     */
    public function execute($args, $create) {
        $variables['args'] = $args;
        $variables['items'] = $this->cart->cart();
        $variables['payment_service'] = $this->paymentService->getPaymentByIdentifier($args['payment']);
        $variables['url'] = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        return $this->createInvoice($variables, $create);
    }

    /**
     * @param array $variables
     * @param bool $create
     * @param bool $download
     * @return void
     */
    public function createInvoice(array $variables, bool $create, bool $download = false) {

        $variables['args']['cart_variables']['taxcart'] = $this->settings['tax'];
        $prefix = $variables['args']['cart_variables']['invoice_number_prefix'];
        $start = intval($variables['args']['cart_variables']['invoice_number']);

        $fiscalYearStart = $variables['args']['cart_variables']['fiscal_year_start'];
        $fiscalYearEnd = $variables['args']['cart_variables']['fiscal_year_end'];

        if(!$start) {
            $start = 1;
        }

        if($create) {
            $invoice_number = $prefix . $this->invoiceRepository->countInvoices($start, $fiscalYearStart, $fiscalYearEnd);

            $invoice = new \NeosRulez\Shop\Domain\Model\Invoice();
            $invoice->setOrdernumber($variables['args']['order_number']);
            $invoice->setInvoicenumber($invoice_number);
            $this->invoiceRepository->add($invoice);
            $this->persistenceManager->persistAll();

            $variables['invoice_date'] = new \DateTime();
        } else {
            $rInvoice = $this->invoiceRepository->findByOrdernumber($variables['args']['order_number'])->getFirst();

            if(empty($rInvoice) || $rInvoice == null) {
                $invoice_number = $prefix . $this->invoiceRepository->countInvoices($start, $fiscalYearStart, $fiscalYearEnd);
                $invoice = new \NeosRulez\Shop\Domain\Model\Invoice();
                $invoice->setOrdernumber($variables['args']['order_number']);
                $invoice->setInvoicenumber($invoice_number);
                $this->invoiceRepository->add($invoice);
                $this->persistenceManager->persistAll();
                $variables['invoice_date'] = new \DateTime();
            } else {
                $invoice_number = $prefix . $rInvoice->getInvoicenumber();
                $variables['invoice_date'] = $rInvoice->getCreated();
            }
        }

        $variables['invoice_number'] = $invoice_number;

        $view = new \Neos\FluidAdaptor\View\StandaloneView();
        $view->setTemplatePathAndFilename($this->settings['Invoice']['templatePathAndFilename']);
        $view->assignMultiple($variables);
        $pdf = $view->render();

//        echo $pdf;

        $mpdf = new \Mpdf\Mpdf();
        $stylesheet = file_get_contents('resource://NeosRulez.Shop/Public/Styles/print.css');
        $mpdf->SetTitle($invoice_number);

//        $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
//        $mpdf->WriteHTML($pdf,\Mpdf\HTMLParserMode::HTML_BODY);
//        $mpdf->SetJS('this.print();');
//        $mpdf->Output();

        $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($pdf,\Mpdf\HTMLParserMode::HTML_BODY);

        if($download) {
            $mpdf->Output('Rechnung.pdf', 'D');
            exit;
        }
        $file = $mpdf->Output('', 'S');
        return $file;

    }

}
