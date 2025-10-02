<?php

namespace App\Services\Consumption;

use App\Entity\Encode;
use App\Entity\Video;
use App\Helper\VideoTypeIdentifier;
use App\Repository\ConsumptionRepository;
use App\Entity\Consumption;
use App\Repository\EncodeRepository;
use App\Repository\VideoRepository;
use App\Services\JsonResponseMessage;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpFoundation\Response;

class ConsumptionManager implements ConsumptionInterface
{

    private $consumptionRepository;
    private $encodeRepository;
    private $videoRepository;
    /**
     * can be a video From encode entity or video entity
     */
    private $selectedTypeVideo;
    private $videoTypeIdentifier;

    public function __construct(
        ConsumptionRepository $consumptionRepository,
        EncodeRepository      $encodeRepository,
        VideoRepository       $videoRepository,
        VideoTypeIdentifier   $videoTypeIdentifier
    )
    {
        $this->consumptionRepository = $consumptionRepository;
        $this->encodeRepository = $encodeRepository;
        $this->videoRepository = $videoRepository;
        $this->videoTypeIdentifier = $videoTypeIdentifier;

    }


    public function calculeForVideo(Video $video)
    {

        $listItem = $this->findComsumptionsRow($video->getUuid());
        $countOriginal = count($listItem);

        if ($video instanceof Video) {

            $video->setCarbonConsumption($this->calculeCarbonConsumtion($video));
            $video->setGainOptimisation($this->calculeEncodeHighestVideo($video));
            $video->setGainCarbonEncode($this->gainCarbonEncodage($video));
            $video->numberOfView = $countOriginal;
            $totalCarbonConsumptionEncode = 0;
            $totalGainCarbon = 0;
            $totalNumberOfView = 0;
            $countNumberOfEncode = 0;

            foreach ($video->getEncodes() as $encode) {
                if ($video->getEncodes() != null) {
                    $countNumberOfEncode += 1;
                    $encode = $this->calculeForEncode($encode);
                    $totalCarbonConsumptionEncode += $encode->getTotalCarbonConsumption();
                    $totalGainCarbon += $encode->getGainCarbon();
                    $totalNumberOfView += $encode->numberOfView;

                }

            }
            $totalNumberOfView += $countOriginal;
            $video->setTotalCarbonConsumption($this->calculeTotalCarbon($listItem)['carbonConsumption']);

            $video->setSavedCarbon($this->gainCarbonTotal($video, $totalGainCarbon));
        }

        return $video;

    }

    public function gainCarbonVideoEncoded($targetEncode, $encodeViews = null)
    {
        //gain carbone video encoder = Nbr de vues X (1 - (taille video encoder / taille video original)) X durée video X carbone
        if ($targetEncode instanceof Encode) {
            return $encodeViews * (1 - ($targetEncode->getSize() / $targetEncode->getVideo()->getSize())) * $targetEncode->getVideo()->getDuration() * $targetEncode->getVideo()->carbon / 60;
        }
    }

    public function gainCarbonEncodage(Video $video)
    {

        $this->selectedTypeVideo = $video;
        /* retrive encode asset with highest resolution  */
        $targetHighestEncodeVideo = $this->encodeRepository->findEndecodedFileWithHighestSize($this->selectedTypeVideo);

        if ($targetHighestEncodeVideo != null) {
            if (!is_object($targetHighestEncodeVideo[0])) {
                return $this;
            }
            //gain carbone encodage = (taille video encoder la plus haute / taille video original) X durée video X carbone
            $videoOptimisation = (1 - ($targetHighestEncodeVideo[0]->getSize() / $video->getSize())) * $video->getDuration() * $video->carbon / 60;
            return round($videoOptimisation, 2, PHP_ROUND_HALF_EVEN);

        }
        return 0;

    }

    public function gainCarbonTotal($video, $totalGainCarbon)
    {
        //gain carbone total = gain carbone encodage + SUM (gain carbone video encoder)
        return round($video->getGainCarbonEncode() + $totalGainCarbon, 2, PHP_ROUND_HALF_EVEN);
    }

    public function calculeForEncode($targetedEncode)
    {

        $listItem = $this->findComsumptionsRow($targetedEncode->getUuid());
        $countEncode = count($listItem);

        if ($targetedEncode instanceof Encode) {
            foreach ($listItem as $encodeConsumed) {

                if ($encodeConsumed->getEncode()->getId() == $targetedEncode->getId()) {
                    $targetedEncode->numberOfView = $countEncode;
                    $targetedEncode->setGainCarbon($this->gainCarbonVideoEncoded($targetedEncode, $targetedEncode->numberOfView));
                }

            }

        }
        return $targetedEncode;
    }

    public function addConsumptionRow($video, $args): void
    {

        if ($video instanceof Video) {

            $consumption = new Consumption();
            $consumption->setVideo($video);
            $this->consumptionRepository->create($consumption, $args);
        } else {

            $consumption = new Consumption();
            $consumption->setEncode($video);
            $this->consumptionRepository->create($consumption, $args);
        }

    }

