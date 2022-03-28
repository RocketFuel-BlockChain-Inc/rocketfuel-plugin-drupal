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
 *   id = "rocketfuel_iframe",
 *   label = "Rocketfuel Standard (Off-site)",
 *   display_label = "Pay with Rocketfuel",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_rocketfuel\PluginForm\RocketfuelIframeForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class RocketfuelIframe extends OffsitePaymentGatewayBase implements RocketfuelIframeInterface
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
            'password' => '',
            'email' => '',
            'environment' => ''
        ] + parent::defaultConfiguration();
    }
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['public_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Public Key'),
            '#default_value' => $this->getPublicKey(),
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
            '#type' => 'textfield',
            '#title' => $this->t('Environment'),
            '#default_value' => $this->getEnvironment(),
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
        }
    }
}
