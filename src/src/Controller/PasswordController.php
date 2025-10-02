<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\JsonResponseMessage;
use App\Helper\LoginJsonResponse;
use App\Repository\UserRepository;
use App\Services\UserValidatorInterface;
use App\Services\Users\UserEmailTokenIdentifier;
use App\Services\Users\UserResetPassword;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/", name="security_")
 */
class PasswordController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * to finish find a way to change status code en message response
     * @Route ("/activate",name="activate",methods={"GET"})
     */
    public function activate(Request $request, UserEmailTokenIdentifier $userEmailTokenIdentifier): Response
    {
        $data = $userEmailTokenIdentifier->define($request, $userEmailTokenIdentifier::FROM_REGISTER);
        return $this->redirect($_ENV['FRONT_BASE_URL'], 302);
    }

    /**
     * to finish find a way to change status code en message response
     * @Route ("/activate-invitation",name="activate_invite",methods={"GET"})
     */
    public function activateInvitation(Request $request, UserEmailTokenIdentifier $userEmailTokenIdentifier): Response
    {
        $data = $userEmailTokenIdentifier->define($request, $userEmailTokenIdentifier::FROM_REGISTER);
        return $this->redirect($_ENV['FRONT_INVITATION_BASE_URL'], 302);
    }
}
