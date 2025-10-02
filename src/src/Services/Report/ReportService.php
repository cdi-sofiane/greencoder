<?php

namespace App\Services\Report;

use App\Entity\Encode;
use App\Entity\Report;
use App\Entity\ReportConfig;
use App\Entity\User;
use App\Entity\Video;
use App\Form\ReportConfigType;
use App\Form\ReportType;
use App\Helper\ReportHelper;
use App\Repository\EncodeRepository;
use App\Repository\ReportConfigRepository;
use App\Repository\ReportRepository;
use App\Repository\VideoRepository;
use App\Services\JsonResponseMessage;
use App\Services\Storage\S3Storage;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Instanceof_;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ReportService
{
    /**
     *
     *
     * @var FormFactory $formFactory;
     */
    public $formFactory;
    public $reportConfigRepository;
    public $serializer;
    public $videoRepository;
    public $encodeRepository;
    public $em;
    public $reportHelper;
    public $storage;
    public $reportRepository;

    public function __construct(
        FormFactoryInterface $formFactory,
        ReportConfigRepository $reportConfigRepository,
        SerializerInterface $serializer,
        VideoRepository $videoRepository,
        EncodeRepository $encodeRepository,
        EntityManagerInterface $em,
        ReportHelper $reportHelper,
        S3Storage $storage,
        ReportRepository $reportRepository
    ) {
        $this->formFactory = $formFactory;
        $this->reportConfigRepository = $reportConfigRepository;
        $this->serializer = $serializer;
        $this->videoRepository = $videoRepository;
        $this->encodeRepository = $encodeRepository;
        $this->reportHelper = $reportHelper;
        $this->em = $em;
        $this->reportRepository = $reportRepository;
        $this->storage = $storage;
    }


    private static function preparReportData(array $reportDataRaw)
    {

        $dataReport = [
            'user' => $reportDataRaw['user'],
            'name' => '',
            'title' => $reportDataRaw['title'],
            'pdf' => '',
            'csv' => '',
            'totalVideos' => 0,
            'totalCarbon' => 0,
            'optimisation' => 0,
            'totalEncodedSize' => 0,
            'totalOriginalSize' => 0,
            'totalEnergyKwh' => 0,
            'totalKwhCost' => 0,
            'totalEncodeEnergyKwh' => 0,
            'totalEncodeKwhCost' => 0,
            'account' => $reportDataRaw['account'],

        ];

        $countRow = 0;
        foreach ($reportDataRaw['template_4'] as $page) {
            foreach ($page as $rawData) {
                if ($rawData instanceof User) {
                    continue;
                }
                $dataReport['totalVideos'] = ++$countRow; // nbr de video dans le raport
                $dataReport['totalCarbon'] += $rawData['GainCo2']; // emission carbone totaliser par un veio originale
                $dataReport['totalEncodedSize'] += $rawData['encodeSize']; // somme poid d'une vieo encoder
                $dataReport['totalOriginalSize'] += $rawData['originalSize']; // somme poid d'un video original
                $dataReport['optimisation'] += $rawData['realGain']; // pourcentage gain entre video originale et encoder
                $dataReport['totalEnergyKwh'] += $rawData['originalEnergyKwh']; // energie consomer en kwh
                $dataReport['totalEncodeEnergyKwh'] += $rawData['encodeEnergyKwh']; // energie consomer en kwh
                $dataReport['totalKwhCost'] += $rawData['GainKwhCost']; // cout estimer
                $dataReport['totalEncodeKwhCost'] += $rawData['GainEnergyKwh']; // cout estimer
            }
        }

        return $dataReport;
    }

    // create an array with encode property and reportconfig property

    private function preparOriginalData($encode, $validReportRow)
    {
        $video = $encode->getVideo();
        $emptyValue = [
            'originalEmissionCo2' => '',
            'originalEnergyKwh' => '',
            'originalKwhCost' => '',
            'GainCo2' => '',
            'GainEnergyKwh' => '',
            'GainKwhCost' => '',
        ];
        $videoData = [
            'name' => $video->getName(),
            'resolution' => $encode->getQuality(),
            'originalUniteSize' => $this->reportHelper->ConvertBytesToGigaBytes($video->getSize()),
            'originalSize' =>  $this->reportHelper->ConvertBytesToGigaBytes($video->getSize()),
            $emptyValue,
            'realGain' => $this->reportHelper->calculeRealGain($encode),

        ];

        return array_merge($validReportRow, $videoData);
    }

    private function findAbsorbtionEqCo2($report, $absorbtionCo2)
    {

        $Co2eqText['arbre'] = "en absorption de " . ReportHelper::roundify($absorbtionCo2['arbre']) . " arbre(s) / an";
        $Co2eqText['voiture'] = "de " . ReportHelper::roundify($absorbtionCo2['voiture_15']) . " voiture(s) qui réalise(nt) 15000 km/an";
        $Co2eqText['avion'] = "de " . ReportHelper::roundify($absorbtionCo2['avion'])  . " A/R Paris - Las Vegas (avion)";
        $displayEqCo2Text = [];
        $totalCarbonTone = ReportHelper::baseCarbonConverter($report->getTotalCarbon());
        if ($report->getTotalCarbon() < 1000) {
            $displayEqCo2Text = [
                'arbre' => $Co2eqText['arbre']
            ];
        } else {
            $displayEqCo2Text = [
                'arbre' => $Co2eqText['arbre'],
                'voiture' => $Co2eqText['voiture'],
                'avion' => $Co2eqText['avion'],
            ];
        }

        return $displayEqCo2Text;
    }
    private function findEnergyElectConsumption($energy)
    {
        switch ($energy['GainEnergyKwh'] >= 2400) {
            case true:
                $consoElect = "Soit la consommation électrique de " . round((($energy['GainEnergyKwh'])  / 2400), 0, PHP_ROUND_HALF_UP)  . " appartements de 65 m² / an";
                break;

            default:
                $consoElect = "Soit la consommation électrique de " . round((($energy['GainEnergyKwh'])  / 200), 0, PHP_ROUND_HALF_UP) . " mois de chauffage d'un appartement de 65 m²";

                break;
        }
        return $consoElect;
    }
    private function findEnergyEconomiser($report, $energy)
    {

        return [
            'totalEncodeEnergyKwh' => ReportHelper::basePuissanceConverter($energy['GainEnergyKwh']),
            'totalEncodeKwhCost' => $energy['GainKwhCost'],
        ];
    }
    // create an array with encode property and reportconfig property
    private function preparEncodeData($encode, $validReportRow)
    {
        $emptyValue = [
            'encodeEmissionCo2' => '',
            'encodeEnergyKwh' => '',
            'encodeKwhCost' => '',
        ];
        $encodeData = [
            'encodeUniteSize' => $this->reportHelper->ConvertBytesToGigaBytes($encode->getSize()),
            'encodeSize' => $this->reportHelper->ConvertBytesToGigaBytes($encode->getSize()),
            $emptyValue,
            'realGain' => $this->reportHelper->calculeRealGain($encode),
        ];


        return array_merge($validReportRow, $encodeData);
    }
    public function createReport($reportDataRaw)
    {


        $dataReport = self::preparReportData($reportDataRaw);

        $report = new Report();
        $form = $this->formFactory->create(ReportType::class, $report);

        $formData = $form->submit($dataReport);
        $formData->getData()->setCreatedAt(new DateTimeImmutable('now'));
        $formData->getData()->setUpdatedAt(new DateTimeImmutable('now'));
        $formData->getData()->setIsDeleted(false);
        $formData->getData()->setUser($dataReport['user']);
        $formData->getData()->setAccount($dataReport['account']);
        $formData->getData()->setOptimisation(ReportHelper::calculeTotalRealGain($dataReport));
        $formData->getData()->setTotalEncodedSize($dataReport['totalEncodedSize']);
        $formData->getData()->setTotalOriginalSize($dataReport['totalOriginalSize']);


        $report = $this->reportRepository->add($formData->getData());
        return $this->reportInfos($reportDataRaw, $report);
    }
    public function reportInfos($reportDataRaw, Report $report)
    {

        $reportDataRaw['report'] = $report;
        $reportDataRaw['template_3'] = [

            'totalVideos' => $report->getTotalVideos(),
            'totalOriginalSize' => ReportHelper::baseSizeConverter($report->getTotalOriginalSize()),
            'totalEncodedSize' => ReportHelper::baseSizeConverter($report->getTotalEncodedSize()),
            'carbonEqSaved' => ReportHelper::baseCarbonConverter($report->getTotalCarbon()),
            'moyOptimisation' => $report->getOptimisation(),
            'repartition' => $reportDataRaw['template_3']['repartition'],
            'absorbtionCo2' => $this->findAbsorbtionEqCo2($report, $reportDataRaw['template_3']['absorbtionCo2']),
            'energy' => $this->findEnergyEconomiser($report, $reportDataRaw['template_3']['energy']),
            'chauffage' => $reportDataRaw['template_3']['energyChauffage'],


        ];

        return  $reportDataRaw;
    }



    public function valideRowVideo($rowVideo, $group = "report:generate")
    {

        $reportConfig = new ReportConfig();
        $data = $this->serializer->deserialize(
            json_encode($rowVideo),
            ReportConfig::class,
            'json',
            [
                'object_to_populate' => $reportConfig,
                "groups" => 'report:admin',
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ],

        );

        return $data;
    }
    /**
     * return calclued value for encode and original video and reportConfig for each encoded selectionned
     * exclude other video if not from $user
     */
    public function calculesForRowVideo($validReportRow, $account)
    {
        if (!isset($validReportRow['uuid'])) {
            return false;
        }

        $encode = $this->encodeRepository->findAccountEncodeVideo($validReportRow['uuid'], $account);


        if ((!$encode  instanceof Encode) && ($encode == null)) {

            return false;
        }


        $validReportOriginalRow = $this->preparOriginalData($encode, $validReportRow);
        $validReportEncodeRow = $this->preparEncodeData($encode, $validReportRow);


        return  array_merge(
            $this->reportHelper->preparCalculeOriginal($validReportOriginalRow),
            $this->reportHelper->preparCalculeEncode($validReportEncodeRow),
        );
    }

    public function buildValidRessource($arrReportData, $account, $user)
    {
        $pagesMaxItem = 10;
        $page = 0;
        $i = 0;

        $countIntervalGainEncodage = [
            '0% a 19%' => 0,
            '20% a 39%' => 0,
            '40% a 59%' => 0,
            '60% a 79%' => 0,
            '80% a 100%' => 0,
        ];

        $absorbtionCo2 = [
            'arbre' => 0,
            'chaufage' => 0,
            'voiture_15' => 0,
            'voiture_10' => 0,
            'avion' => 0,
        ];
        $energy = [
            'GainKwhCost' => 0,
            'GainEnergyKwh' => 0,
        ];

        foreach ($arrReportData['videos'] as $completeRowVideo) {
            # code...

            $completeConvertedRowVideo = $this->sortAndConvert($completeRowVideo);

            /**
             * create a pagination for slides
             */
            $reportDataRaw[$page][] = $completeConvertedRowVideo;
            if ($i <= $pagesMaxItem) {

                ++$i;
            } else {
                ++$page;
                $i = 0;
            }
            $energy = [
                'GainKwhCost' => $energy['GainKwhCost'] + $completeConvertedRowVideo['GainKwhCost'],
                'GainEnergyKwh' => $energy['GainEnergyKwh'] + $completeConvertedRowVideo['GainEnergyKwh'],
            ];
            $absorbtionCo2 = [
                'arbre' => $absorbtionCo2['arbre'] + $completeConvertedRowVideo['GainCo2'] / 25,
                'chaufage' => $absorbtionCo2['chaufage'] + $completeConvertedRowVideo['GainCo2'] / 1000,
                'voiture_15' => $absorbtionCo2['voiture_15'] + $completeConvertedRowVideo['GainCo2']  / 1500,
                'voiture_10' => $absorbtionCo2['voiture_10'] + (($completeConvertedRowVideo['GainCo2']  / 1700) * 15) / 10,
                'avion' => $absorbtionCo2['avion'] + $completeConvertedRowVideo['GainCo2']  / 1000,
            ];


            if ($completeConvertedRowVideo['realGain'] < 20) {
                $countIntervalGainEncodage['0% a 19%'] +=  1;
            } elseif ((20 >= $completeConvertedRowVideo['realGain']) || ($completeConvertedRowVideo['realGain'] <= 39)) {
                $countIntervalGainEncodage['20% a 39%'] +=  1;
            } elseif ((40 >= $completeConvertedRowVideo['realGain']) || ($completeConvertedRowVideo['realGain'] <= 59)) {
                $countIntervalGainEncodage['40% a 59%'] +=  1;
            } elseif ((60 >= $completeConvertedRowVideo['realGain']) || ($completeConvertedRowVideo['realGain'] <= 79)) {
                $countIntervalGainEncodage['60% a 79%'] +=  1;
            } elseif ((80 >= $completeConvertedRowVideo['realGain']) || ($completeConvertedRowVideo['realGain'] <= 100)) {
                $countIntervalGainEncodage['80% a 100%'] +=  1;
            }
        }


        $nbVideos = count($arrReportData['videos']);
        $repartition = [];

        foreach ($countIntervalGainEncodage as $key => $value) {

            if ($value != 0) {
                $repartition[$key] = ReportHelper::pourcentageForGainEncodage($value, $nbVideos);
                $repartition['label'][] = $key;
                $repartition['value'][] = ReportHelper::pourcentageForGainEncodage($value, $nbVideos);
            }
        };

        $reportDataRaw = [
            'title' => $arrReportData['title'],
            'logo' => isset($reportDataRaw['logo']) != null ? $reportDataRaw['logo'] : $account->getLogo(),
            'user' => $user,
            'account' => $account,
            'template_3' => [
                'repartition' => $repartition,
                'absorbtionCo2' => $absorbtionCo2,
                'energy' => $energy,
                'energyChauffage' =>  $this->findEnergyElectConsumption($energy)
            ],
            'template_4' =>  $reportDataRaw
        ];

        return $this->createReport($reportDataRaw);
    }
    /**
     * display in specific order to render in view  and convert values ex:
     * bytes to Go or gr to kg
     */
    public function sortAndConvert($rowDataVideo)
    {

        $ordonnedDataToPrint =
            [
                'name' => $rowDataVideo['name'],
                'totalViews' => $rowDataVideo['totalViews'],
                'originalSize' => $rowDataVideo['originalSize'],
                'originalEmissionCo2' => $rowDataVideo['originalEmissionCo2'],
                'originalEnergyKwh' => $rowDataVideo['originalEnergyKwh'],
                'originalKwhCost' => $rowDataVideo['originalKwhCost'],
                'realGain' => $rowDataVideo['realGain'],
                'encodeEmissionCo2' => $rowDataVideo['encodeEmissionCo2'],
                'encodeEnergyKwh' => $rowDataVideo['encodeEnergyKwh'],
                'encodeKwhCost' => $rowDataVideo['encodeKwhCost'],
                "GainCo2" => $rowDataVideo['GainCo2'],
                "GainEnergyKwh" => $rowDataVideo['GainEnergyKwh'],
                "GainKwhCost" => $rowDataVideo['GainKwhCost'],
                'encodeSize' => $rowDataVideo['encodeSize'],
            ];

        return $ordonnedDataToPrint;
    }

    public function removeFromStorage(Report $report)
    {
        $arrFiles = [
            'csv' => $report->getLink() . '.csv',
            'pdf' => $report->getLink() . '.pdf',
        ];

        foreach ($arrFiles as  $fileIdentifier) {

            $this->storage->removeReports($fileIdentifier);
        }

        $report->setIsDeleted(true)->setDeletedAt(new DateTimeImmutable('now'))->setCsv(null)->setPdf(null);
        $this->em->persist($report);
        $this->em->flush();
    }
}
class  VideoValue
{

    private $encode;
    public function __construct(Encode $encode)
    {
        $this->encode = $encode;
    }

    public function setup() {}
}
