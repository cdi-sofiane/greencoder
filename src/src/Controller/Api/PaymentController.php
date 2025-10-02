<?php

namespace App\Controller\Api;

use App\Services\Payment\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Security;

/**
 * @Route("/payments", name="Payment_")
 *
 */
class PaymentController extends AbstractController
{

  /**
   * @var PaymentService
   */
  private $payment;

  public function __construct(PaymentService $payment)
  {
    $this->payment = $payment;
  }

  /**
   * @Route ("/init",name="init_payment",methods={"POST"})
   * @OA\Post (
   *     tags={"Payment"},
   *     summary="initialize payment form",
   *     description="fetch formtoken to display payment form",
   *
   *     @OA\RequestBody(
   *      @OA\MediaType(mediaType="application/json",
   *          @OA\Schema(
   *              required={"account_uuid",
   *                        "forfait_uuid"
   *              },
   *              @OA\Property (property="account_uuid",description="account uuid ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
   *              @OA\Property (property="forfait_uuid",description="forfait uuid ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
   * )),
   *))
   * @OA\Response(response=200,description="Success", @OA\JsonContent(type="string"))
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
  public function initPayment()
  {
    $data =  $this->payment->initPayment();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route ("/validate",name="validate_payment",methods={"Post"})
   * @OA\Post (
   *     tags={"Payment"},
   *     summary="Callback IPN lyra",
   *     description="callback api to fetch response from lyra",
   * )
   */
  public function validatePayment()
  {
    $data =  $this->payment->validatePayment();
    return new JsonResponse($data);
  }
}
