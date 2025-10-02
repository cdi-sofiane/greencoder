<?php

namespace App\Controller\Api;


use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\ApiKeyService;
use App\Services\Users\UserAcceptTerm;
use App\Services\Users\UserEmailTokenIdentifier;
use App\Services\Users\UserFormalizeResponse;
use App\Services\Users\UserManager;
use App\Services\Users\UserPasswordVerify;
use App\Services\Users\UserResetPassword;
use App\Services\Users\UserUpdateValidator;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/", name="users_")
 *
 */
class UserController extends AbstractController
{
    protected $userRepository;
    protected $jwtDecodeEven;
    protected $paginator;
    protected $validator;
    private $userManager;
    private $apiKey;

    public function __construct(
        UserRepository           $userRepository,
        JWTTokenManagerInterface $jwtDecodeEven,
        PaginatorInterface       $paginator,
        ValidatorInterface       $validator,
        UserManager              $userManager,
        ApiKeyService            $apiKey
    ) {
        $this->userRepository = $userRepository;
        $this->jwtDecodeEven = $jwtDecodeEven;
        $this->paginator = $paginator;
        $this->validator = $validator;
        $this->userManager = $userManager;
        $this->apiKey = $apiKey;
    }

    /**
     *
     * @Route("/users", name="all",methods={"GET"})
     * @OA\Get(
     *  tags={"Users"},
     *   summary="Find users  ",
     *   description="Find list of users with parameters  when authenticated with jwt token",
     *  @OA\Parameter (name="account_uuid",description="account unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",@OA\Schema(type="string"),in="query",required=true),
     *  @OA\Parameter (name="user_uuid",description="user unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",@OA\Schema(type="string"),in="query"),
     *  @OA\Parameter (name="search",in="query",description="firstName ,lastName, email ,company",@OA\Schema(type="string")),
     *  @OA\Parameter (name="isActive",in="query",description="active",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isArchive",in="query",description="archive",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isDelete",in="query",description="active",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isConditionAgreed",in="query",description="term of usage",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="startAt",in="query",description="start interval from date of creation",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *  @OA\Parameter (name="endAt",in="query",description="end interval from date of creation",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *  @OA\Parameter (name="page",in="query",description="default page=1",@OA\Schema(type="integer")),
     *  @OA\Parameter (name="limit",in="query",description="default  limit=12",@OA\Schema(type="integer")),
     *  @OA\Parameter (name="sortBy",in="query",description="date or email",@OA\Schema(type="array",@OA\Items(type="string",enum={"date","email"} ,default="date") )),
     *  @OA\Parameter (name="order",in="query",description="ASC or DESC",@OA\Schema(type="array",@OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Return current user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     *
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function users(Request $request): Response
    {

        $data = $this->userManager->findAll($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     *
     * @Route("/members", name="all_members",methods={"GET"})
     * @OA\Get(
     *  tags={"Users"},
     *   summary="Find users  ",
     *   description="Find list of users with parameters  when authenticated with jwt token",
     *  @OA\Parameter (name="account_uuid",description="account unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",@OA\Schema(type="string"),in="query",required=true),
     *  @OA\Parameter (name="user_uuid",description="user unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",@OA\Schema(type="string"),in="query"),
     *  @OA\Parameter (name="search",in="query",description="firstName ,lastName, email ,company",@OA\Schema(type="string")),
     *  @OA\Parameter (name="isActive",in="query",description="active",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isArchive",in="query",description="archive",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isDelete",in="query",description="active",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="isConditionAgreed",in="query",description="term of usage",@OA\Schema(type="boolean",default=false)),
     *  @OA\Parameter (name="startAt",in="query",description="start interval from date of creation",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *  @OA\Parameter (name="endAt",in="query",description="end interval from date of creation",@OA\Schema(type="string",format="YYYY-MM-DD")),
     *  @OA\Parameter (name="page",in="query",description="default page=1",@OA\Schema(type="integer")),
     *  @OA\Parameter (name="limit",in="query",description="default  limit=12",@OA\Schema(type="integer")),
     *  @OA\Parameter (name="sortBy",in="query",description="date or email",@OA\Schema(type="array",@OA\Items(type="string",enum={"date","email"} ,default="date") )),
     *  @OA\Parameter (name="order",in="query",description="ASC or DESC",@OA\Schema(type="array",@OA\Items(type="string",enum={"ASC","DESC"} ,default="ASC") )),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Return current user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     *
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function members(Request $request): Response
    {

        $data = $this->userManager->getAccountMembres($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * @Route("/me", name="me",methods={"GET"})
     * @OA\Get(
     *  tags={"Users"},
     *   summary="Show User parameters",
     *   description="Show current identified User ,when authenticated with Bearer jwt token ",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Return current user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class,groups={"me"}))
     *     )
     * )
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @Security(name="Bearer")
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     */
    public function me(Request $request, UserFormalizeResponse $userFormalizerResponse): Response
    {

        // dd($this->getUser());
        $data = $this->userManager->findOne($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/users/{user_uuid}",name="user_edit",methods={"PATCH"})
     * @OA\Patch(
     *  tags={"Users"},
     *  summary="Edit User ",
     *  description="Edit an user ",
     * @OA\Parameter (name="user_uuid",description="unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",in="path" ),
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property( property="firstName",type="string",description="jhon"),
     *              @OA\Property( property="lastName",type="string",description="doe"),
     *              @OA\Property( property="phone",type="string",description="0143434343"),
     *              @OA\Property (property="isActive",type="boolean"),
     *              @OA\Property (property="isArchive",type="boolean"),
     *              @OA\Property (property="isConditionAgreed",type="boolean"),
     *      )),
     *     ),
     * ),
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success"),
     *
     *     ),),
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
     * @OA\Response(response="422",description="Unprocessable Entity",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="[{'fields':'email'},{'types':['This value should not be blank.','This value is not a valid email address.']}]"),
     *     ),),
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!"),
     * @Security(name="Bearer")
     */
    public function update_user(Request $request, UserUpdateValidator $userUpdateValidator): Response
    {

        $data = $userUpdateValidator->init($this->getUser());

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * Ask with the appropriate email account to renew the password. A mail will be send .Folow the mail instruction
     * @Route("/users/forgotten-password",name="forgotten_password",methods={"POST"})
     * @OA\Post(
     *     tags={"Users"},
     *     summary=" Ask for new password",
     *     description="Start process to renew the account password",
     *    ),
     * @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"email"},
     *              @OA\Property (property="email",description="user@company.com"),
     *
     *
     * )),
     *     @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"email"},
     *              @OA\Property (property="email",description="user@company.com"),
     *
     * ))
     * )
     * )
     * @OA\Response(response=200,description="User",@OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function forgotten_password(Request $request, UserEmailTokenIdentifier $userEmailTokenIdentifier): Response
    {
        $data = $userEmailTokenIdentifier->define($request, $userEmailTokenIdentifier::FROM_RESET);

        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * With valid JwtToken update  password
     * @Route ("/users/change-password",name="change_password",methods={"PUT"})
     * @OA\Put(
     *     tags={"Users"},
     *     summary="Change password",
     *     description="Change password from a forgotten password request or with a valid Authorization ",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"password","_password"},
     *              @OA\Property (property="password",description="user password"),
     *              @OA\Property (property="_password",description="verify password"),
     * )),
     * @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"password","_password"},
     *              @OA\Property (property="password",description="user password"),
     *              @OA\Property (property="_password",description="verify password"),
     *
     * ))
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
    public function change_password(Request $request, UserResetPassword $userResetPassword): Response
    {

        $data = $userResetPassword->init($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * With valid JwtToken accept terms of use
     * @Route ("/users/accept-term",name="accept_term",methods={"PUT"})
     * @OA\Put(
     *     tags={"Users"},
     *     summary="accept term of usage",
     *     description="accept term of use to be able to work with in API",
     * @OA\RequestBody(
     *    @OA\MediaType(mediaType="application/json",
     *        @OA\Schema(
     *              required={"isConditionAgreed"},
     *              @OA\Property (property="isConditionAgreed",type="boolean",default=true )
     * )),
     * @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"isConditionAgreed"},
     *              @OA\Property (property="isConditionAgreed",type="boolean",default=true )
     * ))
     * ))
     * @OA\Response(response=200,description="User",@OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function accept_term(Request $request, UserAcceptTerm $userAcceptTerm): Response
    {

        $data = $userAcceptTerm->acceptTerm($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }

    /**
     * Verify if the password is correct
     * @Route("/users/verify-password",name="verify_password",methods={"POST"})
     * @OA\Post  (
     *     tags={"Users"},
     *     summary="Verify password",
     *     description="Verify if you both password are the same",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"password"},
     *               @OA\Property( property="password",type="string",description="password",description="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60"),
     *
     *     )),
     *  @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"password"},
     *               @OA\Property( property="password",type="string",description="password",description="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60"),
     *
     *     ))
     * )))
     *
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Valid credential!"),
     *              @OA\Property( property="data",example="{'isValid':'true'}"),
     *
     *     ),),
     * @OA\Response(response="422",description="Unprocessable entity",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example=" Invalid credential"),
     *     ))
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function verify_password(Request $request, UserPasswordVerify $userPasswordVerify)
    {
        $jsonResponse = $userPasswordVerify->init($this->getUser());

        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }

    /**
     * @Route("/users/dashboard",name="dashboard",methods={"GET"})
     * @OA\Get (
     *      tags={"Users"},
     *      summary="User's info",
     *      description="Display curent logged user information ",
     *      @OA\Parameter (name="account_uuid",description="unique identifier ex:6002679b-347f-4cf4-b2a9-cc71671c4410",@OA\Schema(type="string"),in="query"),
     * )
     * @OA\Response(response=200,description="Return current user",
     *     @OA\JsonContent(@OA\Property( property="code",example="200"),
     *        @OA\Property( property="message",example="Success"),
     *        @OA\Property( property="data",example="{'infosVideos':{'totalVideos':0,'totalUsedStorage':0,'totalEncode':0,'totalGainCarbon':0},'infosCredit':{'totalEncode':0,'totalStorage':0}"),
     *     )
     * )
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function dashboard(Request $request, UserManager $userManager)
    {
        $jsonResponse = $userManager->userInfos($this->getUser());
        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }



    /**
     * @Route("/users/account",name="create",methods={"POST"})
     * @OA\Post(
     *     tags={"Users"},
     *     summary="create User",
     *     description="create new user  with videoEngage package(optional) valide=true and isConditionAgreed=true",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"password"},
     *              @OA\Property(property="email",type="string",description="email should be of type email test@email.com"),
     *              @OA\Property(property="pwd",type="string",description="generic password"),
     *
     *     ),),
     * ),)
     * @OA\Response(
     *     response="201",description="Created",@OA\JsonContent(
     *     @OA\Property(property="code",example="200"),
     *     @OA\Property(property="message",example="Created")
     *
     * )
     * )
     * @IsGranted("ROLE_DEV",message="This account is forbidden!")
     * @Security(name="Bearer")
     */
    public function createUser(Request $request)
    {

        $data = $this->userManager->createAccountForVideoEngage();
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }



    /**
     * @Route("/accounts/{account_uuid}/swap",name="switch",methods={"PATCH"})
     * @OA\Patch(
     *     tags={"Accounts"},
     *     summary="Switch role user to pilote",
     *     description="pilote give his  role to user ",
     *     @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"user_uuid"},
     *               @OA\Property( property="user_uuid",type="string",description="user_uuid"),
     *
     *     )),)
     *
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
    public function changeAdminRole(Request $request)
    {
        $data = $this->userManager->switchUserRole($this->getUser());
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }


    /**
     * @Route("/users/{user_uuid}/toggle", name="toggle",methods={"PATCH"})
     * @OA\Patch(
     *  tags={"Users"},
     *   summary="toggle User",
     *   description="active/desactive user",
     * )
     * @OA\Response(
     *     response=200,
     *     description="User Successfully edited",
     * )
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ))
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="Expired JWT Token"),
     *     ))
     * @Security(name="Bearer")
     * @IsGranted("ROLE_USER",message="This account is forbidden!")
     */
    public function toggleUser(string $user_uuid): Response
    {

        $data = $this->userManager->toggleUser($user_uuid);
        return new JsonResponse($data->displayData(), $data->displayHeader());
    }
}
