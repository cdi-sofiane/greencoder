<?php

namespace App\Services;

use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserValidatorInterface
{
    /**
     * @param  $user
     * @return mixed
     */
    public function init( $user);

    /**
     * @param array $message
     * @return JsonResponseMessage
     */
    public function err(array $message);

    /**
     *
     *@return JsonResponseMessage
     */
    public function success();

    /**
     * @return mixed
     */
    public function check_access(UserInterface $user);

}