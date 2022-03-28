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

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        /** @var \Drupal\commerce_rocketfuel\Plugin\Commerce\PaymentGateway\RocketfuelInterface $plugin */
        $plugin = $payment->getPaymentGateway()->getPlugin();
        $environment = $plugin->getEnvironment();

        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $payment->getOrder();

        // Adds information about the billing profile.
        if ($billing_profile = $order->getBillingProfile()) {
            /** @var \Drupal\address\AddressInterface $address */
            $address = $billing_profile->get('address')->first();
            $fields = [
                [
                    'display_name' => 'Billing First Name',
                    'variable_name' => 'first_name',
                    'value' => $address->getGivenName(),
                ],
                [
                    'display_name' => 'Billing Surname',
                    'variable_name' => 'last_name',
                    'value' => $address->getFamilyName(),
                ]
            ];
        }

        // Get total order price.
        $amount = $payment->getAmount();

        // $transactionData = [
        //   'reference' => $order->uuid(),
        //   'amount' => $amount->getNumber() * 100, // Convert to kobo.
        //   'email' => $order->getEmail(),
        //   'callback_url' => $form['#return_url'],
        //   'metadata' => [
        //     'cancel_action' => $form['#cancel_url'],
        //   ],
        // ];
        $transactionData = [
            'reference' => 'ddddddd',
            'amount' => 100, // Convert to kobo.
            'email' => 're@gmail.com',
            'callback_url' => $form['#return_url'],
            'metadata' => [
                'cancel_action' => $form['#cancel_url'],
            ],
        ];
        if (isset($fields)) {
            $transactionData['metadata']['custom_fields'] = $fields;
        }
        // if ($gateway_mode == 'live') {
        //     $form['#attached']['library'][] = 'commerce_rave/rave_live';
        //   }
        //   else {
        //     $form['#attached']['library'][] = 'commerce_rave/rave_staging';
        //   }

        //   $form['#attached']['library'][] = 'commerce_rave/rave';

        $options = [
            "PBFPubKey" => $plugin->getPublicKey(),
            "amount" => $payment_amount,
            "customer_email" => $order->getEmail(),
            "customer_firstname" => $billingAddress->getGivenName(),
            "customer_lastname" => $billingAddress->getFamilyName(),
            "custom_logo" => Url::fromUri('internal:' . theme_get_setting('logo.url'), ['absolute' => TRUE])
                ->toString(),
            "txref" => $plugin->getTransactionReferencePrefix() . '-' . $payment->getOrderId(),
            "payment_method" => 'both',
            "country" => $billingAddress->getCountryCode(),
            "currency" => $payment->getAmount()->getCurrencyCode(),
            "custom_title" => \Drupal::config('system.site')->get('name'),
            "custom_description" => \Drupal::config('system.site')->get('slogan'),
            "pay_button_text" => $plugin->getPayButtonText(),
            "redirect_url" => $form['#return_url'],
        ];



        $form = $this->buildRedirectForm($form, $form_state, '', $options, '');

        $this->calculateChecksum($options);

        $options = array_merge($options, ['integrity_hash' => $this->integrityHash]);

        $form['#attached']['drupalSettings']['rocketfuel']['transactionData'] = json_encode($options);

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

        /** @var \Drupal\commerce_rave\Plugin\Commerce\PaymentGateway\RocketfuelInterface $plugin */
        $plugin = $this->plugin;

        $completeHash = $hashedPayload . $plugin->getPassword();
        $hash = hash('sha256', $completeHash);

        $this->integrityHash = $hash;
    }
}
