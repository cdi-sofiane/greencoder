<?php

namespace App\Controller\Api;

use App\Services\Forfait\ForfaitManager;
use App\Services\Video\VideoManager;
use App\Entity\Video;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Entity\Simulation;
use App\Form\Dto\DtoCredit;
use App\Form\Dto\DtoTag;
use App\Services\JsonResponseMessage;
use App\Services\Storage\S3Storage;

/**
 * @Route("/", name="video_")
 *
 */
class VideoController extends AbstractController
{

    private $videoManager;

    public function __construct(VideoManager   $videoManager)
    {
        $this->videoManager =    $videoManager;
    }

    /**
     * @Route  ("/videos",name="list_videos",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="List of videos ",
     *     description="List of filtered videos depending of users right's  ",
     * @OA\Parameter (name="user_uuid",in="query",description="user uuid",@OA\Schema(type="string")),
     * @OA\Parameter (name="account_uuid",in="query",description="account uuid",@OA\Schema(type="string")),
     * @OA\Parameter (name="folder_uuid",in="query",description="folder uuid",@OA\Schema(type="string")),
     * @OA\Parameter (name="page",in="query",description="select a page default page=1",@OA\Schema(type="integer")),
     * @OA\Parameter (name="limit",in="query",description="number of item in a page default=12",@OA\Schema(type="integer")),
     * @OA\Parameter (name="tags",in="query",description="dont forget the coma between each tag ex:METEO,TF1,Week", @OA\Schema(type="array",@OA\Items(type="string")),
     * @OA\Parameter (name="sortBy",in="query",description="name or date",
     *     @OA\Schema(type="array",@OA\Items(type="string",enum={"name","date"} ,default="date") )
     * ),
     * @OA\Parameter (name="order",in="query",description="ASC or DESC",
     *    @OA\Schema(type="array",@OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )
     * ),
     * @OA\Parameter (name="mediaType",in="query",description="Media type",style="form",explode=false,
     *    @OA\Schema(type="array",@OA\Items(type="string",enum={"DEFAULT", "WEBINAR", "FIXED_SHOT", "HIGH_RESOLUTION","GREEN++","GREEN+","ANIMATION","STILL_IMAGE"} ,default="DEFAULT" ) )
     * ),
     * @OA\Parameter (name="encodingState",style="form",simple=false,in="query",description="State when encoding",style="form",explode=false ,
     *    @OA\Schema(type="array",@OA\Items(type="string",enum={"PENDING","ANALYSING", "RETRY", "ENCODING", "ENCODED", "ERROR"} ,default="PENDING") )
     * ),
     * @OA\Parameter (name="name",in="query",description="name",@OA\Schema(type="string")),
     * @OA\Parameter (name="isStored",in="query",description="if video was stored",@OA\Schema(type="boolean",default=false)),
     * @OA\Parameter (name="isDeleted",in="query",description="if video was deleted",@OA\Schema(type="boolean",default=false)),
     * @OA\Parameter (name="isMultiEncoded",in="query",description="if video was multiencoded",@OA\Schema(type="boolean",default=false)),
     * @OA\Parameter (name="startAt",in="query",description="start interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     * @OA\Parameter (name="endAt",in="query",description="end interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Video::class,groups={"list_of_videos"}))
     *     ),),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function listVideo()
    {

        $data = $this->videoManager->findAll($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
    /**
     * @Route ("videos/file-exist",name="exist",methods="GET")
     * @OA\Get (
     *    tags={"Videos"},
     * )),

     * @IsGranted("ROLE_DEV",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function verifyForVideoEngageAzureFileExist(Request $request, S3Storage $storage)
    {

        $filename = $request->query->get('filename');
        $data = (new JsonResponseMessage)->setCode(200)->setContent($storage->findThumbnailInStorage($filename));

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
    /**
     * @Route("/videos/{video_uuid}",name="one",methods={"GET"})
     * @OA\Get(
     *     tags={"Videos"},
     *     summary=" Find one video",
     *     description="Find a video with its uuid",
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\JsonContent(
     *        type="array",
     *       @OA\Items(ref="#/components/schemas/Video")
     *     ),),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function findVideo()
    {
        $data = $this->videoManager->findOne($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route  ("/videos/{video_uuid}/download",name="download",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="Download a video",
     *     description="Download a video and add a count to calculate carbon consumption evolution",
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\Schema (
     *        type="file",
     *        example="download"
     *
     * ),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     *
     */
    public function downloadVideo()
    {

        return $this->videoManager->downloadFile($this->getUser());
    }

