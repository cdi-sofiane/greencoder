<?php

namespace App\Controller\Api;

use App\Services\Invoice\InvoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Security;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\Invoice;



/**
 * @Route("/", name="Invoice_")
 *
 */
class InvoiceController extends AbstractController
 {


  /**
   * @var InvoiceService
   */
  private $invoice;


  public function __construct(InvoiceService $invoice)
  {
    $this->invoice = $invoice;
  }

     /**
     * @Route ("invoices/{invoice_number}",name="getOneInvoice",methods={"Get"})
     * @OA\Get (
     *     tags={"Invoice"},
     *     summary="get invoice by invoice-number",
     *     description="fetch list of facutres by given user uuid",
     * )
     * @OA\Response(response=200,description="Return invoices",
     *     @OA\JsonContent(type="array",@OA\Items(ref=@Model(type=Invoice::class,groups={"invoice:list"})),
     *     )
     * )
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="404",description="Not Found",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Invoice Not Found"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function getOneInvoice($invoice_number)
    {
      $data =  $this->invoice->getOneInvoice($invoice_number);
      return new JsonResponse($data->displayData(), $data->displayHeader());
    }

   /**
     * @Route ("accounts/{account_uuid}/invoices",name="_account_invoices",methods={"Get"})
     * @OA\Get (
     *     tags={"Invoice"},
     *     summary="get invoices by account",
     *     description="fetch list of invoices by given account uuid",
     * @OA\Parameter (name="search",in="query",description="name",@OA\Schema(type="string")),
     *      @OA\Parameter (name="page",in="query",description="default page=1",@OA\Schema(type="integer")),
     *      @OA\Parameter (name="limit",in="query",description="default  limit=12",@OA\Schema(type="integer")),
     * )
     * @OA\Response(response=200,description="Return invoices",
     *     @OA\JsonContent(type="array",@OA\Items(ref=@Model(type=Invoice::class,groups={"invoice:list"})),
     *     )
     * )
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="404",description="Not Found",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="User Not Found"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function getInvoices($account_uuid)
    {
      $data =  $this->invoice->getInvoicesByAccount($account_uuid);
      return new JsonResponse($data->displayData(), $data->displayHeader());

    }

 }