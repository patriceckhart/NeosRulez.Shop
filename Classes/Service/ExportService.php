<?php
namespace NeosRulez\Shop\Service;

use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use NeosRulez\Shop\Domain\Repository\InvoiceRepository;
use NeosRulez\Shop\Domain\Repository\OrderRepository;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @Flow\Scope("singleton")
 */
class ExportService {

    /**
     * @Flow\Inject
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @Flow\Inject
     * @var ContextFactory
     */
    protected $contextFactory;

    /**
     * @return string
     */
    public function exportOrders(): string
    {
        $spreadsheet = new Spreadsheet();
        $orders = $spreadsheet->getActiveSheet();
        $articles = $spreadsheet->createSheet();

        $orders->setTitle('Bestellungen');
        $articles->setTitle('Artikel');

        $orders->getHeaderFooter()->setOddHeader('&C&H' . $orders->getTitle());
        $articles->getHeaderFooter()->setOddHeader('&C&H' . $articles->getTitle());

        $orders->setCellValue('A1', 'Bestell-Nr.');
        $orders->setCellValue('B1', 'Rechnungs-Nr.');

        $orders->setCellValue('C1', 'Rechnung: Firma');
        $orders->setCellValue('D1', 'Rechnung: Vorname');
        $orders->setCellValue('E1', 'Rechnung: Nachname');
        $orders->setCellValue('F1', 'Rechnung: Adresse');
        $orders->setCellValue('G1', 'Rechnung: Postleitzahl');
        $orders->setCellValue('H1', 'Rechnung: Ort');
        $orders->setCellValue('I1', 'Rechnung: Land');
        $orders->setCellValue('J1', 'Rechnung: E-Mail Adresse');
        $orders->setCellValue('K1', 'Anmerkungen');

        $orders->setCellValue('L1', 'Lieferung: Firma');
        $orders->setCellValue('M1', 'Lieferung: Vorname');
        $orders->setCellValue('N1', 'Lieferung: Nachname');
        $orders->setCellValue('O1', 'Lieferung: Adresse');
        $orders->setCellValue('P1', 'Lieferung: Postleitzahl');
        $orders->setCellValue('Q1', 'Lieferung: Ort');

        $orders->setCellValue('R1', 'Versandart');

        $orders->setCellValue('S1', 'Zahlung');

        $orders->setCellValue('T1', 'Zwischensumme');
        $orders->setCellValue('U1', 'Enthaltene Steuer');
        $orders->setCellValue('V1', 'Versandkosten');
        $orders->setCellValue('W1', 'Enthaltene Steuer (Versand)');
        $orders->setCellValue('X1', 'Gutschein');
        $orders->setCellValue('Y1', 'Gesamt');

        $orders->setCellValue('Z1', 'Datum');

        $orders->getStyle('A1:Z1')->getFont()->setBold(true);

        $row = 2;
        $rowArticles = 2;

        $articles->setCellValue('A1', 'Bestell-Nr.');
        $articles->setCellValue('B1', 'Artikelnummer');
        $articles->setCellValue('C1', 'Artikel');
        $articles->setCellValue('D1', 'Optionen');
        $articles->setCellValue('E1', 'Preis');
        $articles->setCellValue('F1', 'Anzahl');
        $articles->setCellValue('G1', 'Steuer');

        $articles->getStyle('A1:G1')->getFont()->setBold(true);

        $context = $this->contextFactory->create();

        foreach ($this->orderRepository->findAll() as $order) {

            $orders->setCellValue('A' . $row, $order->getOrdernumber());
            $orders->setCellValue('B' . $row, $this->invoiceRepository->findByOrdernumber((int)$order->getOrdernumber())?->getFirst()?->getInvoicenumber());

            $invoiceData = json_decode($order->getInvoicedata(), true);

            $orders->setCellValue('C' . $row, $invoiceData['company']);
            $orders->setCellValue('D' . $row, $invoiceData['firstname']);
            $orders->setCellValue('E' . $row, $invoiceData['lastname']);
            $orders->setCellValue('F' . $row, $invoiceData['address']);
            $orders->setCellValue('G' . $row, $invoiceData['zip']);
            $orders->setCellValue('H' . $row, $invoiceData['city']);
            $orders->setCellValue('I' . $row, $invoiceData['country']);
            $orders->setCellValue('J' . $row, $invoiceData['email']);
            $orders->setCellValue('K' . $row, $invoiceData['notes']);

            $orders->setCellValue('L' . $row, $invoiceData['shipping_company']);
            $orders->setCellValue('M' . $row, $invoiceData['shipping_firstname']);
            $orders->setCellValue('N' . $row, $invoiceData['shipping_lastname']);
            $orders->setCellValue('O' . $row, $invoiceData['shipping_address']);
            $orders->setCellValue('P' . $row, $invoiceData['shipping_zip']);
            $orders->setCellValue('Q' . $row, $invoiceData['shipping_city']);

            $orders->setCellValue('R' . $row, $context->getNodeByIdentifier($invoiceData['shipping'])?->getProperty('title'));
            $orders->setCellValue('S' . $row, $context->getNodeByIdentifier($order->getPayment())?->getProperty('title'));

            $summary = json_decode($order->getSummary(), true);

            $orders->setCellValue('T' . $row, number_format($summary['subtotal'], 2, ',', '.'));
            $orders->setCellValue('U' . $row, number_format($summary['tax'], 2, ',', '.'));
            $orders->setCellValue('V' . $row, number_format($summary['total_shipping'], 2, ',', '.'));
            $orders->setCellValue('W' . $row, number_format($summary['tax_shipping'], 2, ',', '.'));
            $orders->setCellValue('X' . $row, number_format($summary['discount'], 2, ',', '.'));
            $orders->setCellValue('Y' . $row, number_format($summary['total'], 2, ',', '.'));

            $orders->setCellValue('Z' . $row, $order->getCreated()->format('d.m.Y H:i'));

            $row++;

            foreach (json_decode($order->getCart(), true) as $article) {

                $articles->setCellValue('A' . $rowArticles, $order->getOrdernumber());
                $articles->setCellValue('B' . $rowArticles, $article['article_number']);
                $articles->setCellValue('C' . $rowArticles, $article['title']);

                $options = [];
                if (array_key_exists('options', $article)) {
                    foreach ($article['options'] as $option) {
                        $optionNode = $context->getNodeByIdentifier($option);
                        if ($optionNode !== null) {
                            $options[] = $optionNode->getProperty('title');
                        }
                    }
                }

                $articles->setCellValue('D' . $rowArticles, implode(', ', $options));

                $articles->setCellValue('E' . $rowArticles, number_format($article['price_gross'], 2, ',', '.'));
                $articles->setCellValue('F' . $rowArticles, $article['quantity']);
                $articles->setCellValue('G' . $rowArticles, number_format($article['tax'], 2, ',', '.'));
                $rowArticles++;
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $fileName = '/export-' . (new \DateTime())->format('Y-m-d-h-i-s') . '.xlsx';
        $exportTo = FLOW_PATH_WEB . $fileName;
        $writer->save($exportTo);
        return $fileName;
    }

}
