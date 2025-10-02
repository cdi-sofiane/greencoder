<?php

namespace App\Controller\Api;

use App\Repository\VideoRepository;
use App\Services\Consumption\ConsumptionManager;
use App\Services\JsonResponseMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @Route("/", name="public_")
 *
 */
class PublicInfosController extends AbstractController
{

    private $videoRepository;
    private $consumptionManager;

    public function __construct(
        VideoRepository    $videoRepository,
        ConsumptionManager $consumptionManager
    ) {
        $this->videoRepository = $videoRepository;
        $this->consumptionManager = $consumptionManager;
    }

    /**
     * @Route ("infos",name="infos",methods={"GET"})
     * @OA\Get (
     *     tags={"Public"},
     *     summary="Find public infos ",
     *     description="Find infos about comsumption of all videos",
     *
     * )
     * * @OA\Response(
     *     response=200,
     *     description="Return Public infos",
     *     @OA\JsonContent(
     *        @OA\Property( property="code",example="200"),
     *        @OA\Property( property="message",example="Success"),
     *        @OA\Property( property="data",example="{totalGainCarbon:0}"),
     *     )
     * )
     */
    public function publicInfos(Request $request)
    {
        $storedVideos = $this->videoRepository->findVideos();
        $infosAdmin = [
            'totalGainCarbon' => 0
        ];
        foreach ($storedVideos as $video) {

            $calculVideo = $this->consumptionManager->calculeForVideo($video);

            $infosAdmin['totalGainCarbon'] += $calculVideo->getGainCarbonConsumption();
        }
        $data = (new JsonResponseMessage())->setContent($infosAdmin)->setError(['success'])->setCode(Response::HTTP_OK);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}