<?php

namespace App\Tests\ControllerTest;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Entity\UserAccountRole;
use App\Repository\AccountRepository;
use App\Repository\FolderRepository;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FolderControllerTest extends WebTestCase
{
  use UserAuthorizationTokenTrait;

  /**
   * @var WebTestCase $client
   */
  public $client;


  /**
   * @dataProvider rootAccountFolders
   *

   */
  public function testCreateFolderMyAccount($rootAccountFolders)
  {

    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $dataRootFolder = array_merge($rootAccountFolders, ["account_uuid" => $myAccount->getAccount()->getUuid()]);

    $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($dataRootFolder)


    );
    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }

  /**
   * @dataProvider rootAccountFoldersAsAdmin
   *

   */
  public function testCreateFolderAccountAsVidmizer($rootAccountFoldersAsAdmin)
  {

    $this->client = $this->createAuthenticatedClient("a@a1.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $dataRootFolder = array_merge($rootAccountFoldersAsAdmin, ["account_uuid" => $myAccount->getAccount()->getUuid()]);

    $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($dataRootFolder)


    );
    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }
  /**
   * @dataProvider firstChildFolders
   */
  public function testcreatefoldermyfirstchildrenfolder($firstChildFolders)
  {

    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $myRootFolder = $myAccount->getAccount()->getFolders();

    $parrentFolder = $myRootFolder->first();
    $firstChildFolders = array_merge($firstChildFolders, ["folder_uuid" => $parrentFolder->getUuid()]);

    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($firstChildFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }
  /**
   * @dataProvider firstChildFoldersAsAdmin
   *
   */
  public function testCreateFolderMyFirstChildfolderAsVidmizer($firstChildFoldersAsAdmin)
  {

    $this->client = $this->createAuthenticatedClient("a@a1.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $myRootFolder = $myAccount->getAccount()->getFolders();
    $parrentFolder = $myRootFolder->first();


    $firstChildFoldersAsAdmin = array_merge($firstChildFoldersAsAdmin, ["folder_uuid" => $parrentFolder->getUuid()]);
    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($firstChildFoldersAsAdmin)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }
  /**
   * @dataProvider lastChildFolders
   *
   */
  public function testCreateFolderMylastChildfolder($lastChildFolders)
  {

    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $folderRepositoryRepository = self::$container->get(FolderRepository::class);
    $firstLevelfolders = $folderRepositoryRepository->findBy(["account" => $myAccount, 'level' => 1]);
    $lastChildFolders = array_merge($lastChildFolders, ["folder_uuid" => $firstLevelfolders[0]->getUuid()]);


    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($lastChildFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }

  /**
   * @dataProvider level4ChildFolders
   *
   * @return void
   */
  public function testCreateFolderUSER_MyAccountLevel4($level4ChildFolders)
  {
    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $folderRepositoryRepository = self::$container->get(FolderRepository::class);
    $lastLevelfolders = $folderRepositoryRepository->findBy(["account" => $myAccount, 'level' => 2]);

    $level4ChildFolders = array_merge($level4ChildFolders, ["folder_uuid" => $lastLevelfolders[0]->getUuid()]);

    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($level4ChildFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
  }
  /**
   * @dataProvider rootBadAccountFolders
   *

   */
  public function testCreateFolderNotMyAccount($rootBadAccountFolders)
  {

    $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $rootBadAccountFolders = array_merge($rootBadAccountFolders, ["account_uuid" => $myAccount->getAccount()->getUuid()]);
    $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($rootBadAccountFolders)


    );
    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
  }
  /**
   * @dataProvider firstBadChildFolders
   *
   */
  public function testCreateFolderNotMyFirstChildfolder($firstBadChildFolders)
  {

    $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $myRootFolder = $myAccount->getAccount()->getFolders();
    $parrentFolder = $myRootFolder->first();
    $firstBadChildFolders = array_merge($firstBadChildFolders, ["folder_uuid" => $parrentFolder->getUuid()]);

    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($firstBadChildFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
  }

  /**
   * @dataProvider lastBadChildFolders
   *
   */
  public function testCreateFolderNotMylastChildfolder($lastBadChildFolders)
  {

    $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $folderRepositoryRepository = self::$container->get(FolderRepository::class);
    $firstLevelfolders = $folderRepositoryRepository->findBy(["account" => $myAccount, 'level' => 1]);

    $lastBadChildFolders = array_merge($lastBadChildFolders, ["folder_uuid" => $firstLevelfolders[0]->getUuid()]);
    $client = $this->client->request(
      'POST',
      '/api/folders',
      [],
      [],
      [],
      json_encode($lastBadChildFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
  }




  /**
   * @dataProvider rootAccountFolders
   *

   */
  public function testCreateFolderMyAccountDuplicateName($rootAccountFolders)
  {

    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $rootAccountFolders = array_merge($rootAccountFolders, ["account_uuid" => $myAccount->getAccount()->getUuid()]);
    $this->client->request(
      'POST',
      '/api/accounts/' . $myAccount->getAccount()->getUuid() . '/folders',
      [],
      [],
      [],
      json_encode($rootAccountFolders)


    );
    $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
  }
  /**
   * @dataProvider renameFolderData
   *
   * @return void
   */
  public function testRenameFolderNotMyAccountAsVidmizer($renameFolderData)
  {
    $this->client = $this->createAuthenticatedClient("a@a1.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $myRootFolder = $myAccount->getAccount()->getFolders();

    $parrentFolder = $myRootFolder->first();
    // $renameFolderData = array_merge($renameFolderData, ["folder_uuid" => $parrentFolder->getUuid()]);


    $client = $this->client->request(
      'PATCH',
      '/api/folders/' . $parrentFolder->getUuid(),
      [],
      [],
      [],
      json_encode($renameFolderData)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }
  /**
   * @dataProvider renameFolderAsEditorData
   *
   * @return void
   */
  public function testRenameFolderMyAccount($renameFolderAsEditorData)
  {
    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

    $myRootFolder = $myAccount->getAccount()->getFolders();

    $parrentFolder = $myRootFolder->first();

    $client = $this->client->request(
      'PATCH',
      '/api/folders/' . $parrentFolder->getUuid(),
      [],
      [],
      [],
      json_encode($renameFolderAsEditorData)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }

  /**
   * @dataProvider rootAccountFolders
   *

   */
  public function testRenameFolderNotMyAccountAsUser($rootAccountFolders)
  {

    $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
    $myRootFolder = $myAccount->getAccount()->getFolders();

    $parrentFolder = $myRootFolder->first();

    $client = $this->client->request(
      'PATCH',
      '/api/folders/' . $parrentFolder->getUuid(),
      [],
      [],
      [],
      json_encode($rootAccountFolders)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
  }


  public function testFolderInvitationToFolderNewUserWithInvitationRightAsEditor()
  {
    $this->client = $this->createAuthenticatedClient("a@a4.com");
    $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
    /**
     * @var UserAccountRole $myAccount
     */
    $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('az@a1z.com');

    $typeReaderAccount = $myAccount->getAccount()->getUserAccountRole()->filter(function ($userAccountRole) {
      return $userAccountRole->getRole()->getCode() == "reader";
    });

    $myRootFolder = $myAccount->getAccount()->getFolders();

    $parrentFolder = $myRootFolder->first();

    $childrens = $parrentFolder->getSubFolders();



    // $children = $parrentFolder->getSubFolders();
    //si l'utilisateur n'exist pas dans l account le creer en lecteur et ajouter le role definit pour le folder
    $targetFolder = [
      'folder_uuid' => $childrens[0]->getUuid(),
      'role' => "reader",
      'email' =>  "az@a1z.com"

    ];
    $this->client->request(
      'POST',
      '/api/folders/' . $childrens[0]->getUuid() . '/share',
      [],
      [],
      [],
      json_encode($targetFolder)
    );

    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //200
  }
  // public function testInvitationToFolderNewUserWithoutInvitationRightAsEditor()
  // {
  //   //400
  // }
  // public function testInvitationToFolderExistingUserWithInvitationRightAsEditor()
  // {
  //   //200
  // }
  // public function testInvitationToFolderExistingUserWithoutInvitationRightAsEditor()
  // {
  //   //400
  // }
  // public function testInvitationToFolderExistingUserAsVidmizer()
  // {
  //   //200
  // }
  // public function testInvitationToFolderNewUserAsVidmizer()
  // {
  //   //200
  // }



  public function rootBadAccountFolders()
  {
    yield [['name' => "not_my_account_folder_1"]];
    yield [['name' => "not_my_account_folder_2"]];
    yield [['name' => "not_my_account_folder_3"]];
  }

  public function firstBadChildFolders()
  {
    yield [['name' => "not_my_account_folder_1_1"]];
    yield [['name' => "not_my_account_folder_1_2"]];
    yield [['name' => "not_my_account_folder_1_3"]];
  }

  public function lastBadChildFolders()
  {
    yield [['name' => "not_my_account_folder_1_1_1"]];
    yield [['name' => "not_my_account_folder_1_1_2"]];
    yield [['name' => "not_my_account_folder_1_1_3"]];
  }

  public function rootAccountFolders()
  {
    yield [['name' => "account_folder_1"]];
    yield [['name' => "account_folder_2"]];
    yield [['name' => "account_folder_3"]];
  }
  public function rootAccountFoldersAsAdmin()
  {
    yield [['name' => "account_folder_1_ADMIN"]];
  }
  public function firstChildFolders()
  {
    yield [['name' => "account_folder_1_1"]];
    yield [['name' => "account_folder_1_2"]];
    yield [['name' => "account_folder_1_3"]];
  }
  public function firstChildFoldersAsAdmin()
  {
    yield [['name' => "account_folder_1_1_AS_ADMIN"]];
  }

  public function lastChildFolders()
  {
    yield [['name' => "account_folder_1_1_1"]];
    yield [['name' => "account_folder_1_1_2"]];
    yield [['name' => "account_folder_1_1_3"]];
  }
  public function level4ChildFolders()
  {
    yield [['name' => "account_folder_1_1_1_1"]];
    yield [['name' => "account_folder_1_1_1_2"]];
    yield [['name' => "account_folder_1_1_1_3"]];
  }
  public function renameFolderData()
  {
    yield [['name' => "rename_account_folder1"]];
    yield [['name' => "rename_account_folder2"]];
    yield [['name' => "rename_account_folder3"]];
  }
  public function renameFolderAsEditorData()
  {
    yield [['name' => "renameEditor_account_folder1"]];
    yield [['name' => "renameEditor_account_folder2"]];
    yield [['name' => "renameEditor_account_folder3"]];
  }
}
