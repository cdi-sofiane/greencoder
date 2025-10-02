<?php

namespace App\Helper;

use App\Repository\UserRepository;
use App\Services\JsonResponseMessage;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class LoginJsonResponse
{
    protected $JwtEncodedEvent;
    protected $encoder;
    protected $userRepository;

    public function __construct(
        UserRepository               $userRepository,
        UserPasswordEncoderInterface $userPasswordEncoder,
        JWTTokenManagerInterface     $JwtEncodedEvent)
    {
        $this->encoder = $userPasswordEncoder;
        $this->JwtEncodedEvent = $JwtEncodedEvent;
        $this->userRepository = $userRepository;
    }

    public function setUp(Request $request)
    {

        $apiKey = $request->request->get('apiKey') == null ? $request->query->get('apiKey') : $request->request->get('apiKey');
        $users = $this->userRepository->findAll();
        $user = '';
        foreach ($users as $isValidUser) {
            if (password_verify($apiKey, $isValidUser->getAccount()->getApiKey())) {
                $user = $isValidUser;

            }
        }

        if ($user == '') {
            return (new JsonResponseMessage())->setCode(Response::HTTP_UNAUTHORIZED)->setError(['This account is unauthorized!']);
        }

        $jwtToken = $this->JwtEncodedEvent->create($user);
        if ($user->getIsActive() != true || $user->getIsArchive() != false || $user->getAccount()->getIsActive() != true  ) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError(['This account is forbidden!']);
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setToken($jwtToken);


    }
}