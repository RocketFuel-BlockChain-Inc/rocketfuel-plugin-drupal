<?php

namespace Drupal\commerce_rocketfuel\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Rocketfuel Standard Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "rocketfuel",
 *   label = "Rocketfuel Standard (Off-site)",
 *   display_label = "Pay with Rocketfuel",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_rocketfuel\PluginForm\RocketfuelForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class Rocketfuel extends OffsitePaymentGatewayBase implements RocketfuelInterface
{

    /**
     * {@inheritdoc}
     */
    public function getPublicKey()
    {
        return $this->configuration['public_key'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantId()
    {
        return $this->configuration['merchant_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->configuration['password'];
    }
    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->configuration['email'];
    }
    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->configuration['environment'];
    }
    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
                'public_key' => '',
                'merchant_id' => '',
                'password' => '',
                'email' => '',
                'environment' => 'dev'
            ] + parent::defaultConfiguration();
    }
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['public_key'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Public Key'),
            '#default_value' => $this->getPublicKey(),
            '#required' => TRUE,
        ];

        $form['merchant_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Merchant ID'),
            '#default_value' => $this->getMerchantId(),
            '#required' => TRUE,
        ];


        $form['password'] = [
            '#type' => 'password',
            '#title' => $this->t('Password'),
            '#default_value' => $this->getPassword(),
            '#required' => TRUE,
        ];
        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#default_value' => $this->getEmail(),
            '#required' => TRUE,
        ];
        $form['environment'] = [
            '#type' => 'select',
            '#title' => $this->t('Environment'),
            '#default_value' => $this->getEnvironment(),
            '#options' => [
                'prod' => $this
                    ->t('Production'),
                'dev' => $this
                    ->t('Development'),
                'preprod' => $this
                    ->t('Pre Production'),
                'stage2' => $this
                    ->t('QA'),
            ],
            '#required' => TRUE,
        ];

        return $form;
    }
    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);
        // Validate the secret key.
        if (!$form_state->getErrors()) {

            $values = $form_state->getValue($form['#parents']);

            $public_key = $values['public_key'];

            if (!is_string($public_key)) {

                $form_state->setError($form['public_key'], $this->t('A Valid rocketfuel Secret Key is needed'));

            }

            //validate the merchant id
        }
    }
    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['public_key'] = $values['public_key'];
            $this->configuration['merchant_id'] = $values['merchant_id'];
            $this->configuration['password'] = $values['password'];
            $this->configuration['email'] = $values['email'];
            $this->configuration['environment'] = $values['environment'];
        }
    }

    public function onCancel(OrderInterface $order, Request $request) {
        $this->messenger()->addMessage($this->t('You have canceled checkout at RocketFuel but may resume the checkout process here when you are ready.', [
            '@gateway' => $this->getDisplayLabel(),
        ]));
    }
}
