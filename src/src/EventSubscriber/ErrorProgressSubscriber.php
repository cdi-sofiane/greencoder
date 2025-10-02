<?php

namespace App\EventSubscriber;

use App\Entity\Video;
use App\Repository\VideoRepository;
use App\Services\MailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorProgressSubscriber implements EventSubscriberInterface
{

    /**
     * @var MailerService
     */
    private $mailerService;
    /**
     * @var VideoRepository
     */
    private $videoRepository;

    public function __construct(MailerService $mailerService, VideoRepository $videoRepository)
    {

        $this->mailerService = $mailerService;
        $this->videoRepository = $videoRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => [['onEncodeProgressResponse', 1]]
        ];
    }

    public function onEncodeProgressResponse(TerminateEvent $event)
    {
        if ($event->getResponse()->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            return;
        };
        $eventRequest = $event->getRequest();
        if ($eventRequest->get('_route') != 'encoder_progress') {

            return;
        }

        switch ($eventRequest->server->all()['APP_DOMAINE']) {
            case 'https://app-xdemo.greenencoder.com':
            case 'https://app-api.greenencoder.com':
                $video = $this->videoRepository->findOneBy(['uuid' => $eventRequest->attributes->all()['video_uuid']]);

                $data = [
                    'video' => $video,
                    'subject' => "Alerte: encodage de vidéos en échec pour {$video->getUser()->getEmail()}",
                ];

                if ($video->getEncodingState() == Video::ENCODING_ERROR) {

                    $this->mailerService->sendErrorMail(MailerService::SUPPORT_MAIL, MailerService::MAIL_ENCODE_ERROR, $data);
                }
                break;
            default:
        }
    }
}