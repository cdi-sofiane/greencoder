<?php


namespace App\Controller\Api;

use App\Services\Account\AccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Account;
use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\Response;
use App\Form\Dto\DtoAccountInvitationResponse;
use App\Entity\UserAccountRole;
use App\Entity\User;
use App\Services\ApiKeyService;
use App\Services\Folder\FolderManager;
use App\Services\Users\UserManager;
use App\Form\Dto\DtoEditRight;
use App\Form\Dto\DtoUploadFolder;

/**
 * @Route("/accounts", name="account_")
 *
 */
class AccountController extends AbstractController
{
    /**
     * @var AccountManager $accountManager
     */
    private $accountManager;
    public function __construct(AccountManager $accountManager)
    {
        
        $this->accountManager = $accountManager;
    }


    /**
     *
     * @Route("",name="list",methods={"GET"})
     * @OA\Get(
     *  tags={"Accounts"},
     *  summary="find and filters  Accounts ",
     *  description="find accounts with filters",
     *      @OA\Parameter (name="search",in="query",description="name,company,email",
     *                      @OA\Schema(type="string")),
     *      @OA\Parameter (name="page",in="query",description="default page=1",
     *                      @OA\Schema(type="integer")),
     *      @OA\Parameter (name="limit",in="query",description="default  limit=12",
     *                      @OA\Schema(type="integer")),
     *      @OA\Parameter (name="sortBy",in="query",description="company or date or name ,lastConnection",
     *                     @OA\Schema(type="array",
     *                                @OA\Items(type="string",enum={"name","date,lastConnection"} ,default="date") )
     * 
     * ),
     *      @OA\Parameter (name="order",in="query",description="ASC or DESC",
     *      @OA\Schema(type="array",
     *                  @OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )
     * ),
     *      @OA\Parameter (name="user_uuid",in="query",
     *                      @OA\Schema (type="string"),description="user uuid ex:123F-2347Y-23456-987GJ"),
     *      @OA\Parameter (name="isMultiAccount",in="query",
     *                     @OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="isActive",in="query",
     *                     @OA\Schema (type="boolean",default=false )),
     *      @OA\Parameter (name="asAdmin",in="query",
     *                     @OA\Schema (type="boolean",default=false )),
     * )
     *
     * @OA\Response(response=200,description="Return accounts",
     *        @Model(type=Account::class,groups={"account:list"})
     * )
     * )
     *
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function findAll(Request $request)
    {

        $data = $this->accountManager->getAllAccounts($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     *
     * @Route("/{account_uuid}",name="one",methods={"GET"})
     * @OA\Get(
     *  tags={"Accounts"},
     *  summary="find an account",
     *  description="find an  accounts based on its uuid ",
     * )

     *  @OA\Response(response=200,description="Return Account",
     *        @Model(type=Account::class,groups={"account:list"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function getAccount(Request $request)
    {

        $data = $this->accountManager->getAccounts($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     *
     * @Route("/{account_uuid}",name="edit",methods={"PATCH"})
     * @OA\Patch(
     *  tags={"Accounts"},
     *  summary="Edit a specific account",
     *  description="Edit a specific account",
     *    @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property( property="usages",type="string",description="professional",description="Individual or professional",enum={"Individual","Professional"} ,default="Professional"),
     *              @OA\Property( property="name",type="string",description="doe"),
     *              @OA\Property( property="siret",type="string",description="12B4-AB2D-EZA4-A3V9"),
     *              @OA\Property( property="company",type="string",description="My Company"),
     *              @OA\Property( property="address",type="string",description="90 rue des hais"),
     *              @OA\Property( property="postalCode",type="string",description="75017"),
     *              @OA\Property( property="country",type="string",description="FRANCE"),
     *              @OA\Property( property="tva",type="string",description="5,5"),
     *              @OA\Property( property="maxInvitations",type="string",description="set maximum user account can invite multi-account = default 3 , mono-account default 1 only multi-account can be upgraded"),
     *      )),
     *     ),
     * )
     *
     *
     *  @OA\Response(response=200,description="Return Account",
     * )
     *
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function updateAccount(Request $request)
    {

        $data = $this->accountManager->editAccounts($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     *
     * @Route("/{account_uuid}/multi-account",name="multi",methods={"PUT"})
     * @OA\Put(
     *  tags={"Accounts"},
     *  summary="change mono account to multi account",
     *  description="change  mono user to multi user account only an account usage professional can be switched",
     *    @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="isMultiAccount",type="boolean",description="true or false"),
     *      )),
     *     ),
     * )
     *
     *  @OA\Response(response=200,description="Return Account",
     *        @Model(type=Account::class,groups={"account:admin:isMultiAccount"})
     * )
     *
     *
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function updateMultiAccount(Request $request)
    {

        $data = $this->accountManager->multiAccounts($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     *
     * @Route("/{account_uuid}/invite",name="invite",methods={"POST"})
     * @OA\Post(
     *  tags={"Accounts"},
     *   summary="Invite collaborator to your account",
     *  description="Invite collaborator to your account ",
     *    @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *               required={"email,role"},
     *               @OA\Property (property="email",type="string" ),
     *               @OA\Property (property="role", type="string", enum={"reader", "editor"})
     * ),
     *      )),
     *     ),
     * )
     *

     * @OA\Response(response=201,description="created",
     *       @Model(type=DtoAccountInvitationResponse::class,groups={"account:invitation"})
     * ))

     * @OA\Response(response="404",description="Not Found",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Invoice Not Found"),
     *     ),),

     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function inviteUser(Request $request)
    {

        $data = $this->accountManager->inviteToAccount();

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     *
     * @Route ("/join",name="account_join",methods={"PUT"})
     * @OA\Put(
     *     tags={"Accounts"},
     *     summary="join  to account after reciving an invitaion mail",
     *     description="With valid JwtToken Activate and edit  password and users required infos",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"password","firstName","lastName","phone"},
     *              @OA\Property (property="firstName",description="user firstName"),
     *              @OA\Property (property="lastName",description="user lastName"),
     *              @OA\Property (property="phone",description="user phone number"),
     *              @OA\Property (property="password",description="user password"),
     * )),

     * )
     * )
     * @OA\Response(response=200,description="User",@OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function userJoinAccount(Request $request): Response
    {

        $data = $this->accountManager->userJoinAccount($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     *
     * @Route("/{account_uuid}/toggle",name="toggle",methods={"PUT"})
     * @OA\Put(
     *  tags={"Accounts"},
     *  summary="active & inactive an account",
     *  description="active & inactive an account",
     * )
     * @OA\Response(response=202,description="Success", @OA\JsonContent(
     *              @OA\Property( property="code",example="202"),
     *              @OA\Property( property="message",example="Account successfully edited")
     *     )),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @OA\Response(response="404",description="Not Found",@OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Account Not Found"),
     *     ),),
     *
     * @IsGranted("ROLE_VIDMIZER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */

