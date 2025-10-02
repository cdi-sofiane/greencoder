<?php

namespace App\Services\Users;

use App\Entity\User;
use App\Services\JsonResponseMessage;
use App\Repository\UserRepository;
use App\Services\MailerService;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserEmailTokenIdentifier
{
    public const FROM_REGISTER = "register";
    public const FROM_RESET = "reset";

    public $userRepository;
    public $JWTTokenManager;
    public $mailerService;

    public function __construct(UserRepository $userRepository, JWTTokenManagerInterface $JWTTokenManager, MailerService $mailerService)
    {
        $this->userRepository = $userRepository;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->mailerService = $mailerService;
    }

    /**
     * with a string "register" or "reset" it will allow the user account
     * to be activate or reset password from email/jwtToken
     *
     * @param Request $request
     * @param string $sting "register or reset"
     * @return JsonResponseMessage
     */

    public function define(Request $request, string $string): JsonResponseMessage
    {
        if ($string === self::FROM_REGISTER) {
            return $this->register($request);
        }
        if ($string === self::FROM_RESET) {
            return $this->reset($request);
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['not found']);
    }

    /**
     * activate account
     * depending on request result user will be activated or error response
     *
     * @param User $user
     *
     */
    private function register(Request $request): JsonResponseMessage
    {
        $object = $this->parameterValidation($request);
        if ($object instanceof JsonResponseMessage) {
            return $object;
        }
        $this->userRepository->activeAccount($object);
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Account has been activated']);
    }

    /**
     * check if jwtToken exist or isexpire
     * check if user exist
     *
     * @param $request
     * @return User|JsonResponseMessage
     */
    private function parameterValidation($request)
    {
        if ($request->query->get('key') == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['not found']);
        }
        try {
            $token = $this->JWTTokenManager->parse($request->query->get('key'));
        } catch (JWTDecodeFailureException $e) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_UNAUTHORIZED)->setError([$e->getMessage()]);
        }
        $user = $this->userRepository->findOneBy(['email' => $token['username']]);
        if ($user == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['not found']);
        }

        return $user;
    }

    /**
     * with email try if exist to rest password by sending a mail with jwtToken
     *
     * @param Request $request
     * @return JsonResponseMessage
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function reset(Request $request)
    {
        $body = json_decode($request->getContent('email'), true);

        $object = $this->userRepository->findOneBy(['email' => $body['email']]);
        if ($object == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['not found']);
        }
        $data['subject'] = 'Mot de passe oublié';

        $response= $this->mailerService->sendMail($object, MailerService::MAIL_RESET, $data);

        if ($response instanceof JsonResponseMessage) {
            return $response;
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Success']);
    }
}
