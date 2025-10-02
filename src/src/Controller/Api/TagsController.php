<?php

namespace App\Controller\Api;

use App\Services\Tags\TagsManager;
use App\Services\Users\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @Route("/", name="tags_")
 *
 */
class TagsController extends AbstractController
{
    /**
     * @var TagsManager
     */
    private $tagsManager;

    public function __construct(TagsManager $tagsManager)
    {
        $this->tagsManager = $tagsManager;
    }

    /**
     * @Route ("/tags",name="create",methods={"POST"})
     * @OA\Post (
     *      tags={"Tags"},
     *      summary="create tag",
     *      description="add or create tag('s) for video('s) and add it to account tags list ",
     * ),
     * @OA\RequestBody (
     *     @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"tags","videos"},
     *             @OA\Property (property="tags",type="array" ,@OA\Items(type="string")),
     *             @OA\Property (property="videos",type="array" ,@OA\Items(type="string")),
     * )
     * )
     * )
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success"),
     *
     *     ),),
     * )
     *  * @OA\Response(response="422",description="Unprocessable Entity",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="Unprocessable Entity"),
     *     ),),
     * )

     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function createTags(Request $request)
    {

        $jsonResponse = $this->tagsManager->addTagsToVideos($this->getUser());
        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }

    /**
     * @Route ("/tags",name="all",methods={"GET"})
     * @OA\Get (
     *      tags={"Tags"},
     *      summary="find accounts tag('s)",
     *      description="get tag('s') from the current account",
     * @OA\Parameter (name="account_uuid",in="query",description="account uuid",@OA\Schema(type="string")),
     * ),

     * )
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *
     *
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function findTags(Request $request)
    {

        $jsonResponse = $this->tagsManager->findAccountTags($this->getUser());
        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }


    /**
     * @Route ("/tags",name="delete",methods={"DELETE"})
     * @OA\Delete  (
     *      tags={"Tags"},
     *      summary="remove account tags ",
     *      description="remove tag('s') from video('s) and unused tags from Account",
     * ),
     * @OA\RequestBody (
     *     @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"videos","tags"},
     *             @OA\Property (property="videos",type="array" ,@OA\Items(type="string")),
     *             @OA\Property (property="tags",type="array" ,@OA\Items(type="string")),
     *
     * )
     * )
     * )
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *                @OA\Property( property="code",example="200"),
     *                @OA\Property( property="message",example="Success"),
     *
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function removeTags(Request $request)
    {

        $jsonResponse = $this->tagsManager->removeTagsFromVideos($this->getUser());
        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }
}
