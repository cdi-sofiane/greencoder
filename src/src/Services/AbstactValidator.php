<?php

namespace App\Services;

use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstactValidator
{
    private $request;

    public function __construct(RequestStack $request)
    {
        $this->request = $request->getCurrentRequest();
    }

    /**
     * Use any model to init a validation process,if entity  validator unsucessful
     * trow an error list else trow a success json response
     *
     * @param $entity
     *
     */
    public function init($entity)
    {
    }

    public function err($err)
    {
        $datas = '';
        $i = 0;
        foreach ($err as $currentErr) {
            $data[$i]['fields'] = $currentErr->getPropertyPath();
            $data[$i]['types'] = $currentErr->getMessage();
            $i++;
        }
        $datas = $data;

        return (new JsonResponseMessage())->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)->setContent($datas)->setError(['The Unprocessable entity !'])->setToken('');
    }

    protected function currentUserFinder($user = null, $group = null)
    {

        if ($user->getRoles()[0] === AuthorizationService::AS_USER) {
            $targetUser = $user;
        } else {

            $targetUser = $user;
            if ($targetUser == null) {
                $message = 'user not found!!';
                return $this->dataFormalizerResponse->extract(
                    null,
                    $group,
                    false,
                    $message,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            if ($targetUser->getRoles()[0] != AuthorizationService::AS_USER) {
                $targetUser = $user;
            }
        }
        return $targetUser;
    }
}
