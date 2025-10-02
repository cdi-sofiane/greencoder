<?php

namespace App\Tests\ControllerTest;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Entity\UserAccountRole;
use App\Repository\AccountRepository;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AccountControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;

    /**
     * @var WebTestCase $client
     */
    public $client;
    /**
     * @dataProvider queryFindAccountGood
     */
    public function testFindAccountAsPilotGood($queryFindAccountGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);

        $this->client->request(
            'GET',
            '/api/accounts',
            $queryFindAccountGood,
            [],
            []
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider queryFindAccountBad
     */
    public function testFindAccountAsPilotBad($queryFindAccountBad)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);

        $this->client->request(
            'GET',
            '/api/accounts',
            $queryFindAccountBad,
            [],
            []
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    /**
     * @dataProvider queryFindAccountBad
     */
    public function testFindAccountAsDevGood($queryFindAccountBad)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);

        $this->client->request(
            'GET',
            '/api/accounts',
            $queryFindAccountBad,
            [],
            []
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider queryEditAccountGood
     */
    public function testEditAccountAsPilotGood($queryEditAccountGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $userAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

        $this->client->request(
            'PATCH',
            '/api/accounts/' . $userAccount->getAccount()->getUuid(),
            [],
            [],
            [],

            json_encode($queryEditAccountGood)


        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    /**
     * @dataProvider queryEditAccountGood
     */
    public function testEditAccountAsPilotBadAccount($queryEditAccountGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $notMyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a1.com');

        $this->client->request(
            'PATCH',
            '/api/accounts/' . $notMyAccount->getAccount()->getUuid(),
            [],
            [],
            [],

            json_encode($queryEditAccountGood)


        );
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @dataProvider queryEditAccountGood
     */
    public function testEditAccountAsDevUserAccount($queryEditAccountGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $notMyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

        $this->client->request(
            'PATCH',
            '/api/accounts/' . $notMyAccount->getAccount()->getUuid(),
            [],
            [],
            [],

            json_encode($queryEditAccountGood)


        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    /**
     * @dataProvider queryEditAccountIsMultiGood
     */
    public function testMultiAccountAsDevToUserAccountPro($queryEditAccountIsMultiGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $accountRepository = self::$container->get(AccountRepository::class);
        $account = $accountRepository->findOneBy(['usages' => Account::USAGE_PRO]);

        $this->client->request(
            'PUT',
            '/api/accounts/' . $account->getUuid() . '/multi-account',
            [],
            [],
            [],

            json_encode($queryEditAccountIsMultiGood)


        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    /**
     * @dataProvider queryEditAccountIsMultiGood
     */
    public function testMultiAccountAsDevToUserAccountIndividual($queryEditAccountIsMultiGood)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $accountRepository = self::$container->get(AccountRepository::class);
        $account = $accountRepository->findOneBy(['usages' => Account::USAGE_INDIVIDUEL]);

        $this->client->request(
            'PUT',
            '/api/accounts/' . $account->getUuid() . '/multi-account',
            [],
            [],
            [],

            json_encode($queryEditAccountIsMultiGood)


        );
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }




    /**
     * @dataProvider dataEmails
     */
    public function testInvitationAccountRoleDevGoodAccountGoodEmailMultiAccount($dataEmails)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $accountRepository = self::$container->get(AccountRepository::class);
        $account = $accountRepository->findOneBy(['isMultiAccount' => true, "email" => "a@a4.com"]);

        $this->client->request(
            'POST',
            '/api/accounts/' . $account->getUuid() . '/invite',
            [],
            [],
            [],
            json_encode($dataEmails)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider dataEmailsPilote
     */
    public function testInvitationAccountRolePiloteGoodAccountGoodEmailMultiAccount($dataEmailsPilote)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $userAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        // dd($userAccount->getAccount()->getMaxInvitations());
        $this->client->request(
            'POST',
            '/api/accounts/' . $userAccount->getAccount()->getUuid() . '/invite',
            [],
            [],
            [],
            json_encode($dataEmailsPilote)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    /**
     * @dataProvider dataEmails
     */
    public function testInvitationAccountRolePiloteBadAccountGoodEmailMultiAccount($dataEmails)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $userAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a2.com');

        $this->client->request(
            'POST',
            '/api/accounts/' . $userAccount->getAccount()->getUuid() . '/invite',
            [],
            [],
            [],
            json_encode($dataEmails)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function queryFindAccountGood()
    {

        yield [["isMultiAccount" => true, "page" => 1, "limit" => 1, "search" => "", "sortBy" => 'date', "order" => 'ASC']];
        yield [["isMultiAccount" => false, "page" => 4, "limit" => 18, "search" => "ind", "sortBy" => 'name', "order" => 'DESC']];
        yield [["isMultiAccount" => true, "page" => 1, "limit" => 1, "search" => "", "sortBy" => 'date', "order" => 'ASC']];
    }

    public function queryFindAccountBad()
    {

        yield [["user_uuid" => '234567', "page" => 1, "limit" => 1, "search" => "", "sortBy" => 'name', "order" => 'ASC']];
    }

    public function queryEditAccountGood()
    {
        yield [[
            "email" => "a@a4.com",
            "usages" => "Professional",
            "name" => "string",
            "siret" => "stringemail",
            "company" => "string",
            "phone" => "string",
            "address" => "12345 ru des chat",
            "postalCode" => "string",
            "contry" => "ert",
            "tva" => "123456",
            "apiKey" => "NINJA",
            "maxInvitations" => 20
        ]];
        yield [[
            "email" => "a@a45.com",
            "name" => "string",
        ]];
    }

    public function queryEditAccountIsMultiGood()
    {
        yield [['isMultiAccount' => true]];
        yield [['isMultiAccount' => 1]];
        yield [['isMultiAccount' => '1']];
        yield [['isMultiAccount' => false]];
    }
    public function dataEmails()
    {
        yield [["email" => "az@a1z.com", "role" => "reader"]];
        yield [["email" => "az@a2z.com", "role" => "reader"]];
        yield [["email" => "az@a3z.com", "role" => "reader"]];
        yield [["email" => "az@a4z.com", "role" => "reader"]];
    }
    public function dataEmailsPilote()
    {
        yield [["email" => "az@a1pz.com", "role" => "reader"]];
        yield [["email" => "az@a2pz.com", "role" => "reader"]];
        yield [["email" => "az@a3pz.com", "role" => "reader"]];
        yield [["email" => "az@a4pz.com", "role" => "reader"]];
    }
}
