<?php

namespace Drupal\commerce_rocketfuel\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the Rocketfuel payment gateway.
 */
interface RocketfuelIframeInterface extends OffsitePaymentGatewayInterface {

  /**
   * Get the Rocketfuel API Secret key set for the payment gateway.
   *
   * @return string
   *   The Rocketfuel API Secret key.
   */
  public function getPublicKey();

}
