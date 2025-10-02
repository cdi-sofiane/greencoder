<?php

namespace App\Services\Invoice;

use App\Entity\Account;
use App\Entity\Invoice;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\InvoiceRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\InvoiceVoter;
use App\Services\DataFormalizerResponse;
use App\Services\JsonResponseMessage;
use App\Services\Storage\S3Storage;
use App\Utils\FileUtils;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;


class InvoiceService
{

  const TVA = 20;
  /**
   * @var Pdf
   */
  private $pdf;

  /**
   * @var Environment
   */
  private $twig;

  /**
   * @var S3Storage
   */
  private $storage;

  /**
   * @var ManagerRegistry
   */
  private $doctrine;

  /**
   * @var PaginatorInterface
   */
  private $paginator;

  /**
   * @var DataFormalizerResponse
   */
  private $formalizer;

  /**
   * @var Request
   */
  private $request;

  /**
   * @var InvoiceRepository
   */
  private $invoiceRepository;

  private $invoiceStorage;

  private $link;

  private $fileUtils;

  private $security;

  private $accountVoter;

  private $invoiceVoter;

  public function __construct(
                              Pdf $pdf,
                              Environment $twig,
                              S3Storage $storage,
                              ManagerRegistry $doctrine,
                              DataFormalizerResponse $formalizer,
                              RequestStack $request,
                              FileUtils $fileUtils,
                              InvoiceRepository $invoiceRepository,
                              PaginatorInterface $paginator,
                              Security $security,
                              AccountVoter $accountVoter,
                              InvoiceVoter $invoiceVoter)
                               {
    $this->pdf = $pdf;
    $this->twig = $twig;
    $this->storage = $storage;
    $this->doctrine = $doctrine;
    $this->paginator = $paginator;
    $this->formalizer = $formalizer;
    $this->invoiceRepository = $invoiceRepository;
    $this->request = $request->getCurrentRequest();
    $this->invoiceStorage =  $_ENV['PUBLIC_FACTURE_STORAGE_NAME'];
    $this->link = $_ENV['PUBLIC_FACTURE_STORAGE_LINK'];
    $this->fileUtils = $fileUtils;
    $this->security = $security;
    $this->accountVoter = $accountVoter;
    $this->invoiceVoter = $invoiceVoter;

    $this->pdf->setOptions([
      'page-width' => "800px",
      'page-height' => "1080px",
      "print-media-type" => true,
      "enable-smart-shrinking" => true,
      "enable-javascript" => true
    ]);
  }

  public function generateInvoice($data): void
  {
    $local = $this->pdf->getTemporaryFolder() . '/' . $data['name'];
    $this->pdf->generateFromHtml(
      $this->twig->render(
        'invoice/invoice.html.twig',
        ['data' => $data]
      ),
      $local
    );
    $this->storage->uploadPdf($this->invoiceStorage, $data['name']);
    unlink($local);
  }

  public function createInvoice(Payment $payment, Account $account, Forfait $forfait, Order $order): ?Invoice
  {
    $em = $this->doctrine->getManager();

    $fileName =  $this->fileUtils->slugify($forfait->getName()) . '_' . $order->getReference() . '.pdf';
    $amountHT = $this->calculPrixHT($forfait->getPrice(), self::TVA);
    $arr_invoice = [
      'reference' => $order->getReference(),
      'created_at' => $order->getCreatedAt(),
      'expire_at' => $order->getExpireAt(),
      'invoiceNumber' => 'GE' . $order->getReference(),
      'name' => $fileName,
      'customer' => [
        'name' => $account->getUsages() === 'Professional' ? $account->getCompany() : $account->getName(),
        'address' => $account->getAddress(),
        'company' => $account->getCompany(),
        'country' => $account->getCountry(),
        'zipCode' => $account->getPostalCode(),
      ],
      'forfait' => [
        'name' => $forfait->getName(),
        'type' => $forfait->getType(),
        'nature' => $forfait->getNature(),
        'priceTTC' => $forfait->getPrice(),
        'priceHT' => $amountHT,
        'tva' => $forfait->getPrice() - $amountHT,
      ],
    ];
    $this->generateInvoice($arr_invoice);
    $invoice = new Invoice();
    $invoice->setPayments($payment)
      ->setAccount($account)
      ->setInvoiceNumber($arr_invoice['invoiceNumber'])
      ->setDownloadLink($this->link . $arr_invoice['name']);
    $em->persist($invoice);
    $em->flush();
    return $invoice;
  }

  public function getOneInvoice(string $invoice_number): JsonResponseMessage
  {
    /**
     * @var Invoice
     */
    $invoice = $this->invoiceRepository->findOneBy(['invoiceNumber' => $invoice_number]);

    $this->invoiceVoter->vote($this->security->getToken(), $invoice, [InvoiceVoter::FIND_INVOICE]);

    return $this->formalizer->extract($invoice, 'invoice:list', false, 'Success', Response::HTTP_OK);
  }

  public function getInvoicesByAccount(string $account_uuid): JsonResponseMessage
  {
    $page = $this->request->query->getInt("page");
    $limit = $this->request->query->getInt("limit");

    $filters['page'] =  $page === 0 ? 1 : $page;
    $filters['limit'] =  $limit === 0 ? 12 : $limit;
    $filters['search'] = $this->request->query->get("search");

    $account = $this->doctrine->getRepository(Account::class)->findOneBy(['uuid' => $account_uuid]);

    $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_INVOICE]);

    $invoices = $this->invoiceRepository->findInvoices($account, $filters);

    return $this->formalizer->extract($invoices, 'invoice:list', true, 'Success', Response::HTTP_OK, $filters);
  }

  public function calculPrixHT($amountTTC, $tva)
  {
    return round($amountTTC / (1 + $tva/100), 2);
  }
}
