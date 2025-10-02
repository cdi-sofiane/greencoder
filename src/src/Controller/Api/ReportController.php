<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\ReportConfig;
use App\Entity\Report;
use App\Services\Report\ReportManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @Route("/", name="reports_")
 *
 */
class ReportController extends AbstractController
{

    /**
     * Undocumented variable
     *
     * @var ReportManager $reportManager
     */
    public $reportManager;


    public function __construct(ReportManager $reportManager)
    {
        $this->reportManager = $reportManager;
    }


    /**
     * @Route ("reports-config",name="config_edit",methods={"PUT"})
     * @OA\Put (
     *     tags={"Reports"},
     *     summary="Edit report configuration",
     *     description="Edit for an existing user report configuration  ",
     *     @OA\Parameter (name="account_uuid",in="query",description="account uuid")),
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property( property="totalCompletion",type="integer"),
     *              @OA\Property( property="totalViews",type="integer"),
     *              @OA\Property( property="mobileRepartition",type="integer"),
     *              @OA\Property( property="desktopRepartition",type="integer"),
     *              @OA\Property( property="mobileCarbonWeight",type="integer"),
     *              @OA\Property( property="desktopCarbonWeight",type="integer"),
     *
     *      )),
     *     ),
     * )
     * @OA\Response(response=200,description="Return packages",
     *        @Model(type=ReportConfig::class,groups={"report:get"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function updateReportConfig(Request $request)
    {

        $data = $this->reportManager->editReportConfig($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("/reports-config",name="config",methods={"GET"})
     * @OA\Get (
     *     tags={"Reports"},
     *     summary="Find Report configuration ",
     *     description="Find users default report configuration values ",
     *   @OA\Parameter (name="account_uuid",in="query",description="account uuid")),

     *
     * )
     * @OA\Response(response=200,description="Return packages",
     *        @Model(type=ReportConfig::class,groups={"report:get"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function getConfig(Request $request)
    {
        $data = $this->reportManager->getDefaultConfig($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
    /**
     * @Route ("/accounts/{account_uuid}/reports",name="find",methods={"GET"})
     * @OA\Get (
     *     tags={"Reports"},
     *     summary="retrive Reports ",
     *     description="Find user reports",
     *      @OA\Parameter (name="search",in="query",description="find report")),
     *      @OA\Parameter (name="sortBy",in="query",description="name or date or number video or % economie",
     *      @OA\Schema(type="array",@OA\Items(type="string",enum={"name","date","video","economie"} ,default="date") )
     * ),
     *      @OA\Parameter (name="order",in="query",description="ASC or DESC",
     *      @OA\Schema(type="array",@OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )
     * ),
     * @OA\Parameter (name="startAt",in="query",description="start interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     * @OA\Parameter (name="endAt",in="query",description="end interval",@OA\Schema(type="string",format="YYYY-MM-DD")),
     * @OA\Parameter (name="isDeleted",in="query",description="true or false",
     *      @OA\Schema(type="array",@OA\Items(type="string",enum={"true","false"} ) )
     * )
     * )
     * @OA\Response(response=200,description="Return packages",
     *        @Model(type=Report::class,groups={"report:admin"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */

    public function getReports(Request $request)
    {
        $data = $this->reportManager->findReports();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
    /**
     * @Route ("/accounts/{account_uuid}/reports",name="generate",methods={"POST"})
     * @OA\Post (
     *     tags={"Reports"},
     *     summary="Create Reports ",
     *     description="Create report for logged user  ",
     *  @OA\RequestBody (
     *     @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(type="object",
     *              @OA\Property (property="name"),
     *              @OA\Property (property="account_uuid"),
     *              @OA\Property (property="videos",type="array",
     *                               @OA\Items(
     *                               @OA\Property (property="uuid",description ="encoded video uuid"),
     *                               @OA\Property( property="totalCompletion",type="integer"),
     *                               @OA\Property( property="totalViews",type="integer"),
     *                               @OA\Property( property="mobileRepartition",type="integer"),
     *                               @OA\Property( property="desktopRepartition",type="integer"),
     *                               @OA\Property( property="mobileCarbonWeight",type="integer"),
     *                               @OA\Property( property="desktopCarbonWeight",type="integer"),
     *
     * ),),),),

     * )
     * )
     * )
     * )
     * @OA\Response(response=200,description="Return packages",
     *        @Model(type=Report::class,groups={"report:admin"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function createReports(Request $request)
    {
        $data = $this->reportManager->generateReport();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("/reports/{report_uuid}",name="remove",methods={"DELETE"})
     * @OA\Delete (
     *     tags={"Reports"},
     *     summary="Delete Reports ",
     *     description="Delete report from storage  csv and pdf file will be deleted  ",
     * )
     * @OA\Response(response=200,description="Delete successfuly")
     * )
     * @OA\Response(response=404,description="Not found")
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function removeReports(Request $request)
    {
        $data = $this->reportManager->deleteReport();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("/reports/csv",name="edit",methods={"GET"})
     * @OA\Get (
     *     tags={"Reports"},
     *     summary="Get Report datas ",
     *     description="Get and extract report data from csv file ",

     *     @OA\Parameter (name="report_uuid",in="query",description="report uuid")),

     * )
     * )
     * @OA\Response(
     *     response="200",
     *     description="Exemple de réponse",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *         @OA\Property(property="uuid", type="string"),
     *         @OA\Property(property="totalCompletion", type="string"),
     *         @OA\Property(property="totalViews", type="string"),
     *         @OA\Property(property="mobileRepartition", type="string"),
     *         @OA\Property(property="desktopRepartition", type="string"),
     *         @OA\Property(property="mobileCarbonWeight", type="string"),
     *         @OA\Property(property="desktopCarbonWeight", type="string"),
     *         @OA\Property(property="resolution", type="string")
     *         )
     *     )
     * )
     * @OA\Response(response=404,description="Not found")
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function editReport(Request $request)
    {
        $data = $this->reportManager->extractCsvData();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
