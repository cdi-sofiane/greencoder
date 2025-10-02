<?php

namespace App\EventListener;


use App\Services\Video\VideoManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class CopyAndEncodeListener
{

    private $videoManager;
    private $log;

    public function __construct(VideoManager $videoManager, LoggerInterface $log)
    {
        $this->videoManager = $videoManager;
        $this->log = $log;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {

        if (!$event->isMasterRequest()) {
            return;
        }
        $eventRequest = $event->getRequest();

        if ($eventRequest->get('_route') != 'video_copy') {
            return;
        }

        $video = $eventRequest->request->get('selectedVideo');
        
        if ($video == null) {
            return;
        }
        return $this->videoManager->copyResponse($video);
    }
}
