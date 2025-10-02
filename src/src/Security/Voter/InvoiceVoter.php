<?php

namespace App\Security\Voter;

use App\Entity\Invoice;
use App\Entity\Role;
use App\Entity\User;
use App\Helper\RightsHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class InvoiceVoter extends Voter
{
    public const FIND_INVOICE = 'find_invoice';

    private $rightsHelper;

    public function __construct(RightsHelper $rightsHelper)
    {
        $this->rightsHelper = $rightsHelper;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, [
            self::FIND_INVOICE,

        ])) {
            throw new Exception("Resource not found", Response::HTTP_NOT_FOUND);
            return false;
        }
        if (!$subject instanceof \App\Entity\Invoice) {
            throw new Exception("Invoice not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            throw new Exception("User not found", Response::HTTP_NOT_FOUND);
            return false;
        }


        /**
         * Authorise les ADMINISTRAEUR VIDMIZER
         */
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::FIND_INVOICE:
                return $this->canFindInvoices($user, $subject);
                break;
            default:
                throw new Exception("Action not found", Response::HTTP_NOT_FOUND);
                return false;
                break;
        }

        return false;
    }

    private function canFindInvoices(User $user, Invoice $invoice): bool
    {
        $isGranted = $this->rightsHelper->verifyUserAccountRole($user, $invoice->getAccount(), [Role::ROLE_ADMIN]);
        if (!$isGranted) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }

        return true;
    }
}
