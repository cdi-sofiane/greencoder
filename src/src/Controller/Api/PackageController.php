<?php

namespace App\Controller\Api;

use App\Entity\Forfait;
use App\Services\Forfait\ForfaitManager;
use App\Services\Users\UserUpdateValidator;
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
 * @Route("/", name="package_")
 *
 */
class PackageController extends AbstractController
{
    /**
     * @Route("/packages",name="list",methods={"GET"})
     * @OA\Get(
     *  tags={"Packages"},
     *  summary="Find and filter package ",
     *     description="Find package with filtered params depend of level right from user",
     *      @OA\Parameter (name="search",in="query",description="name",@OA\Schema(type="string")),
     *      @OA\Parameter (name="page",in="query",description="default page=1",@OA\Schema(type="integer")),
     *      @OA\Parameter (name="limit",in="query",description="default  limit=12",@OA\Schema(type="integer")),
     *      @OA\Parameter (name="package_uuid",in="query",@OA\Schema (type="string"),description="package uuid ex:123F-2347Y-23456-987GJ"),
     *      @OA\Parameter (name="createdBy",in="query",@OA\Schema (type="string"),description="package user creator uuid ex:123F-2347Y-23456-987GJ"),
     *      @OA\Parameter (name="isActive",in="query",@OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="isEntreprise",in="query",@OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="isAutomatic",in="query",@OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="startAt",in="query",description="start interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *      @OA\Parameter (name="endAt",in="query",description="end interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *      @OA\Parameter (name="nature",in="query",
     *          @OA\Schema (type="array",@OA\Items(type="string",enum={"encodage", "stockage","hybride"} ))),
     *      @OA\Parameter (name="type",in="query",
     *          @OA\Schema (type="array",@OA\Items(type="string",enum={"Gratuit", "OneShot", "Credit", "Abonnement"} )))
     * )
     * @OA\Response(response=200,description="Return current user",
     *     @OA\JsonContent(type="array",@OA\Items(ref="#/components/schemas/Forfait")
     *     )
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
     *
     */
    public function find_package(Request $request, ForfaitManager $packageManager): Response
    {

        $data = $packageManager->findAll($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/packages",name="create",methods={"POST"})
     * @OA\Post(
     *  tags={"Packages"},
     *  summary="Create new packages ",
     *     description="Create a new pachage , depending  of the  nature and type creating rules exist",
     *   @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"nature","type","name","isEntreprise"},
     *              @OA\Property (property="name",description="unique name"),
     *              @OA\Property (property="price",description="ex: 0 euro if type=Gratuit",example="0"),
     *              @OA\Property (property="duration",description="0 if nature=storage , in durations ex: 60 ",example="0"),
     *              @OA\Property (property="sizeStorage",description="0 if nature=encodage ex: 0.1 Giga octet",example="0"),
     *              @OA\Property (property="isEntreprise",type="boolean",default=false),
     *              @OA\Property (property="isActive",type="boolean",default=false),
     *              @OA\Property (property="isAutomatic",type="boolean",default=false),
     *              @OA\Property (property="nature",type="string",enum={"encodage", "stockage","hybride"},default="encodage"),
     *              @OA\Property (property="type",type="string",enum={"Gratuit", "OneShot", "Credit", "Abonnement"} ,default="Abonnement")
     *
     * )),
     *     @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"nature","type","name","isEntreprise"},
     *              @OA\Property (property="name",description="unique name"),
     *              @OA\Property (property="price",description="ex: 0 euro if type=Gratuit",example="0"),
     *              @OA\Property (property="duration",description="0 if nature=storage , in durations ex: 60 ",example="0"),
     *              @OA\Property (property="sizeStorage",description="0 if nature=encodage ex: 0.1 Giga octet",example="0"),
     *              @OA\Property (property="isEntreprise",type="boolean",default=false),
     *              @OA\Property (property="isActive",type="boolean",default=false),
     *              @OA\Property (property="isAutomatic",type="boolean",default=false),
     *              @OA\Property (property="nature",type="string",enum={"encodage", "stockage","hybride"},default="encodage"),
     *              @OA\Property (property="type",type="string",enum={"Gratuit", "OneShot", "Credit", "Abonnement"} ,default="Abonnement")
     *
     * ))
     *     )
     * )
     * @OA\Response(response=200,description="Return packages",
     *     @OA\JsonContent(type="array",
     *        @OA\Items(ref=@Model(type=Forfait::class,groups={"list_all"}))
     *     )
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
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     *
     */
    public function create_package(Request $request, ForfaitManager $packageManager): Response
    {
        $data = $packageManager->newPackage($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/packages/{package_uuid}",name="edit",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Packages"},
     *  summary="Edit packages",
     *  description="Allow to modify a package information ",
     *     @OA\Parameter (name="package_uuid",in="path",description="package identifier" ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(type="object",
     *      @OA\Property (property="name",type="string",description="unique name",example="" ),
     *      @OA\Property (property="price",type="string",description="ex: 0 euro if type=Gratuit",example=""),
     *      @OA\Property (property="duration",type="string",description="0 if nature=storage ex: 60 min",example=""),
     *      @OA\Property (property="sizeStorage",type="string",description="0 if nature=encodage ex: 0.1 Giga octet",example=""),
     *      @OA\Property (property="isActive",type="string",example=""),
     *      @OA\Property (property="isEntreprise",type="string",example=""),
     *      @OA\Property (property="isAutomatic",type="string",example=""),
     *      @OA\Property (property="nature",type="string",enum={"encodage", "stockage","hybride"},default="encodage"),
     *      @OA\Property (property="type",type="string",enum={"Gratuit", "OneShot", "Credit", "Abonnement"} ,default="Abonnement")
     *      ),)
     *
     * ),
     *
     * @OA\Response(
     *     response=200,
     *     description="Return current user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref="#/components/schemas/Forfait")
     *     )
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
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     *
     */
    public function edit_package(Request $request, ForfaitManager $packageManager): Response
    {

        $data = $packageManager->editPackage($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/packages/{package_uuid}",name="remove",methods={"DELETE"})
     * @OA\Delete  (
     *  tags={"Packages"},
     *  summary="Delete packages",
     *  description="Remove package from and block it from being bought ",
     *     @OA\Parameter (name="package_uuid",in="path",description="package identifier" ),
     *
     *     ),
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Package was deleted"),
     * )),
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function remove(Request $request, ForfaitManager $forfaitManager)
    {
        $data = $forfaitManager->removePackage($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
