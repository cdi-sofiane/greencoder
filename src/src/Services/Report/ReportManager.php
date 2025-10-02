<?php

namespace App\Services\Report;

use App\Entity\Account;
use App\Entity\Encode;
use App\Entity\Report;
use App\Entity\ReportConfig;
use App\Entity\User;
use App\Form\Dto\DtoFilters;
use App\Helper\ReportHelper;
use App\Repository\ReportConfigRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\ReportVoter;
use App\Security\Voter\VideoVoter;
use App\Services\AbstactValidator;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerResponse;
use App\Services\JsonResponseMessage;
use App\Services\Report\Config\ReportConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class ReportManager extends AbstactValidator
{
    /**
     *
     *
     * @var Request $request
     */
    protected $request;

    public $reportConfigService;
    public $authorisation;
    public $reportConfigRepository;
    public $validator;
    public $dataFormalizerResponse;
    public $userRepository;
    public $reportService;
    public $knpSnappyBundle;
    public $pdfService;
    public $reportRepository;
    public $serializer;
    public $csvService;
    private $targetUser;
    private $accountVoter;
    private $security;
    private $em;
    private $videoVoter;
    private $reportVoter;


    public function __construct(
        RequestStack $requestStack,
        ReportConfigService $reportConfigService,
        AuthorizationService $authorisation,
        ReportConfigRepository $reportConfigRepository,
        ValidatorInterface $validator,
        DataFormalizerResponse $dataFormalizerResponse,
        UserRepository $userRepository,
        ReportService $reportService,
        PdfService $pdfService,
        ReportRepository $reportRepository,
        SerializerInterface $serializer,
        CsvService $csvService,
        Security $security,
        AccountVoter $accountVoter,
        VideoVoter $videoVoter,
        ReportVoter $reportVoter,
        EntityManagerInterface $em

    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->reportConfigService = $reportConfigService;
        $this->reportConfigRepository = $reportConfigRepository;
        $this->validator = $validator;
        $this->authorisation = $authorisation;
        $this->dataFormalizerResponse = $dataFormalizerResponse;
        $this->userRepository = $userRepository;
        $this->reportService = $reportService;
        $this->pdfService = $pdfService;
        $this->pdfService = $pdfService;
        $this->reportRepository = $reportRepository;
        $this->serializer = $serializer;
        $this->csvService = $csvService;
        $this->security = $security;
        $this->accountVoter = $accountVoter;
        $this->em = $em;
        $this->reportVoter = $reportVoter;
        $this->videoVoter = $videoVoter;
    }

    public function getDefaultConfig()
    {

        $group = 'report:admin';
        $accountRepo = $this->em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);
        if (!array_intersect($this->security->getUser()->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {

            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_REPORT_CONFIG]);
        }


        $reportConfig = $this->reportConfigRepository->findOneBy(['account' =>  $account]);


        $message = 'Report configuration was found successfully!!';
        if ($reportConfig === null) {

            $reportConfig  = $this->reportConfigRepository->findOneBy(["account" => null]);

            if ($reportConfig  == null) {

                $reportConfig = $this->reportConfigService->createUserReportConfig(null, null);
            }
        }
        $this->reportConfigRepository->add($reportConfig);
        return $this->dataFormalizerResponse->extract($reportConfig, $group, false, $message, Response::HTTP_OK);
    }


    public function editReportConfig()
    {
        $group = 'report:admin';
        $accountRepo = $this->em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);

        if (!array_intersect($this->security->getUser()->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {

            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_EDIT_REPORT_CONFIG]);
        }

        $reportConfig = $this->reportConfigRepository->findOneBy(['account' => $account]);

        if ($reportConfig == null) {
            $reportConfig  = $this->reportConfigRepository->findOneBy(["account" => null]);
            // if ($reportConfig  == null) {
            //     $reportConfig = $this->reportConfigService->createUserReportConfig(null, null);
            // }
            $reportConfig = $this->reportConfigService->createUserReportConfig(null, $account);
        }
        $body = $this->request->getContent();
        if ($body == null) {
            $message = 'Unprocessable Entity';
            return $this->dataFormalizerResponse->extract($reportConfig, $group, false, $message, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $reportConfig = $this->reportConfigService->editReportConfig($body, $reportConfig, $group);

        $err = $this->validator->validate($reportConfig, null, [$group]);

        if ($err->count() > 0) {
            return $this->err($err);
        };


        $message = 'Report configuration was successfully modified!';
        $this->reportConfigRepository->add($reportConfig);

        return $this->dataFormalizerResponse->extract($reportConfig, $group, false, $message, Response::HTTP_OK);
    }

    public function generateReport($user = null)
    {
        $user = $this->security->getUser();
        $accountRepo = $this->em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_CREATE_REPORT]);


        $body = json_decode($this->request->getContent(), true);


        $group = 'report:admin';

        if (!isset($body['videos']) || isset($body['videos']) == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['there is no data to generate a report!']);
        }
        $name = isset($body['name']) != null ? $body['name'] : '';



        $arrReportData = [
            'title' => $name,
            'logo' =>  $account->getLogo(),
            'videos' => []
        ];
        $encodeRepo = $this->em->getRepository(Encode::class);
        foreach ($body['videos'] as $rowVideo) {

            $rowdata = $this->reportService->valideRowVideo($rowVideo);

            $err = $this->validator->validate($rowdata, null, $group);

            if ($err->count() > 0) {
                return $this->err($err);
            };

            $calculedReportRow = $this->reportService->calculesForRowVideo($rowVideo, $account);
            $video = $encodeRepo->findOneBy(['uuid' => $calculedReportRow['uuid']])->getVideo();

            $isGranted = $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_VIDEO_REPORT]);

            if ($isGranted == -1) {
                continue;
            }
            if (!$calculedReportRow) {
                continue;
            }

            $arrReportData['videos'][] = $calculedReportRow;
        }

        if ($arrReportData['videos'] == null) {
            $message = "Encode videos nots found!";
            return $this->dataFormalizerResponse->extract(null, $group, false, $message, Response::HTTP_NOT_FOUND);
        }

        $reportPages = $this->reportService->buildValidRessource($arrReportData, $account, $user);

        $this->csvService->generateCsv($arrReportData['videos'], $reportPages);
        /**
         * @var Account $account
         */

        $reportPages['pilote'] = $account->getOwner()->toArray();


        $this->pdfService->generatePdf($reportPages);

        $message = 'success';

        return $this->dataFormalizerResponse->extract($reportPages['report'], $group, false, $message, Response::HTTP_OK);
    }



    public function findReports()
    {

        $account = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $this->request->attributes->get("account_uuid")]);
        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_REPORTS]);
        $group = 'report:list';
        $data = $this->serializer->deserialize(
            json_encode($this->request->query->all()),
            Report::class,
            'json',
            [
                'object_to_populate' =>  new Report(),
                "groups" => $group,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ],

        );

        $err = $this->validator->validate($data, null, $group);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $filters =  $data->toArray();

        $reports = $this->reportRepository->findFilteredReports($filters, $account);

        $group = 'report:admin';
        $message = "Reports  successfully retrived!";
        if ($reports == null) {
            $group = null;
            $message = "Reports  not found!";
            return $this->dataFormalizerResponse->extract(null, $group, false, $message, Response::HTTP_NO_CONTENT);
        }

        return $this->dataFormalizerResponse->extract($reports, $group, true, $message, Response::HTTP_OK, $filters);
    }
    public function deleteReport()
    {

        $report_uuid = $this->request->attributes->get('report_uuid');

        $filters = ['uuid' => $report_uuid,  'isDeleted' => false];

        $report = $this->reportRepository->findOneBy($filters);

        $this->reportVoter->vote($this->security->getToken(), $report, [ReportVoter::ACCOUNT_DELETE_REPORT]);


        $this->reportService->removeFromStorage($report);

        return $this->dataFormalizerResponse->extract(null, null, false, 'successfuly deleted');
    }
    /**
     * Undocumented function
     *
     * @param [type] $user
     * @return JsonResponseMessage
     */
    public function extractCsvData($user = null)
    {


        $report_uuid = $this->request->query->get('report_uuid');

        $report = $this->reportRepository->findOneBy(['uuid' => $report_uuid]);
        $this->reportVoter->vote($this->security->getToken(), $report, [ReportVoter::ACCOUNT_EDIT_REPORT]);

        $csvFile = $this->csvService->csvToArray($report);
        if ($csvFile != null) {

            return $this->dataFormalizerResponse->extract($csvFile);
        }

        return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('file not found');
    }
}
