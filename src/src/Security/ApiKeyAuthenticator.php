<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\ApiKeyService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $JWTTokenManager;
    private $apiKeyService;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, JWTTokenManagerInterface $JWTTokenManager, ApiKeyService $apiKeyService, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->apiKeyService = $apiKeyService;
        $this->userRepository = $userRepository;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): bool
    {
        return 'security_api_login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $body = json_decode($request->getContent(), true);

        return $body;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {

        if (null === $credentials) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            return null;
        }
        // The "username" in this case is the apiToken, see the key `property`
        // of `your_db_provider` in `security.yaml`.
        // If this returns a user, checkCredentials() is called next:

        return $this->apiKeyService->valide_user($credentials['apiKey']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // Check credentials - e.g. make sure the password is valid.
        // In case of an API token, no credential check is needed.

        // Return `true` to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        // on success, let the request continue
        $response = [
            'code' => Response::HTTP_FORBIDDEN,
            'error' => 'This account is forbidden!'
        ];
        /**
         * @var User $user
         */
        $user = $token->getUser();

        $jwtToken = $this->JWTTokenManager->create($user);

        if ($user->getIsActive() === true && $user->getIsArchive() === false) {

            $response = [
                'code' => Response::HTTP_OK,
                "isConditionAgreed" => $user->getIsConditionAgreed(),
                'token' => $jwtToken
            ];
        }
        return new JsonResponse($response, $response['code']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
