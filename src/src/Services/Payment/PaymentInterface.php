<?php

namespace App\Services\Payment;

use App\Services\JsonResponseMessage;

interface PaymentInterface
{
  /**
   * initialize payment form 
   *
   * @return JsonResponseMessage
   */
  public function initPayment();

  /**
   * callback function to check the payment object
   *
   * @return void
   */
  public function validatePayment();
}