<?php

namespace App\Services\Users;

use App\Services\JsonResponseMessage;
use App\Repository\UserRepository;
use App\Services\AbstactValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserResetPassword extends AbstactValidator
{
    public $validator;
    public $userRepository;
    public $request;

    public function __construct(ValidatorInterface $validator, UserRepository $userRepository, RequestStack $requestStack)
    {

        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function init($user)
    {
        $data = json_decode($this->request->getContent(), true);

        if ($data['password'] != $data['_password']) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)->setError(['Verify input(s)']);
        }
        $user = $user->setPassword($data['password']);

        $err = $this->validator->validate($user, null, ['resetpassword']);

        if ($err->count() > 0) {

            return $this->err($err);
        } else {
            $user = $this->userRepository->updatePassword($user);
            return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Password has been Successfuly changed']);
        }
    }
}
