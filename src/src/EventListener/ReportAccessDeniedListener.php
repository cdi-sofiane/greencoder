<?php

namespace App\EventListener;

use App\Entity\Report;
use App\Entity\User;
use App\Services\JsonResponseMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\EventDispatcher\Event;

class ReportAccessDeniedListener implements EventSubscriberInterface
{
  const REPORT_ROUTE = ['reports_find', 'reports_one', 'reports_config_edit', 'reports_config', 'reports_remove', 'reports_generate'];
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

      KernelEvents::REQUEST => ['onMultiAccountRepportAccess', 3]
    ];
  }

  public function onMultiAccountRepportAccess(RequestEvent $event)
  {
    /**
     * @var User $user
     */
    $user = $this->security->getUser();


    $route = $event->getRequest()->attributes->get("_route");


    return;
  }
}
