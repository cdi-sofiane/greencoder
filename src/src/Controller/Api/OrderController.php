<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Services\Order\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/", name="order_")
 *
 */
class OrderController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * @Route ("/orders",name="pack",methods={"POST"})
     * @OA\Post (
     *  tags={"Orders"},
     *  summary="Subscribe user to a Pack create an order ",
     *  description="Subscribe an user with an active package encodage,storage fo user to be able to encode and/or store videos",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"user_uuid","package_uuid"},
     *              @OA\Property (property="package_uuid",description="pack unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
     *              @OA\Property (property="account_uuid",description="user unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
     * )),
     *      @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"user_uuid","package_uuid"},
     *              @OA\Property (property="package_uuid",description="pack unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
     *              @OA\Property (property="account_uuid",description="user unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
     * ))
     * )
     * ),
     * @OA\Response(response=200,description="Success",
     *     @OA\JsonContent(type="array",@OA\Items(ref=@Model(type=Order::class,groups={"list_of_order"})))
     * )
     * @OA\Response(response="204",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="204"),
     *              @OA\Property( property="message",example="Object empty"),
     *
     *     ),),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="422",description="Unprocessable Entity",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="[{'fields':'email'},{'types':['This value should not be blank.','This value is not a valid email address.']}]"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function orderPackage(Request $request, OrderManager $orderManager)
    {

        $data = $orderManager->subscribeOrder();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/orders",name="list",methods={"GET"})
     * @OA\Get(
     *  tags={"Orders"},
     *  summary="Find Orders ",
     *  description="Find an existing order depending of filtered params",
     *      @OA\Parameter (name="account_uuid",in="query",description="account uuid",@OA\Schema(type="string")),
     *      @OA\Parameter (name="package_uuid",in="query",description="package uuid",@OA\Schema(type="string")),
     *      @OA\Parameter (name="page",in="query",description="select a page default page=1",@OA\Schema(type="integer")),
     *      @OA\Parameter (name="limit",in="query",description="number of item in a page default=12",@OA\Schema(type="integer")),
     *      @OA\Parameter (name="isConsumed",in="query",@OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="expireAt",in="query",description="order expiration",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *       @OA\Property (property="nature",type="string",enum={"encodage", "stockage","hybride"},default="encodage"),
     *      @OA\Property (property="type",type="string",enum={"Gratuit", "OneShot", "Credit", "Abonnement"} ,default="Abonnement")
     * )
     * )
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *              type="array",@OA\Items(ref="#/components/schemas/Order"))
     * )
     * @OA\Response(response="204",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="204"),
     *              @OA\Property( property="message",example="Object empty"),
     *
     *     ),),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="422",description="Unprocessable Entity",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="[{'fields':'email'},{'types':['This value should not be blank.','This value is not a valid email address.']}]"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     *
     */
    public function find_order(Request $request, OrderManager $orderManager)
    {
        $data = $orderManager->findall($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("orders/{order_uuid}",name="edit",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Orders"},
     *  summary="Disable Orders ",
     *  description="Set an existing order to consumed ,remove credits from user",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="isConsumed",type="boolean"),
     *      )),
     *     ),
     * ),
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *               @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Order has been discarded !")),
     * )
     * @OA\Response(response="204",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="204"),
     *              @OA\Property( property="message",example="Object empty"),
     *
     *     ),),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @IsGranted("ROLE_DEV",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function edit(Request $request, OrderManager $orderManager)
    {
        /* put isConsumed to true and substract if there is credit from user->credit items*/
        $data = $orderManager->orderDisociate($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/orders/{order_uuid}/renewable",name="renewable",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Orders"},
     *  summary="Enable or disable order renewal ",
     *  description="Enable or disable the automatic renewal of an order can be changed untill 1 month befor the end of the selected order",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="isRenewable",type="boolean"),
     *      )),
     *     ),
     * ),
     * )
     * * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *               @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success")),
     * )
     * @OA\Response(response="404",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="message",example="Order('s) not found"),
     *
     *     ),),
     * @OA\Response(response="422",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="message",example="Can't modify order anymore"),
     *
     *     ),),
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function renewable(Request $request, OrderManager $orderManager)
    {

        $data = $orderManager->orderRenewable($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
    /**
     * @Route("/orders/{order_uuid}/swap",name="_swap",methods={"POST"})
     * @OA\Post  (
     *  tags={"Orders"},
     *  summary="Upgrade or Downgrade order ",
     *  description="Upgrade or downgrade depending of the targeted Package ressource if
     *   package ressource >= current order ressource then upgrade else downgrade
     *   (downgrade possiblbe only 1 month before expriration date), can't downgrad a
     *   storage order if the total of stored encoded videos ",
     *  @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="package_uuid",description="pack unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410" ),
     *      )),
     *     ),
     * ),
     * )
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success")),
     * )
     * @OA\Response(response="404",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="message",example="Order('s) not found"),
     *
     *     ),),
     * @OA\Response(response="422",description="No content",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="message",example="Can't modify order anymore"),
     *
     *     ),),
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @IsGranted({"ROLE_USER"},message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function swap(Request $request, OrderManager $orderManager)
    {

        $data = $orderManager->orderSwap($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
