<?php

namespace App\Services\Users;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\AbstactValidator;
use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAcceptTerm extends AbstactValidator
{

    private $validator;
    private $request;
    private $userRepository;

    public function __construct(RequestStack $request, ValidatorInterface $validator, UserRepository $userRepository)
    {
        $this->validator = $validator;
        $this->request = $request->getCurrentRequest();
        $this->userRepository = $userRepository;
    }

    public function acceptTerm($user)
    {

        $body = $this->request->request->all() != null ? $this->request->request->all() : json_decode($this->request->getContent(), true);
        $body['isConditionAgreed'] = isset($body['isConditionAgreed']) != null ? $body['isConditionAgreed'] : '';
        /**@var User $user */

        $user->setIsConditionAgreed($body['isConditionAgreed']);
        $err = $this->validator->validate($user, null, ['term']);

        if ($err->count() > 0) {
            return $this->err($err);
        }
        $this->userRepository->update($user);

        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError($user->getIsConditionAgreed() === true ? ['Term accepted!'] : ['Term rejected!']);
    }
}