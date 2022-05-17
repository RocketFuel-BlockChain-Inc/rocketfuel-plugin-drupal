<?php

namespace Drupal\commerce_rocketfuel\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class RocketfuelForm extends BasePaymentOffsiteForm
{
    use RocketFuelPaymentHelper;

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var Drupalcommerce_paymentEntityPaymentInterface $payment */
        $payment = $this->entity;

        $payment->save();

        /** @var Drupalcommerce_rocketfuelPluginCommercePaymentGatewayRocketfuelInterface $plugin */
        $plugin = $payment->getPaymentGateway()->getPlugin();

        /** @var Drupalcommerce_orderEntityOrderInterface $order */
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingProfile()->address->first();


        foreach($order->getItems() as $item){
            $options['cart'][] = [
                'id' => $item->getPurchasedEntityId(),
                'name' => $item->getTitle(),
                'price' => $item->getUnitPrice()->getNumber(),
                'quantity' => $item->getQuantity()
            ];
        }

        $data = [
            'cred' => [
                'email'=>$plugin->getEmail(),
                'password'=>$plugin->getPassword()
            ],
            'endpoint' => $this->getEndpoint($plugin->getEnvironment()),
            'body' => [
                'amount' => (string)$payment->getAmount()->getNumber(),
                'cart' => $options['cart'],
                'merchant_id' => $plugin->getMerchantId(),
                'currency' =>  $payment->getAmount()->getCurrencyCode(),
                'order' => (string)$payment->getOrderId(),
                'redirectUrl' => \Drupal::request()->getSchemeAndHttpHost().'/payment/notify/'.$payment->getPaymentGateway()->id()
            ]
        ];

        $options = [
            "merchant_auth" => $this->getMerchantAuth($plugin->getMerchantId()),
            "amount" => /*$payment_amount*/$payment->getAmount()->getNumber(),
            "customer_email" => $order->getEmail(),
            "customer_firstname" => $billingAddress->getGivenName(),
            "customer_lastname" => $billingAddress->getFamilyName(),
            "uuid" => $this->getUUID($data),
            "redirect_url" => $form['#return_url'],
            "environment"=>$plugin->getEnvironment(),
            "notifyUrl" => \Drupal::request()->getSchemeAndHttpHost().'/payment/notify/'.$payment->getPaymentGateway()->id()
        ];

        $options['continueurl'] = $form['#return_url'];
        //$options['cancelurl'] = $form['#cancel_url'];
        //$payment->save();
        $options['payment_id'] = $payment->id();


        $form['#attached']['drupalSettings']['rocketfuel'] = json_encode($options);
        $form['#attached']['library'][] = 'commerce_rocketfuel/checkout';


        //$form = $this->buildRedirectForm($form, $form_state, '', $options, '');
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
}