    /**
     * @Route  ("/videos",name="remove_multi_Videos",methods={"DELETE"})
     * @OA\Delete   (
     *     tags={"Videos"},
     *     summary="remove multiple video",
     *     description="Can remove multiples videos with list of video_uuid's ",
     *   )
     * )
     * @OA\RequestBody (
     *     @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"videos"},
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
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     *
     */
    public function removeMultiVideos()
    {

        $data = $this->videoManager->multiRemoveVideo($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route  ("/videos/{video_uuid}/stream",name="stream",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="Stream a video",
     *     description="Stream a video  and add a count to calculate carbon consumption evolution ",
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\Schema (
     *        type="file",
     *        example="streaming"
     *
     * ),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     */
    public function streamVideo()
    {

        return $this->videoManager->streamFile();
    }

    /**
     * @Route  ("/videos/encode",name="encode",methods={"POST"})
     *
     * @OA\Post (
     *     tags={"Videos"},
     *     summary="Encode a video",
     *     description="Encode a video depending of params, and create a thumbnail",
     * @OA\RequestBody(
     *     description="Video file",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *      required={"file","isMultiEncoded","isStored","mediaType"},
     * @OA\Property (property="title",description="alternative name"),
     * @OA\Property (property="file",type="string" , format="binary"),
     * @OA\Property (property="isMultiEncoded",type="boolean",default=true ),
     * @OA\Property (property="isStored",type="boolean",default=true ),
     * @OA\Property (property="mediaType",enum={"DEFAULT", "WEBINAR", "FIXED_SHOT", "HIGH_RESOLUTION","GREEN++","GREEN+","ANIMATION","STILL_IMAGE","TWITCH"} ,default="DEFAULT"),
     * @OA\property (property="account_uuid",type="string",description="account uuid ex:123F-2347Y-23456-987GJ"),
     * @OA\property (property="folder_uuid",type="string",description="folder uuid ex:123F-2347Y-23456-987GJ"),
     * @OA\Property (property="qualityNeed",type="string",description="ex:1080x640"),
     * @OA\Property (property="tags", type="array", @OA\Items(ref=@Model(type=DtoTag::class),description="dont forget the coma between each tag ex:METEO,TF1,Week"),
     *          ),
     *
     *     )
     *   )
     * )
     *
     * )
     * @OA\Response(response="201",description="Created uploaded Video",
     *     @OA\JsonContent(
     *        type="array",
     *       @OA\Items(ref="#/components/schemas/Video")
     *     )),
     * @OA\Response(response="401",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This Action is forbidden for this account!"),
     *     ),),
     * @OA\Response(response="406",description="Not Acceptable",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="406"),
     *              @OA\Property( property="error",example="select a valid file!"),
     *     ),),
     * @OA\Response(response="403",description="forbiden",
     *      @OA\JsonContent(
     *      @OA\Property( property="code",example="403"),
     *      @OA\Property( property="data",
     *        type="array", @OA\Items(patternProperties="data",ref=@Model(type=DtoCredit::class,groups={"credit:error"}))),
     *       @OA\Property( property="error",example="not enought credits"),
     *
     *     ),),
     * @OA\Response(response="415",description="Unsupported Media Type",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="415"),
     *              @OA\Property( property="error",example="Unsupported Media Type"),
     *     ),),
     * @OA\Response(response="422",description="Unprocessable Entity",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="Unprocessable Entity"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */

    public function encodeVideo()
    {
        $data = $this->videoManager->encode($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route  ("/videos/estimate",name="estimate",methods={"POST"})
     *
     * @OA\Post (
     *     tags={"Videos"},
     *     summary="Estimate video",
     *     description="Estimate gain for  encoding video ,we dont keep video in storage and we dont encode it",
     *     @OA\RequestBody(description="Video file",required=true,
     *          @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(property="file",type="string" , format="binary"),
     *          ),
     *
     *     ))
     * )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Simulation::class,groups={"estimate"}))
     *     ),),
     * @OA\Response(response="403",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This Action is forbidden for this account!"),
     *     ),),
     * @OA\Response(response="406",description="Not Acceptable",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="406"),
     *              @OA\Property( property="error",example="select a valid file!"),
     *     ),),
     * @OA\Response(response="415",description="Unsupported Media Type",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="415"),
     *              @OA\Property( property="error",example="Unsupported Media Type"),
     *     ),),
     * @OA\Response(response="422",description="Unprocessable Entity",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="Unprocessable Entity"),
     *     ),),
     * )
     */
    public function estimateVideo()
    {
        $data = $this->videoManager->estimate();

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route  ("/videos/{video_uuid}/thumbnail",name="thumbnail",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="Find thumbnail",
     *     description="Find thumbnail in storage",
     *      @OA\Parameter (name="quality",in="query",required=true,
     *     @OA\Schema (type="array",@OA\Items(type="string",enum={"HD","SD"},default="HD" )))),
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\Schema (
     *        type="file",
     *        example="thumbnail",
     * ),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     */
    public function thumbnailStream()
    {
        return $this->videoManager->streamThumbnail();
    }

    /**
     * @Route  ("/videos/{video_uuid}/increment-download",name="download_increment",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="Increment download count-down",
     *     description="Add aditional possibilty to download an original video or encoded video when it is not stored",
     *      @OA\Parameter (name="video_uuid",in="path",required=true,
     *
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *      @OA\Schema (
     *        type="file",
     *        example="thumbnail",
     * ),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function downloadCounter()
    {
        return $this->videoManager->addDownloadCount($this->getUser());
    }

    /**
     * @Route  ("/videos/{video_uuid}/progress",name="encode_progress",methods={"GET"})
     * @OA\Get  (
     *     tags={"Videos"},
     *     summary="Progressing encode",
     *     description="Verify encode progression from 0 to 100 %",
     * @OA\Parameter (name="video_uuid",in="path",required=true,
     *
     *   )
     * )
     * @OA\Response(response="200",description="Success",
     *     response=200,
     *     description="Video",
     *     @OA\JsonContent(
     *       type="array",
     *     @OA\Items(ref=@Model(type=Video::class,groups={"encode:progress"})))
     *     )
     * ),
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function progress()
    {
        $data = $this->videoManager->pingProgress($this->getUser());
        return new JsonResponse($data->displayData());
    }

    /**
     * @Route("/videos/{video_uuid}",name="remove",methods={"DELETE"})
     * @OA\Delete  (
     *  tags={"Videos"},
     *  summary="Delete video",
     *  description="Delete a video",
     *     @OA\Parameter (name="video_uuid",in="path",description="video identifier" ),
     *     )
     * @OA\Response(
     *     response=200,
     *     description="Video",
     *    @OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="error",example="Video succefuly removed!"),
     *     ),),
     * )
     * @OA\Response(response="404",description="Not found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Video(s) not found!"),
     *     ),),
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function remove()
    {
        $data = $this->videoManager->removeVideo($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/videos/{video_uuid}/retry",name="retry",methods={"PATCH"})
     * @OA\Patch   (
     *  tags={"Videos"},
     *  summary="Retry encoding video",
     *  description="Retry encoding a video change encodingState to retry then progess when encoder is re encoding  ",
     *     @OA\Parameter (name="video_uuid",in="path",description="video identifier" ),
     *     )
     * @OA\Response(
     *     response=200,
     *     description="Video",
     *     @OA\JsonContent(
     *       type="array",@OA\Items(ref=@Model(type=Video::class,groups={"encode:retry"})))
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function recode()
    {
        $data = $this->videoManager->retryEncode();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("videos/{video_uuid}",name="edit",methods="PUT")
     * @OA\Put   (
     *  tags={"Videos"},
     *  summary="Edit video info",
     *  description="Edit a video title ",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"title"},
     *              @OA\Property (property="title" )
     * )))),
     * @OA\Response(
     *     response=200,
     *     description="Video",
     *     @OA\JsonContent(
     *       type="array",@OA\Items(ref=@Model(type=Video::class,groups={"encode:retry"})))
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function editVideo()
    {
        $data = $this->videoManager->editVideoInfo($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route ("videos/{video_uuid}/store",name="store",methods="PUT")
     * @OA\Put   (
     *  tags={"Videos"},
     *  summary="Store video",
     *  description="Store  a video that was unstored  ",
     * )),
     * @OA\Response(
     *     response=200,
     *     description="Video",
     *     @OA\JsonContent(
     *       type="array",@OA\Items(ref=@Model(type=Video::class,groups={"encode:retry"})))
     *     )
     * )
     * @IsGranted("ROLE_PILOTE",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function keepVideo()
    {
        $data = $this->videoManager->storeVideo();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/videos/{video_uuid}/copy",name="copy",methods={"POST"})
     * @OA\Post   (
     *  tags={"Videos"},
     *  summary="Copy  video",
     *  description="Copy video en re encode with another type ",
     *     @OA\Parameter (name="video_uuid",in="path",description="video identifier" ),
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property (property="mediaType",type="string",enum={"DEFAULT", "WEBINAR", "FIXED_SHOT", "HIGH_RESOLUTION","GREEN++","GREEN+","ANIMATION","STILL_IMAGE","TWITCH"} ,default="DEFAULT"),
     *      )),
     *     ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Video",
     *     @OA\JsonContent(
     *       type="array",@OA\Items(ref=@Model(type=Video::class,groups={"encode:retry"})))
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function copy()
    {
        $data = $this->videoManager->reEncodeFromExistingVideo($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/videos/{video_uuid}/trash",name="trash_video",methods={"PATCH"})
     * @OA\Patch(
     *  tags={"Videos"},
     *  summary="trash video",
     *  description="delete video temporarie",
     *     @OA\Parameter (name="video_uuid",in="path",description="video identifier"),
     * )
     * @OA\Response(
     *     response=200,
     *     description="Video"
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function trash($video_uuid)
    {
        $data = $this->videoManager->trashVideo($video_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/videos/{video_uuid}/restore",name="restore_video",methods={"PATCH"})
     * @OA\Patch(
     *  tags={"Videos"},
     *  summary="restore video",
     *  description="restore video",
     *     @OA\Parameter (name="video_uuid",in="path",description="video identifier"),
     * )
     * @OA\Response(
     *     response=200,
     *     description="Video"
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function restore($video_uuid)
    {
        $data = $this->videoManager->restoreVideo($video_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
