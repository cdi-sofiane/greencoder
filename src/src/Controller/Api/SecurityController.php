<?php

namespace App\Controller\Api;

use App\Helper\LoginJsonResponse;
use App\Repository\UserRepository;
use App\Services\Users\UserRegisterValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


/**
 * @Route("/", name="security_")
 *
 */
class SecurityController extends AbstractController
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/register", name="register",methods={"POST"})
     *
     * @OA\Post(
     *   tags={"Authentication"},
     *   summary="Register new Account",
     *   description="Create a new account with new user as account pilote",
     *   @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *          @OA\Schema(
     *              required={"email","password","usages"},
     *              @OA\Property( property="email",type="string",description="email@test.com",description="NotBlank,type email"),
     *              @OA\Property( property="password",type="string",description="password",description="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60"),
     *              @OA\Property( property="usages",type="string",description="professional",description="Individual or Professional",enum={"Individual","Professional"} ,default="Professional"),),
     *
     *     )
     *      ),
     *  @OA\MediaType(mediaType="multipart/form-data",
     *          @OA\Schema(
     *              required={"email","password","usages"},
     *              @OA\Property( property="email",type="email",description="email@test.com",description="NotBlank,type email"),
     *              @OA\Property( property="password",type="string",description="password",description="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60"),
     *              @OA\Property( property="usages",type="array",description="professional",description="Individual or Professional",@OA\Items(type="string",enum={"Individual","Professional"} ,default="Professional"),),
     *
     *     )
     *      )
     *   ),
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="message",example="Success"),
     *     ),),
     * @OA\Response(response="201",description="Created",@OA\JsonContent(
     *              @OA\Property( property="code",example="201"),
     *              @OA\Property( property="message",example="Created"),
     *     ),),
     * @OA\Response(response="409",description="Conflict",@OA\JsonContent(
     *              @OA\Property( property="code",example="409"),
     *              @OA\Property( property="error",example="Conflict"),
     *     ),),
     * @OA\Response(response="422",description="Unprocessable Entity",@OA\JsonContent(
     *              @OA\Property( property="code",example="422"),
     *              @OA\Property( property="error",example="[{'fields':'email'},{'types':['This value should not be blank.','This value is not a valid email address.']}]"),
     *     ),),
     * )
     *
     */


    public function register(Request $request, UserRegisterValidator $userRegisterValidator): Response
    {
        $message = $userRegisterValidator->init($this->getUser());
        return new JsonResponse($message->displayData(), $message->displayHeader());
    }

    /**
     * @Route("/login", name="login",methods={"POST"})
     * @OA\Post(
     *   tags={"Authentication"},
     *   summary="Authentification",
     *   description="User Authentification with email, password",
     *   operationId="login",
     *   @OA\RequestBody(
     *     required=true,
     *   @OA\JsonContent(
     *     type="object",
     *              @OA\Property( property="username",example="",type="string",description="email ex:test@test.com"),
     *              @OA\Property( property="password",example="",type="string",description="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60"),
     *          )
     *
     *      )
     *   ),
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="token",example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NXyIfLk-PusRbBb-[.....]"),
     *     ),),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="This account is unauthorized!"),
     *     ),),
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ),),
     * )
     *
     */
    public function login(Request $request, LoginJsonResponse $loginJsonResponse)
    {
        return new Response();
    }

    /**
     * @Route("/authenticate", name="api_login",methods={"POST"})
     * @OA\Post(
     *   tags={"Authentication"},
     *   summary="Authentification",
     *   description="Authentification with PILOTE apiKey t",
     *   operationId="authenticate",
     *   @OA\RequestBody(
     *     required=true,
     *   @OA\JsonContent(
     *     type="object",
     *              @OA\Property( property="apiKey",example="",type="string",description="apiKey ex:12345678U5678HG"),
     *          )
     *
     *      )
     *   ),
     * @OA\Response(response="200",description="Success",@OA\JsonContent(
     *              @OA\Property( property="code",example="200"),
     *              @OA\Property( property="token",example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NXyIfLk-PusRbBb-[.....]"),
     *     ),),
     * @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(
     *              @OA\Property( property="code",example="401"),
     *              @OA\Property( property="error",example="This account is unauthorized!"),
     *     ),),
     * @OA\Response(response="403",description="Forbiden",@OA\JsonContent(
     *              @OA\Property( property="code",example="403"),
     *              @OA\Property( property="error",example="This account is forbidden!"),
     *     ),),
     * )
     *
     */
    public function api_login(Request $request, LoginJsonResponse $loginJsonResponse)
    {
        return new Response();
    }
}