    public function toggleAccount(Request $request)
    {

        $data = $this->accountManager->toggleAccount($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route ("/{account_uuid}/api-key",methods={"PATCH"})
     * @OA\Patch (
     *     tags={"Accounts"},
     *      summary="apikey generation",
     *      description="create an apikey ",
     * ),
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success"),
     *
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function generateApiKey(Request $request, ApiKeyService $apiKeyService)
    {
        $jsonResponse = $apiKeyService->generateApiKey($this->getUser());

        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }
    /**
     * @Route ("/{account_uuid}/change-role",methods={"PATCH"})
     * @OA\Patch (
     *     tags={"Accounts"},
     *      summary="apikey generation",
     *      description="create an apikey ",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *               required={"user_uuid,role"},
     *               @OA\Property (property="user_uuid",type="string" ),
     *               @OA\Property (property="role", type="string", enum={"reader", "editor"})
     * ),
     *      )),
     *     ),
     * )
     * @OA\Response(response=201,description="created",
     *       @Model(type=User::class,groups={"list_users"})
     * ))
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function changeRole()
    {
        $data = $this->accountManager->changeUserRole();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/{account_uuid}/logo",name="edit_logo",methods={"POST"})
     * @OA\Post(
     *  tags={"Accounts"},
     *  summary="Edit Account's logo ",
     *  description="Edit an account's logo ",
     * @OA\Parameter (name="account_uuid",description="unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",in="path" ),
     * @OA\RequestBody(
     *     description="Logo file",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *      @OA\Property (property="file",type="string" , format="binary"),
     *          ),
     *     )
     *   )
     * )
     *
     * )
     * @OA\Response(response="200",description="Logo Uploaded",
     *     @OA\JsonContent(
     *         @OA\Property( property="code",example="200"),
     *         @OA\Property( property="message",example="Logo Added successfully!"),
     *     ),),
     * @OA\Response(response="401",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This Action is forbidden for this account!"),
     *     ),),
     * @OA\Response(response="404",description="Not Found",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="User not found!"),
     *     ),),
     * @OA\Response(response="400",description="Bad Request",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="400"),
     *              @OA\Property( property="error",example="['File too large', 'Only PNG and JPEG are allowed', 'Image dimension should be within min width 32x32 and max 256x256']"),
     *     ),),
     * @OA\Response(response="422",description="Unprocessable entity",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="File not specified"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function uploadLogo(Request $request, AccountManager $accountManager)
    {

        $data = $accountManager->uploadAccountLogo();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/{account_uuid}",name="delete",methods={"DELETE"})
     * @OA\Delete(
     *     tags={"Accounts"},
     *     summary="remove user from account   ",
     *     description="delete account's a user , account owner can't be removed",
     *         @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"user_uuid"},
     *               @OA\Property( property="user_uuid",type="string",description="user_uuid"),
     *
     *     )),)
     * )
     * @OA\Response(
     *     response="201",description="Created",@OA\JsonContent(
     *     @OA\Property(property="code",example="200"),
     *     @OA\Property(property="message",example="Created")
     *
     * )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function remove(Request $request)
    {

        $data = $this->accountManager->removeAccountMember($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/{account_uuid}/move-videos",name="account_video_move",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Accounts"},
     *  summary="move videos",
     *  description="move videos ",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="folder_uuid",type="string"),
     *              @OA\Property (property="account_uuid",type="string"),
     *              @OA\Property (property="videos",type="array",
     *                  @OA\Items(type="string")
     *              ),
     *      )),
     *     ),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function moveVideos()
    {
        $data = $this->accountManager->moveVideos();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/{account_uuid}/move-folders",name="account_folder_move",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Accounts"},
     *  summary="move folder's",
     *  description="move folder ",
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property (property="folder_uuid",type="string"),
     *              @OA\Property (property="account_uuid",type="string"),
     *              @OA\Property (property="folders",type="array",
     *                  @OA\Items(type="string")
     *              ),
     *      )),
     *     ),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function moveFolders()
    {
        $data = $this->accountManager->moveFolders();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     *
     * @Route("/{account_uuid}/trash",name="trash",methods={"GET"})
     * @OA\Get(
     *  tags={"Accounts"},
     *  summary="trash",
     *  description="find all videos and folders in trash ",
     * @OA\Parameter (name="search",in="query",description="folder and video title"),
     * @OA\Parameter (name="order",in="query",description="ASC or DESC",
     *    @OA\Schema(type="array",@OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )
     * ),
     * @OA\Parameter (name="sortBy",in="query",description="name or date",
     *     @OA\Schema(type="array",@OA\Items(type="string",enum={"name","date"} ,default="date") )
     * ),
     *  @OA\Response(response=200,description="Return Account"),
     *)
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function getTrash(string $account_uuid)
    {
        $data = $this->accountManager->getTrash($account_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/{account_uuid}/rights",name="account_edit_rights",methods={"PATCH"})
     * @OA\Patch (
     *  tags={"Accounts"},
     *  summary="edit rights",
     *  description="edit rights",
     * @OA\RequestBody(
     *         @OA\JsonContent(ref=@Model(type=DtoEditRight::class))
     *     ),
     *     ),
     * ),
     *  @OA\Response(
     *     response="200",description="Created",@OA\JsonContent(
     *     @OA\Property(property="code",example="200"),
     *     @OA\Property(property="message",example="Permission has been edited successfully")
     * )
     * ),
     * @OA\Response(response="404",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Account Not Found!"),
     * ),),
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function editRights(string $account_uuid)
    {
        $data = $this->accountManager->editRights($account_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     *
     * @Route("/get-one/email", name="one_by_email", methods={"GET"})
     * @OA\Get(
     *  tags={"Accounts"},
     *  summary="find accounts by email",
     *  description="find accounts by email",
     *      @OA\Parameter (name="email", in="query", required=true, @OA\Schema(type="string")),
     * ),
     * @OA\Response(response=200,description="Return accounts",
     *        @Model(type=Account::class,groups={"account:list"})
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function findAccountByEmail()
    {
        $data = $this->accountManager->getOneByEmail();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/{account_uuid}/upload-folders",name="upload_folders",methods={"POST"})
     * @OA\Post (
     *  tags={"Accounts"},
     *  summary="upload folders",
     *  description="upload folders",
     * @OA\RequestBody(
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=DtoUploadFolder::class))
     *     )
     *     ),
     *     ),
     * ),
     * @OA\Response(response="201",description="Folders successfully uploaded",
     *     @OA\JsonContent(
     *               @OA\Property(property="code",example="201"),
     *               @OA\Property(property="message",example="Folders successfully uploaded")
     * ),),
     * @OA\Response(response="404",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="404"),
     *              @OA\Property( property="error",example="Not Found!"),
     * ),),
     * @OA\Response(response="401",description="Forbidden",
     *     @OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This Action is forbidden for this account!"),
     * ),),
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function uploadFolders(string $account_uuid)
    {
        $data = $this->accountManager->uploadFolders($account_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
