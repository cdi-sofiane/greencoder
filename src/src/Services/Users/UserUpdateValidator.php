<?php

namespace App\Services\Users;

use App\Entity\User;
use App\Helper\RightsHelper;
use App\Services\JsonResponseMessage;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Services\AbstactValidator;
use App\Services\AuthorizationService;
use App\Services\UserValidatorInterface;
use App\Services\Storage\S3Storage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class UserUpdateValidator extends AbstactValidator
{

    public $validator;
    public $user;
    public $request;
    protected $passwordEncoder;
    protected $userRepository;
    protected $authorizationService;
    private $storage;
    private $urlGenerator;
    private $userVoter;
    private $security;
    private $rightsHelper;


    public function __construct(
        ValidatorInterface           $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        UserRepository               $userRepository,
        RequestStack                 $requestStack,
        AuthorizationService         $authorizationService,
        S3Storage                    $storage,
        UserVoter                    $userVoter,
        Security                     $security,
        RightsHelper                 $rightsHelper,
        UrlGeneratorInterface        $router

    ) {
        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->authorizationService = $authorizationService;
        $this->request = $requestStack->getCurrentRequest();
        $this->storage = $storage;
        $this->urlGenerator = $router;
        $this->security = $security;
        $this->userVoter = $userVoter;
        $this->rightsHelper = $rightsHelper;
    }

    public function init($user)
    {

        $body = json_decode($this->request->getContent(), true);


        // $targetUser = $this->authorizationService->getTargetUserOrNull($user);
        $targetUser = $this->userRepository->findOneBy(['uuid' => $this->request->attributes->get('user_uuid')]);
        $this->userVoter->vote($this->security->getToken(), $targetUser, [UserVoter::USER_EDIT]);

        isset($body["firstName"]) != null ? $targetUser->setFirstName($body["firstName"]) : null;
        isset($body["lastName"]) != null ? $targetUser->setLastName($body["lastName"]) : null;
        isset($body["phone"]) != null ? $targetUser->setPhone($body["phone"]) : $targetUser->setPhone('');
        isset($body["theme"]) != null ? $targetUser->setTheme($body["theme"]) : null;
        isset($body["lang"]) != null ? $targetUser->setLang($body["lang"]) : null;

        $targetUser->setUpdatedAt(new \DateTimeImmutable('now'));
        $admin_validation = '';

        /**
         * @todo creer des route dedier a cette notion
         */
        // if (
        //     array_intersect($user->getRoles(), array_merge(User::ACCOUNT_ADMIN_ROLES))
        // ) {
        //     isset($body["isActive"]) != null ? $targetUser->setIsActive($body["isActive"]) : '';
        //     isset($body["isArchive"]) != null ? $targetUser->setIsArchive($body["isArchive"]) : '';
        //     $admin_validation = 'update_admin';
        // }

        $err = $this->validator->validate($targetUser, null, ['update', $admin_validation]);
        if (count($err) > 0) {
            return $this->err($err);
        }

        $this->user = $targetUser;
        $this->userRepository->update($targetUser);
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setContent($targetUser)->setError(['User has been successfully edited!']);
    }
}
