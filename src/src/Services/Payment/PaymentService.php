<?php

namespace App\Services\Payment;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use App\Form\PaymentType;
use App\Security\Voter\AccountVoter;
use App\Services\Invoice\InvoiceService;
use App\Services\JsonResponseMessage;
use App\Services\Order\OrderManager;
use App\Services\Order\OrderPackage;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class PaymentService implements PaymentInterface
{

  /**
   * @var Request
   */
  private $requestStack;

  /**
   * @var LyraService
   */
  private $payment;


  /**
   * @var FormFactoryInterface
   */
  private $formFactory;

  /**
   * @var ManagerResitry
   */
  private $doctrine;

  /**
   * @var OrderManager
   */
  private $orderManager;

  /**
   * @var OrderPackage
   */
  private $orderPackage;

  private $invoiceService;
  private $security;
  private $accountVoter;

  public function __construct(
    RequestStack $requestStack,
    LyraService $payment,
    ManagerRegistry $doctrine,
    OrderManager $orderManager,
    OrderPackage $orderPackage,
    InvoiceService $invoiceService,
    FormFactoryInterface $formFactory,
    Security $security,
    AccountVoter $accountVoter
  ) {
    $this->requestStack = $requestStack->getCurrentRequest();
    $this->payment = $payment;
    $this->formFactory = $formFactory;
    $this->doctrine = $doctrine;
    $this->orderManager = $orderManager;
    $this->orderPackage = $orderPackage;
    $this->invoiceService = $invoiceService;
    $this->security = $security;
    $this->accountVoter = $accountVoter;
  }


  public function initPayment(): JsonResponseMessage
  {
    $data = json_decode($this->requestStack->getContent(), true);

    $account = $this->doctrine->getRepository(Account::class)->findOneBy(['uuid' => $data['account_uuid']]);

    $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_MAKE_PAYMENT]);

    /**
     * @var Forfait
     */
    $forfait = $this->doctrine->getRepository(Forfait::class)->findOneBy(['uuid' => $data['forfait_uuid']]);
    if (!$forfait) {
      return (new JsonResponseMessage())->setError('Forfait Not Found!')->setCode(Response::HTTP_NOT_FOUND);
    }

    $canBuy = $this->orderManager->canBuyOrder($account, $forfait);

    if (!$canBuy) {
      return (new JsonResponseMessage())->setError('Cannot buy this pack!')->setCode(Response::HTTP_BAD_REQUEST);
    }


    $amountTTC = ($forfait->getPrice() * 100);
    $lyraObj = [
      'amount' => $amountTTC,
      'currency' => 'EUR',
      //"ipnTargetUrl" => getenv('APP_DOMAINE') . "/api/validatePayment",
      'orderId' => $this->orderPackage->getReference(),
      'customer' => [
        'reference' => $account->getUuid(),
        'email' => $account->getEmail(),
        'billingDetails' => [
          'name' => $account->getName(),
          'phoneNumber' => $account->getOwner()->getPhone(),
          'address' => $account->getAddress(),
          'zipCode' => $account->getPostalCode(),
          'city' => $account->getCountry(),
          'country' => 'FR',
          'language' => 'fr',
        ],
        'shoppingCart' => [
          'cartItemInfo' => [
            [
              'productLabel' => $forfait->getName(),
              'productRef' => $forfait->getUuid(),
              'productQty' => 1,
              'productAmount' => $forfait->getPrice(),
            ],
          ],
        ],
      ],
    ];

    $response = $this->payment->createPayment($lyraObj);

    if ($response['status'] !== 'SUCCESS') {
      $errorContent = $response['answer']['detailedErrorMessage'];
      $errorMessage = $response['answer']['errorMessage'];
      return (new JsonResponseMessage())->setContent($errorContent)->setError($errorMessage)->setCode(Response::HTTP_BAD_REQUEST);
    }

    return (new JsonResponseMessage())->setContent($response['answer']['formToken'])->setError('Token created successfully')->setCode(Response::HTTP_OK);
  }

  public function validatePayment(): void
  {
    $em = $this->doctrine->getManager();

    if (!$this->payment->checkHash()) {
      return;
    }

    /* Retrieve the IPN content */
    $rawAnswer = $this->payment->getParsedFormAnswer();
    $formAnswer = $rawAnswer['kr-answer'];
    $transaction = $formAnswer['transactions'][0];
    $orderDetails = $formAnswer['orderDetails'];
    $customer = $formAnswer['customer'];

    $forfait_uuid = $customer['shoppingCart']['cartItemInfo'][0]['productRef'];
    $account_uuid = $customer['reference'];
    $order_ref = $orderDetails['orderId'];

    $account = $this->doctrine->getRepository(Account::class)->findOneBy(['uuid' => $account_uuid]);
    $forfait = $this->doctrine->getRepository(Forfait::class)->findOneBy(['uuid' => $forfait_uuid]);


    if ($formAnswer['orderStatus'] === 'UNPAID') {
      return;
    }

    $order = $this->orderPackage->orderPack($forfait, $account, $order_ref);
    $this->orderManager->removeHybrideOrder($account);

    $arr_payment = [
      'status' => $formAnswer['orderStatus'],
      'amount' => $orderDetails['orderTotalAmount'] / 100,
      'transaction' => $transaction['uuid']
    ];

    $payment = new Payment();
    $payment->setOrder($order);
    $paymentForm = $this->formFactory->create(PaymentType::class, $payment);
    $paymentForm->submit($arr_payment);
    $em->persist($paymentForm->getData());
    $order->setPayments($payment);
    $em->persist($order);
    $fact =  $this->invoiceService->createInvoice($payment, $account, $forfait, $order);
    if ($fact) {
      $payment->setInvoices($fact);
      $em->persist($payment);
    }
    $em->flush();
  }
}
