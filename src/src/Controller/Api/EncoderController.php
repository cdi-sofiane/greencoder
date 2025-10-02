<?php

namespace App\Controller\Api;

use App\Form\Dto\DtoEncodeProgress;
use App\Services\Video\VideoManager;
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
 * @Route("/", name="encoder_")
 *
 */
class EncoderController extends AbstractController
{


    /**
     * @Route ("/encode/{video_uuid}/progress",name="progress",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Encoder"},
     *  summary="Update Progress",
     *  description="increment attribute progress in video until encoding is completed ",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="progress",type="integer"),
     *              @OA\Property (property="status",type="string",enum={"PENDING","ANALYSING", "RETRY", "ENCODING", "ENCODED", "ERROR"}),
     *
     *      )),
     *     ),
     * )
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *          type="array",@OA\Items(ref=@Model(type=DtoEncodeProgress::class,groups={"encode:progress"})))
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
    public function progress(Request $request, VideoManager $videoManager)
    {

        $data = $videoManager->encodeProgress($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
        
    }

    /**
     * @Route ("/encode/{video_uuid}",name="update",methods={"Patch"})
     * @OA\Patch (
     *  tags={"Encoder"},
     *  summary="Create encoded ",
     *  description="Create encoded video from original video",
     *   @OA\RequestBody(
     *     required=false,
     *      @OA\JsonContent(
     *         @OA\Property(
     *             property="socialNetwork",
     *             type="array",
     *             @OA\Items(
     *                 type="string"
     *             ),
     *             @OA\AdditionalProperties(
     *                 type="array",
     *                 @OA\Items(
     *                     type="string"
     *                 )
     *             ),
     *             example={
     *                 "1280x720": "9d31a74b-fe4-83ca_greencoded_1280x720.mp4",
     *                 "720x1280": "9d31a74b-fe4-83ca_greencoded_720x1280.mp4",
     *             }
     *         )
     *     )
     *   )
     * )
     * @OA\Response(response=200,description="Success",@OA\JsonContent(
     *          type="array",@OA\Items(ref=@Model(type=DtoEncodeProgress::class,groups={"encode:progress"})))
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
    public function createEncoded(Request $request, VideoManager $videoManager)
    {

        $data = $videoManager->populateVideo();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
