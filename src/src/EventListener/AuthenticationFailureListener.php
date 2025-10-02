<?php

namespace App\EventListener;

use App\Services\JsonResponseMessage;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\Response;

class AuthenticationFailureListener
{
    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {

        $jsonResponseMessage=(new JsonResponseMessage)->setCode(Response::HTTP_UNAUTHORIZED)->setError(['This account is unauthorized!']);
        $event->setResponse(new JsonResponse($jsonResponseMessage->displayData(), $jsonResponseMessage->displayHeader()));
    }
}