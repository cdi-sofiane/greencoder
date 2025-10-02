<?php


namespace App\Services\Permission\Account;

use App\Entity\AccountRoleRight;
use App\Entity\Role;
use App\Repository\RightRepository;
use App\Utils\RoleBuilder;
use Doctrine\ORM\EntityManagerInterface;

class AccountRoleRightService
{
  private $em;
  private $rightRepository;
  public function __construct(EntityManagerInterface $em, RightRepository $rightRepository)
  {
    $this->em = $em;
    $this->rightRepository = $rightRepository;
  }
  /**
   * for an account set default rights for each existing roles
   *
   * @param [type] $account
   * @return void
   */
  public function prepartAccountights($account)
  {
    $roleRepository = $this->em->getRepository(Role::class);
    $roles = $roleRepository->findAll();
    foreach ($roles as $role) {
     $this->initDefaultRight($role, $account);
    }
  }

  public function initDefaultRight(Role $role, $account)
  {
    switch ($role->getCode()) {
      case Role::ROLE_ADMIN:
        $rights = $this->rightRepository->findBy(['code' => RoleBuilder::defaultRole()[Role::ROLE_ADMIN]]);
        foreach ($rights as $right) {
          $accountRoleRight = new AccountRoleRight();
          $accountRoleRight->setAccount($account);
          $accountRoleRight->setRole($role);
          $accountRoleRight->setRights($right);
          $this->em->persist($accountRoleRight);
        }
        break;
      case Role::ROLE_EDITOR:
        $rights = $this->rightRepository->findBy(['code' => RoleBuilder::defaultRole()[Role::ROLE_EDITOR]]);
        foreach ($rights as $right) {
          $accountRoleRight = new AccountRoleRight();
          $accountRoleRight->setAccount($account);
          $accountRoleRight->setRole($role);
          $accountRoleRight->setRights($right);
          $this->em->persist($accountRoleRight);
        }
        break;
      case Role::ROLE_READER:
        $rights = $this->rightRepository->findBy(['code' => RoleBuilder::defaultRole()[Role::ROLE_READER]]);
        foreach ($rights as $right) {
          $accountRoleRight = new AccountRoleRight();
          $accountRoleRight->setAccount($account);
          $accountRoleRight->setRole($role);
          $accountRoleRight->setRights($right);
          $this->em->persist($accountRoleRight);
        }
        break;

      default:
        # code...
        break;
    }
    $this->em->flush();
  }
}
