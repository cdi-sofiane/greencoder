<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Services\Account\AccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;



/**
 * @Route("/historys",name="historys_")
 */
class HistoryController extends AbstractController
{

  public $accountManager;
  /**
   *
   * @Route("/accounts",name="one",methods={"GET"})
   * @OA\Get(
   *  tags={"History"},
   *  summary="find an accounts history ",
   *  description="find accouts history with last green-encoded video,pilote basic infos,account link",
   * )

   *  @OA\Response(response=200,description="Return Account",
   *        @Model(type=Account::class,groups={"account:list"})
   * )
   * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */

  public function getAccountsHistory(Request $request, AccountManager $accountManager): JsonResponse
  {

    $data = $accountManager->getAccountHistorys($this->getUser());

    return new JsonResponse($data->displayData(), $data->displayHeader());
  }
}
