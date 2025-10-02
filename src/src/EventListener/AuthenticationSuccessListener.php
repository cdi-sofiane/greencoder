<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use \Symfony\Component\HttpFoundation\Response;

class AuthenticationSuccessListener
{
    private $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        /**
         * @var User $user
         */
        $user = $event->getUser();

        $response = [
            'code' => Response::HTTP_FORBIDDEN,
            'error' => 'This user is forbidden!'
        ];

        $isAccountActive = true;
        $accounts = $user->getUserAccountRole()->filter(function ($userAccountRole) {
            if ($userAccountRole->getAccount()->getIsActive() == true) {
                return $userAccountRole->getAccount();
            };
        });


        if ($accounts->count() <= 0) {
            $response = [
                'code' => Response::HTTP_FORBIDDEN,
                'error' => 'This account is inactive!'
            ];
            $isAccountActive = false;
        }

        if ($user->getIsActive() === true && $user->getIsArchive() === false && $isAccountActive) {

            $user->setLastConnection(new DateTimeImmutable('now'));
            $this->userRepository->update($user);

            $response = [
                'code' => $event->getResponse()->getStatusCode(),
                "isConditionAgreed" => $user->getIsConditionAgreed(),
                'token' => $event->getData()['token']
            ];
        }

        $event->getResponse()->setStatusCode($response['code']);
        $event->setData($response);
    }
}
