<?php

namespace Drupal\commerce_rocketfuel\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class RocketfuelForm extends BasePaymentOffsiteForm
{
    protected $integrityHash;
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var Drupalcommerce_paymentEntityPaymentInterface $payment */
        $payment = $this->entity;
        /** @var Drupalcommerce_rocketfuelPluginCommercePaymentGatewayRocketfuelInterface $plugin */
        $plugin = $payment->getPaymentGateway()->getPlugin();

        /** @var Drupalcommerce_orderEntityOrderInterface $order */
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingProfile()->address->first();


        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $uuid = $user->uuid();

        $options = [
            "merchant_auth" => $this->getMerchantAuth($plugin->getPublicKey(), $plugin->getMerchantId()),
            "amount" => /*$payment_amount*/$payment->getAmount()->getNumber(),
            "customer_email" => $order->getEmail(),
            "customer_firstname" => $billingAddress->getGivenName(),
            "customer_lastname" => $billingAddress->getFamilyName(),
            "orderId" => $payment->getOrderId(),
            "uuid" => $uuid,
            "country" => $billingAddress->getCountryCode(),
            "currency" => $payment->getAmount()->getCurrencyCode(),
            "redirect_url" => $form['#return_url'],
            "endpoint"=>$this->getEndpoint($plugin->getEnvironment()),
            "environment"=>$plugin->getEnvironment(),
        ];

        $options['continueurl'] = $form['#return_url'];
        $options['cancelurl'] = $form['#cancel_url'];

        //$this->calculateChecksum($options);

        //$options = array_merge($options, ['integrity_hash' => $this->integrityHash]);

        $form['#attached']['drupalSettings']['rocketfuel'] = json_encode($options);
        $form['#attached']['library'][] = 'commerce_rocketfuel/checkout';


        /*$form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Accept instalments and complete purchase'),
        ];
        $form['actions']['cancel'] = [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => Url::fromUri($form['#cancel_url']),
        ];*/

        $form = $this->buildRedirectForm($form, $form_state, '', $options, '');
        return $form;
    }
    /**
     * {@inheritdoc}
     */
    public static function processRedirectForm(array $element, FormStateInterface $form_state, array &$complete_form)
    {
        $complete_form['#attributes']['class'][] = 'payment-redirect-form';
        unset($element['#action']);
        // The form actions are hidden by default, but needed in this case.
        $complete_form['actions']['#access'] = TRUE;
        foreach (Element::children($complete_form['actions']) as $element_name) {
            $complete_form['actions'][$element_name]['#access'] = TRUE;
        }

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = BasePaymentOffsiteForm::REDIRECT_GET)
    {
        // if (array_key_exists('hosted_payment', $data) && $data['hosted_payment'] === 1) {
        //     $helpMessage = t('Please wait while you are redirected to the payment server. If nothing happens within 10 seconds, please click on the button below.');
        // } else {
        $helpMessage = t('Please wait while the payment server loads. If nothing happens within 10 seconds, please click on the button below.');
        // }

        $form['commerce_message'] = [
            '#markup' => '<div class="checkout-help">' . $helpMessage . '</div>',
            '#weight' => -10,
            // Plugin forms are embedded using #process, so it's too late to attach
            // another #process to $form itself, it must be on a sub-element.
            '#process' => [
                [get_class($this), 'processRedirectForm'],
            ],
        ];

        return $form;
    }

    /**
     * Calculate Checksum of Rave Payload.
     *
     * For more: https://flutterwavedevelopers.readme.io/docs/checksum.
     */
    protected function calculateChecksum(array $options)
    {
        ksort($options);

        $hashedPayload = '';

        foreach ($options as $key => $value) {
            $hashedPayload .= $value;
        }

        /** @var Drupalcommerce_ravePluginCommercePaymentGatewayRocketfuelInterface $plugin */
        $plugin = $this->plugin;

        $completeHash = $hashedPayload . $plugin->getPassword();
        $hash = hash('sha256', $completeHash);

        $this->integrityHash = $hash;
    }

    private function getMerchantAuth($public_key, $merchant_id)
    {
        $out = "";
        $cert = $public_key;
        $to_crypt = $merchant_id;

        $public_key = openssl_pkey_get_public($cert);

        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;

        $parts = str_split($to_crypt, $part_len);

        foreach ($parts as $part) {

            $encrypted_temp = '';

            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);

            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }

    public function getEndpoint($environment)
    {
        $environmentData = [
            'prod' => 'https://app.rocketfuelblockchain.com/api',
            'dev' => 'https://dev-app.rocketdemo.net/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
        ];

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://qa-app.rocketdemo.net/api';
    }
}
