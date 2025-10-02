<?php

namespace App\Services\Permission\Account;

use App\Entity\Account;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class UserAccountRolesService
{

  private $em;

  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;
  }

  public function addUserToAccount($account, $user, $role): ?UserAccountRole
  {
    $role = $this->em->getRepository(Role::class)->findOneBy(['code' => $role]);
    switch ($role->getCode()) {
      case 'admin':
        return $this->AdminRole($account, $user);
        break;
      case 'editor':
        return $this->EditorRole($account, $user);
        break;
      case 'reader':
        return $this->ReaderRole($account, $user);
        break;

      default:
        # code...
        break;
    }
  }

  public function AdminRole(Account $account, User $user): UserAccountRole
  {

    $roleRepository = $this->em->getRepository(Role::class);
    $role = $roleRepository->findOneBy(['code' => Role::ROLE_ADMIN]);

    return $this->createUserAccountRole($account,  $user, $role);
  }
  public function EditorRole(Account $account, User $user)
  {
    $roleRepository = $this->em->getRepository(Role::class);
    $role = $roleRepository->findOneBy(['code' => Role::ROLE_EDITOR]);

    return $this->createUserAccountRole($account,  $user, $role);
  }
  public function ReaderRole(Account $account, User $user)
  {
    $roleRepository = $this->em->getRepository(Role::class);
    $role = $roleRepository->findOneBy(['code' => Role::ROLE_READER]);

    return $this->createUserAccountRole($account,  $user, $role);
  }
  public function editUserToAccount(UserAccountRole  $userAccountRole, Role $role): UserAccountRole
  {

    $userAccountRole
      ->setRole($role);
    /**
     * @var UserAccountRoleRepository $userAccountRoleRepository
     */
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    $userAccountRole = $userAccountRoleRepository->add($userAccountRole);

    return $userAccountRole;
  }
  private function hasAdminRoleBeenSet()
  {
  }
  public function userAlreadyInAccount($account, $user): Bool
  {
    /**
     * @var \App\Repository\UserAccountRoleRepository $userAccountRepository
     */
    $userAccountRepository = $this->em->getRepository(UserAccountRole::class);
    $userAccountRole = $userAccountRepository->findOneBy(['user' => $user, 'account' => $account]);

    if ($userAccountRole) {
      return true;
    }
    return false;
  }

  private function createUserAccountRole(Account $account, User $user, Role $role): UserAccountRole
  {

    $userAccountRole = new UserAccountRole();
    $userAccountRole
      ->setAccount($account)
      ->setUser($user)
      ->setRole($role);
    /**
     * @var UserAccountRoleRepository $userAccountRoleRepository
     */
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    $userAccountRole = $userAccountRoleRepository->add($userAccountRole);

    return $userAccountRole;
  }


  /**
   * Find Role for User in Account
   *
   * @param User $user
   * @param Account $account
   * @return Role
   */
  public  function findUserRoleInAccount(User $user, Account $account): Role
  {
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    /**
     * @var \App\Entity\UserAccountRole $userAccountRole
     */
    $userAccountRole = $userAccountRoleRepository->findOneBy(['account' => $account, "user" => $user]);
    if ($userAccountRole == null) {
      throw new Exception("member not found", Response::HTTP_NOT_FOUND);
    }
    $role = $userAccountRole->getRole();
    return $role;
  }
  /**
   * find for an user  for one account its rights
   *  eg user (Account (role [rights]}))
   *
   * @return void
   */
  public  function findUserRightsInAccount()
  {
  }
  /**
   * fins all accounts of a user
   *
   * @return userAccountRole | null
   */
  public  function findUserAccounts($user, $account): ?UserAccountRole
  {
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    /**
     * @var \App\Entity\UserAccountRole $userAccountRole
     */
    $userAccountRole = $userAccountRoleRepository->findOneBy(['account' => $account, "user" => $user]);

    if ($userAccountRole == null) {
      throw new Exception("member not found", Response::HTTP_NOT_FOUND);
    }

    return $userAccountRole;
  }
  /**
   * find one Account of a user
   *
   * @return void
   */
  public  function findUserAccount($account, $user): Account
  {
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    /**
     * @var \App\Entity\UserAccountRole $userAccountRole
     */
    $userAccountRole = $userAccountRoleRepository->findOneBy(['account' => $account]);
    if ($userAccountRole == null) {
      throw new Exception("member not found", Response::HTTP_NOT_FOUND);
    }
    return $userAccountRole->getAccount();
  }

  public function findAdminAccount($account): UserAccountRole
  {


    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    /**
     * @var \App\Repository\UserAccountRoleRepository $userAccountRoleRepository
     */
    $userAccountRole = $userAccountRoleRepository->findAccountOwner($account);
    if ($userAccountRole == null) {
      throw new Exception("member not found", Response::HTTP_NOT_FOUND);
    }
    return $userAccountRole;
  }

  public function removeFromAccount($user, Account $account)
  {

    $userAccountRole = $this->findUserAccounts($user, $account);
    if ($userAccountRole->getRole()->getCode() == Role::ROLE_ADMIN) {
      throw new Exception("Can't remove account owner", Response::HTTP_FORBIDDEN);
    }
    $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
    /**
     * @var \App\Repository\UserAccountRoleRepository $userAccountRoleRepository
     */
    $userAccountRoleRepository->remove($userAccountRole);
  }
}
