<?php

namespace App\Services\Invoice;

use App\Entity\Invoice;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use App\Services\JsonResponseMessage;

interface InvoiceInterface
{

  /**
   * Generate pdf invoice by given payment details
   *
   * @param [type] $data
   * @return void
   */
  public function generateInvoice($data): void;

  /**
   * call generateInvoice to create invoice and save in db
   *
   * @param Payment $payment
   * @param User $user
   * @param Forfait $forfait
   * @param Order $order
   * @return Invoice|null
   */
  public function createInvoice(Payment $payment, User $user, Forfait $forfait, Order $order): ?Invoice;


  /**
   * get one invoice by given invoce number
   *
   * @param string $invoce_number
   * @return JsonResponseMessage
   */
  public function getOneInvoice(string $invoce_number): JsonResponseMessage;


  /**
   * get all invoices by given user uuid
   *
   * @param string $user_uuid
   * @return JsonResponseMessage
   */
  public function getInvoicesByUser(string $user_uuid): JsonResponseMessage;


}
