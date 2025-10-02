<?php

namespace App\Services\Users;

use App\Services\JsonResponseMessage;
use App\Services\AbstactValidator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPasswordVerify extends AbstactValidator
{
    protected $userPasswordEncoder;
    public $request;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder, RequestStack $request)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->request = $request->getCurrentRequest();
    }

    public function init($user)
    {
        if (empty($this->request->request->all()) || ($this->request->request->all() === null)) {
            $body = json_decode($this->request->getContent(), true);
        } else {
            $body = $this->request->request->all();
        }

        $isValid = $this->userPasswordEncoder->isPasswordValid($user, $body['password']);
        $data['isValid'] = false;
        if ($isValid === true) {
            $data['isValid'] = $isValid;
            $message = ['Valid credential!'];
            return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setContent($data)->setError($message);
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)->setError(['Invalid credential!']);
    }
}
