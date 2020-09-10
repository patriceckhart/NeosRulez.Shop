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
    public function execute($args) {
//        $send_invoice = intval($args['cart_variables']['invoice']);
        $variables['args'] = $args;
        $variables['items'] = $this->cart->cart();
        $variables['payment_service'] = $this->paymentService->getPaymentByIdentifier($args['payment']);
        $variables['url'] = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];
        if($this->settings['Mail']['debugMode']) {
            $variables['debug'] = true;
            return $this->send($variables, $args['cart_variables']['order_subject'], $this->settings['Mail']['senderMail'], [$args['email'] => $args['firstname'].' '.$args['lastname']]);
        } else {
            if(strpos($args['cart_variables']['recipient_mail'], ',') !== false) {
                $recipients = explode(',', $args['cart_variables']['recipient_mail']);
                foreach ($recipients as $recipient) {
                    $valid_recipient[$recipient] = $recipient;
                }
                $this->send($variables, $args['cart_variables']['order_subject'], $this->settings['Mail']['senderMail'], [$args['email'] => $args['firstname'].' '.$args['lastname']], $args);
                $this->send($variables, $args['cart_variables']['order_subject'], [$args['email'] => $args['firstname'].' '.$args['lastname']], $valid_recipient, $args);
            } else {
                $this->send($variables, $args['cart_variables']['order_subject'], $this->settings['Mail']['senderMail'], [$args['email'] => $args['firstname'].' '.$args['lastname']], $args);
                $this->send($variables, $args['cart_variables']['order_subject'], [$args['email'] => $args['firstname'].' '.$args['lastname']], [$args['cart_variables']['recipient_mail'] => $args['cart_variables']['recipient_mail']], $args);
            }
        }
    }

    public function send($variables, $subject, $sender, $recipient, $args) {
        $view = new \Neos\FluidAdaptor\View\StandaloneView();
        $view->setTemplatePathAndFilename($this->settings['Mail']['templatePathAndFilename']);
        $view->assignMultiple($variables);
        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom($sender)
            ->setTo($recipient)
            ->setSubject($subject);
        $mail->setBody($view->render(), 'text/html');

        $send_mail = intval($args['cart_variables']['invoice']);

        if($send_mail) {
            $pdf = $this->invoiceService->execute($args);
            $prefix = $args['cart_variables']['invoice_number_prefix'];
            $start = intval($args['cart_variables']['invoice_number']);
            $invoice_number = $prefix . $this->invoiceRepository->countInvoices($start);
            $attachment = new \Swift_Attachment($pdf, $invoice_number . '_Invoice.pdf', 'application/pdf');
            $mail->attach($attachment);
        }

        if($this->settings['Mail']['debugMode']) {
            return $view->render();
        } else {
            $mail->send();
        }
    }

}
