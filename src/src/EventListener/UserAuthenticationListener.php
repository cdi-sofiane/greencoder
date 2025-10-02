<?php

namespace App\EventListener;

use App\Entity\Account;
use App\Entity\User;
use App\Services\JsonResponseMessage;
use App\Services\Video\VideoManager;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class UserAuthenticationListener
{

    private $account;

    public function __construct()
    {
    }

    public function onUserAuthentication(RequestEvent $event)
    {

        return (new JsonResponseMessage)->setCode('500')->setError('fuck');
        if (!$event->isMasterRequest()) {
            return;
        }
        $eventRequest = $event->getRequest();

        if ($eventRequest->get('_route') != 'video_encode') {
            return;
        }
        $video = $eventRequest->request->get('currentVideo');
        if ($video == null) {
            return;
        }
        $uploadFile = $eventRequest->files->get('file');
        return $this->videoManager->encodeResponse($uploadFile);
    }
}
