<?php

namespace App\EventListener;


use App\Services\Video\VideoManager;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class UploadAndEncodeListener
{

    private $videoManager;

    public function __construct(VideoManager $videoManager)
    {
        $this->videoManager = $videoManager;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {

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
