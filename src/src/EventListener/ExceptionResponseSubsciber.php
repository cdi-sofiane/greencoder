<?php

namespace App\EventListener;

use App\Entity\User;
use App\Services\JsonResponseMessage;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class ExceptionResponseSubsciber implements EventSubscriberInterface
{
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public static function getSubscribedEvents(): array
    {

        return [

            // the priority must be greater than the Security HTTP
            // ExceptionListener, to make sure it's called before
            // the default exception listener

            KernelEvents::EXCEPTION => ['onKernelException', 1]
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        // dd($exception);
        // if ($event->getRequest()->headers->get('Authorization') == null) {
        //     $code = $exception->getCode() == 0 ? Response::HTTP_UNAUTHORIZED : $exception->getCode();
        //     $data = (new JsonResponseMessage)->setCode(Response::HTTP_UNAUTHORIZED)->setError($exception->getMessage());
        //     $predata = ['code' => $data->getCode(), 'message' => $data->getError()];
        //     $event->setResponse(new JsonResponse($predata, $data->displayHeader()));
        //     return;
        // }
        $trace =  'files:' . $exception->getFile() . '__' . 'trace:' . $exception->getLine();
        // if ($exception instanceof NotFoundHttpException) {
        //     $code = $exception->getCode() == 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : $exception->getCode();
        //     $data = (new JsonResponseMessage)->setCode($exception->getStatusCode())->setError($exception->getMessage() . "-->" . $trace);
        //     $event->setResponse(new JsonResponse($data->displayData(), $data->displayHeader()));
        // }


        // if ($exception instanceof NotEncodableValueException) {
        //     $code = $exception->getCode() == 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : $exception->getCode();
        //     $data = (new JsonResponseMessage)->setCode($code)->setError($exception->getMessage() . "-->" . $trace);
        //     $event->setResponse(new JsonResponse($data->displayData(), $data->displayHeader()));
        // }
        // if ($exception instanceof Exception) {
        //     $code = $exception->getCode() == 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : $exception->getCode();
        //     $data = (new JsonResponseMessage)->setCode($code)->setError($exception->getMessage() . "-->" . $trace);
        //     $event->setResponse(new JsonResponse($data->displayData(), $data->displayHeader()));
        // }
        /**
         * todo si le code retour  ne fonctionne pas essay de le mettre ici sinon dans les voter ne plus envoyer les exeption
         */
        // if ($event->getRequest()->headers->get('Authorization') == null) {
        //     $code = $exception->getCode() == 0 ? Response::HTTP_UNAUTHORIZED : $exception->getCode();
        //     $data = (new JsonResponseMessage)->setCode(Response::HTTP_UNAUTHORIZED)->setError("Expired JWT Token");
        //     $predata = ['code' => $data->getCode(), 'message' => $data->getError()];
        //     $event->setResponse(new JsonResponse($predata, $data->displayHeader()));
        // }
    }
}