    public function calculeTotalCarbon($listItems = null)
    {
        $listItems = isset($listItems) != null ? $listItems : $this->findComsumptionsRow();

        $value = 0;
        foreach ($listItems as $item) {
            if ($item->getVideo() != null) {
                /**@var Consumption $item */
                $value = $value + $this->calculeCarbonConsumtion($item->getVideo());
            }
            if ($item->getEncode() != null) {
                /**@var Consumption $item */

                $value = $value + $this->calculeCarbonConsumtion($item->getEncode());;
            }
        }

        $data = [
            "video" => $this->selectedTypeVideo,
            "carbonConsumption" => $value
        ];

        return $data;
    }

    public function calculeCarbonConsumtion($targetItem = null)
    {

        // calcule en grammes ( taille video originale / taille video original ) * constante en gramme) * ( dureer de la video en sec))
        if ($targetItem instanceof Video) {

            $data = ($targetItem->getSize() / $targetItem->getSize()) * $targetItem->carbon * ($targetItem->getDuration() / 60);

        }
        // en gramme ( taille video encoder / taille video original ) * constante en gramme) * ( dureer de la video en sec))
        if ($targetItem instanceof Encode) {

            $data = (($targetItem->getSize() / $targetItem->getVideo()->getSize()) * $targetItem->getvideo()->carbon) * ($targetItem->getvideo()->getDuration() / 60);
        }

        return $data;
    }

    public function calculeBP($listItems = null)
    {

        $listItems = $this->selectedTypeVideo == null ? $this->findData() : $listItems;

        $value = 0;
        foreach ($listItems as $item) {
            if ($item->getVideo() != null) {
                /**@var Video $item */
                $value = $value + $item->getVideo()->getSize();
            }
            if ($item->getEncode() != null) {
                /**@var Encode $item */
                $value = $value + $item->getEncode()->getSize();

            }

        }
        $data = [
            "video" => $this->selectedTypeVideo,
            "BandWidth" => $value
        ];
        return $data;


    }

    // ce qu'on as gagner en pourcentage entre original et la plus haute definition des video encoder (raport a la taille )
    public function calculeEncodeHighestVideo(Video $video)
    {
        $this->selectedTypeVideo = $video;
        $highestEncode = $this->findHighestEncodedVideo($video);

        // 100 - (((taille de la video encoder avec la meilleur resolution / taille originale)) * 100)

        if ($highestEncode != null) {

            $videoOptimisation = $highestEncode->getSize() != 0 ? 100 - (($highestEncode->getSize() / $video->getSize()) * 100) : 0;
            return round($videoOptimisation, 2, PHP_ROUND_HALF_EVEN);
        }
        return 0;
    }

    public function findHighestEncodedVideo(Video $targetVideo = null)
    {
        $targetHighestEncodeVideo = $this->encodeRepository->findEndecodedFileWithHighestSize($targetVideo);

        if ($targetHighestEncodeVideo == null) {
            return null;
        }
        if (!is_object($targetHighestEncodeVideo[0])) {
            return null;
        }
        return $targetHighestEncodeVideo[0];


    }

// ce q'on as gagner en carbon en utilisant les video encoder en pourcentage
    public
    function calculeGainCarbonConsumption(Video $targetVideo, $totalCarbonConsumptionEncode, $totalNumberOfView)
    {
        $emissionCarbonVideoEncode = $totalCarbonConsumptionEncode;
        $emissionCarbonVideoOriginal = $targetVideo->getCarbonConsumption() * $targetVideo->numberOfView;
        $emissionCarbonVideoEncodeHighestResolution = $this->findHighestEncodedVideo($targetVideo) != null ? $this->findHighestEncodedVideo($targetVideo)->getCarbonConsumption() : 0;
        $TotalEmissionCarbon = (($totalNumberOfView + 1) * ($targetVideo->getDuration() / 60)) * $targetVideo->carbon; // conso carbone moyen mondial a expliciter

        // (1 - ( total de consomation carbonne des video encoder + emission carbone de video originale + emission carbone de la video encoder avec le meuilleur ration )/ total des emission carbon * 100
        return (1 - ($emissionCarbonVideoEncode + $emissionCarbonVideoOriginal + $emissionCarbonVideoEncodeHighestResolution) / $TotalEmissionCarbon) * 100;
    }

    /**
     *
     * find videos or encode with define param
     * @param $video
     * @param null $launched
     * @return int|mixed|void
     */
    public
    function findComsumptionsRow($video_uuid = null, $launched = null, $dateDebutFacturation = null, $dateFin = null, $user = null)
    {

        $this->selectedTypeVideo = $this->videoTypeIdentifier->identify($video_uuid);

        $args = [
            'video' => $this->selectedTypeVideo,
            'launched' => $launched,
            'dateDebutFacturation' => $dateDebutFacturation,
            'dateFin' => $dateFin,
            'user' => $user
        ];

        return $this->consumptionRepository->findConsumedVideos($args);

    }

}