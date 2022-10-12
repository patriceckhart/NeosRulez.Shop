<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Swift_Attachment;

/**
 * Class Mail
 *
 * @Flow\Scope("singleton")
 */
class MailService {

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
     * @var \NeosRulez\Shop\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Repository\InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

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
     * @param array $args
     * @return void
     */
    public function execute($args) {
        $variables['args'] = $args;
        $variables['items'] = $this->cart->cart();
        $variables['payment_service'] = $this->paymentService->getPaymentByIdentifier($args['payment']);
        $variables['url'] = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        $send_invoice = intval($args['cart_variables']['invoice']);
        if($this->settings['Mail']['debugMode']) {
            $variables['debug'] = true;
            return $this->send($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $args['email']) => $args['firstname'].' '.$args['lastname']], $args);
        } else {
            if(strpos($args['cart_variables']['recipient_mail'], ',') !== false) {
                $recipients = explode(',', $args['cart_variables']['recipient_mail']);
                $valid_recipient = [];
                foreach ($recipients as $recipient) {
                    $valid_recipient[$recipient] = $recipient;
                }
                $this->send($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $args['email']) => $args['firstname'].' '.$args['lastname']], $args);
                $this->send($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], $valid_recipient, $args);
                if($send_invoice) {
                    $this->sendInvoice($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $args['email']) => $args['firstname'] . ' ' . $args['lastname']], $args, true, $valid_recipient);
                }
            } else {
                $this->send($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $args['email']) => $args['firstname'].' '.$args['lastname']], $args);
                $this->send($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [$args['cart_variables']['recipient_mail'] => $args['cart_variables']['recipient_mail']], $args);
                if($send_invoice) {
                    $this->sendInvoice($variables, $args['cart_variables']['order_subject'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $args['email']) => $args['firstname'] . ' ' . $args['lastname']], $args, true, [$args['cart_variables']['recipient_mail'] => $args['cart_variables']['recipient_mail']]);
                }
            }
        }
    }

    public function send($variables, $subject, $sender, $recipient, $args) {
        $variables['args']['cart_variables']['taxcart'] = $this->settings['tax'];
        $view = new \Neos\FluidAdaptor\View\StandaloneView();
        $view->setTemplatePathAndFilename($this->settings['Mail']['templatePathAndFilename']);
        $view->assignMultiple($variables);
        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom($sender)
            ->setTo($recipient)
            ->setSubject($subject);
        $mail->setBody($view->render(), 'text/html');

        if($this->settings['Mail']['debugMode']) {
            return $view->render();
        } else {
            $mail->send();
        }
    }

    public function sendInvoice($variables, $subject, $sender, $recipient, $args, $create, $bcc) {
        $view = new \Neos\FluidAdaptor\View\StandaloneView();
        $view->setTemplatePathAndFilename($this->settings['Invoice']['Mail']['templatePathAndFilename']);
        $view->assignMultiple($variables);

        $pdf = $this->invoiceService->execute($args, $create);
        $prefix = $args['cart_variables']['invoice_number_prefix'];
        $start = intval($args['cart_variables']['invoice_number']);

        $fiscalYearStart = '01-01';
        $fiscalYearEnd = '12-31';
        if(array_key_exists('fiscal_year_start', $args['cart_variables']) && array_key_exists('fiscal_year_end', $args['cart_variables'])) {
            $fiscalYearStart = $args['cart_variables']['fiscal_year_start'];
            $fiscalYearEnd = $args['cart_variables']['fiscal_year_end'];
        }
        $invoice_number = $prefix . $this->invoiceRepository->countInvoices($start, $fiscalYearStart, $fiscalYearEnd);

        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom($sender)
            ->setTo($recipient)
            ->setBcc($bcc)
            ->setSubject($invoice_number . ' Invoice');
        $mail->setBody($view->render(), 'text/html');

        $attachment = new \Swift_Attachment($pdf, $invoice_number . '_Invoice.pdf', 'application/pdf');
        $mail->attach($attachment);

        if($this->settings['Invoice']['debugMode']) {
            return $view->render();
        } else {
            $mail->send();
        }
    }

}
