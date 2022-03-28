<?php

namespace Drupal\commerce_rocketfuel\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class RocketfuelIframeForm extends BasePaymentOffsiteForm
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        /** @var \Drupal\commerce_rocketfuel\Plugin\Commerce\PaymentGateway\RocketfuelIframeInterface $plugin */
        $plugin = $payment->getPaymentGateway()->getPlugin();
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

        // Initialize a transaction.
        // $rocketfuel = new Rocketfuel($plugin->getSecretKey());
        // try {
        //   $responseObj = $rocketfuel->transaction->initialize($transactionData);
        // }
        // catch (Exception $e) {
        //   throw new PaymentGatewayException($e->getMessage());
        // }

        $redirectUrl = 'Here it is on it';
        $order->setData('rocketfuel_iframe', [
            'reference' => 'TEst',
            'access_code' => 'TEst',
            'authorization_url' => 'Test',
        ]);
        $order->save();

        $data = [
            'return' => $form['#return_url'],
            'cancel' => $form['#cancel_url'],
            'total' => $payment->getAmount()->getNumber(),
        ];

        // $redirectMethod = BasePaymentOffsiteForm::REDIRECT_GET;
        $form = $this->buildRedirectForm($form, $form_state, $redirectUrl, $data, $redirectMethod);

        return $form;
    }
}
