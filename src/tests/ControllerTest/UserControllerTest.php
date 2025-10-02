<?php

namespace App\Tests\ControllerTest;

use App\Repository\ForfaitRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;


    public function testUserUpdateRoleDevNoUuidNoParametersInContentAndRequest()
    {
        $this->client = $this->createAuthenticatedClient('a@a1.com');

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/a ', [], [], []);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider userGoodData
     */
    public function testUserUpdateRoleDevWithGoodParametersInContent($userGoodData)
    {
        $this->client = $this->createAuthenticatedClient('a@a1.com');

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], json_encode($userGoodData));
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    /**
     * @dataProvider userBadData
     */
    public function testUserUpdateRoleDevWithBadParameters($userBadData)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], json_encode($userBadData));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }





    /**
     * @dataProvider userGoodData
     */
    public function testUpdateUserRolePiloteWithUserUuid($userGoodData)
    {
        $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'xsekio@gmail.com']);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        $userGoodData = array_merge($userGoodData, ['account_uuid' => $MyAccount->getAccount()->getUuid()]);

        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [],  json_encode($userGoodData));
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider userGoodData
     */
    public function testUpdateUserRoleUserWithPiloteUuid($userGoodData)
    {
        $this->client = $this->createAuthenticatedClient("a@a3.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a3.com');
        $userGoodData = array_merge($userGoodData, ['account_uuid' => $MyAccount->getAccount()->getUuid()]);
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getUuid(),
            [],
            [],
            [],
            json_encode($userGoodData)
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider userBadData
     */
    public function testUserUpdateRoleVidmizerNotMyUuidBadParametersInRequest($userBadData)
    {
        $this->client = $this->createAuthenticatedClient("a@a2.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], json_encode($userBadData));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider userGoodData
     */
    public function testUpdateUserRoleUserNotMyUuidGoodParam($userGoodData)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a3.com');
        $userGoodData = array_merge($userGoodData, ['account_uuid' => $MyAccount->getAccount()->getUuid()]);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], json_encode($userGoodData));
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserUpdateRoleUserNotMyUuidGoodParametersInRequest()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [
            "firstName" => "jhone",
            "lastName" => "doe",
            "siret" => "1234567",
            "company" => "TOTO",
            "phone" => "",
            "address" => "100 rue des Martires",
            "postalCode" => "78900",
            "country" => "FRANCE",
            "usages" => "Professional",
            "tva" => "5,5"
        ], [], [], '{

}');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserUpdateRoleUserNotMyUuidBadParametersInContent()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], '{
    "firstName":"jhone",
    "lastName":"doe",
    "siret":"1234567",
    "company":"TOTO",
    "phone":"1",
    "address":"100 rue des Martires",
    "postalCode":"78900",
    "country":"FRANCE",
    "usages":"Professional",
    "tva":"5,5"
}');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserUpdateRoleUserNotMyUuidBadParametersInRequest()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a2.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [
            "firstName" => "jhone",
            "lastName" => "doe",
            "siret" => "1234567",
            "company" => "TOTO",
            "phone" => "1",
            "address" => "100 rue des Martires",
            "postalCode" => "78900",
            "country" => "FRANCE",
            "usages" => "Professional",
            "tva" => "5,5"
        ], [], [], '{

}');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserUpdateRoleUserGoodUuidGoodParametersInContent()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [], [], [], '{
    "firstName":"jhone",
    "lastName":"doe",
    "siret":"1234567",
    "company":"TOTO",
    "phone":"",
    "address":"100 rue des Martires",
    "postalCode":"78900",
    "country":"FRANCE",
    "usages":"Individual",
    "tva":"5,5"
}');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUserUpdateRoleUserGoodUuidGoodParametersInRequest()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('PATCH', '/api/users/' . $user->getUuid(), [
            "firstName" => "jhone",
            "lastName" => "doe",
            "siret" => "1234567",
            "company" => "TOTO",
            "phone" => "",
            "address" => "100 rue des Martires",
            "postalCode" => "78900",
            "country" => "FRANCE",
            "usages" => "Individual",
            "tva" => "5,5"
        ], [], [], '{

}');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUserVerifyPasswordGoodPassword()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('POST', '/api/users/verify-password', [], [], [], '{"password" : "Az1-tyze"}');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUserVerifyPasswordBadPassword()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");

        $this->client->request('POST', '/api/users/verify-password', [], [], [], '{"password" : "Az1-tyze5"}');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  passwordBadPair
     */
    public function testChangePasswordBadPair($passwordBadPair)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('PUT', '/api/users/change-password', [], [], [], json_encode($passwordBadPair));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }



    public function testRoleDevDashboard()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        $this->client->request('GET', '/api/users/dashboard', ['account_uuid' => $MyAccount->getAccount()->getUuid()]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleUserDashboard()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        $this->client->request('GET', '/api/users/dashboard', ['account_uuid' => $MyAccount->getAccount()->getUuid()]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleUserDashboardBadUserUuid()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a2.com');
        $this->client->request('GET', '/api/users/dashboard', ['account_uuid' =>  $MyAccount->getAccount()->getUuid()]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRoleDevApiKeyGeneration()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a1.com');
        $this->client->request('PATCH', '/api/accounts/' . $MyAccount->getAccount()->getUuid() . '/api-key');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleDevApiKeyGenerationUser()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        $this->client->request('PATCH', '/api/accounts/' . $MyAccount->getAccount()->getUuid() . '/api-key');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleUserApiKeyGenerationGoodUser()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        $this->client->request('PATCH', '/api/accounts/' . $MyAccount->getAccount()->getUuid() . '/api-key');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testConnectWithVideoEngageNewUser()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request(
            'POST',
            '/api/users/account',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'email' => 'sofiens.argoubi@vidmizer.com',
                'pwd' => '4c072c5af87260592415a48264122a5fc70da1878e13ccef4d73d3b4e4f7987a9ec4faffb19eeb83b1ec3955b14ff179df63d90868383b5675a0b255784652d8926e45107b3b93aa3865e678b3f6a215cf2493734ab4e22ecac3862c8331180979c99d64f8900c310df084a446b5eced27e4bbf75b993e9456876d503b2a4f1a87433cbad662818d571cbf5eb9a0696fec855dfb606578dd066bb8157b6f199fec8d74ff3f457e1fe4dc6fb093ae83c09c56a3f116e23a529244f2ad9e77d4068636a9b603ada989e00e0d6f89b4505d352db7c93477afde46a031a76c0df01a4455fdac1f157d3977faf5b900ee7ee259693c37a27212ef8849a90d20b809c6d4fcbbd3950c21caa5f1271fc210a552261d3a2276ea87d0508f17ffbf591d4cef0699e189e38b961b4816388bbbc6310d98f7f3415f4af3f3f61768418b8b356a91275bd2254bca990bd3402b8203c9da15a54f092128cac2f3c8426558bbfbe8b91b7e43f46ac69a3cabf7e37efff7a7e41c968cb376dc66df1c54498e591f99846eb0aa448d792fcc44796aade70f44e168928a1cd888943e7c8c5171bd59c046f1ed27af84dda9ea98903b72c19551602707567fd413d0b241830a010a386896ffd6c64f2268e2751697ac17eb46e20a8567460ca5c067fc41d91f11540ef3859bc52a8f30c092c125367baec4e9ea207f4c2a121bf640fd91cda65ed152',
                'storage' => true
            ))
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }



    /**
     * @dataProvider badDataConnectVideoEngage
     */
    public function testConnectWithVideoEngageBadData($badDataConnectVideoEngage)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request(
            'POST',
            '/api/users/account',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($badDataConnectVideoEngage)
        );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRoleUserApiKeyGenerationBadUser()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a1.com');
        $this->client->request('PATCH', '/api/accounts/' . $MyAccount->getAccount()->getUuid() . '/api-key');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRoleDevSubscribePackageAbonnementStorage()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');
        $packageRepository = self::$container->get(ForfaitRepository::class);
        $pack = $packageRepository->findOneBy(['name' => 'pack stockage sup']);

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid(),
                'account_uuid' => $MyAccount->getAccount()->getUuid()
            ))
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
    public function testRoleDevSubscribePackageAbonnementEncodage()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');

        $packageRepository = self::$container->get(ForfaitRepository::class);
        $pack = $packageRepository->findOneBy(['name' => 'pack encodage']);

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid(),
                'account_uuid' => $MyAccount->getAccount()->getUuid()
            ))
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
    public function testConnectWithVideoEngageOldUser()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request(
            'POST',
            '/api/users/account',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'email' => 'cdi.sofiane@gmail.com',
                'pwd' => '4c072c5af87260592415a48264122a5fc70da1878e13ccef4d73d3b4e4f7987a9ec4faffb19eeb83b1ec3955b14ff179df63d90868383b5675a0b255784652d8926e45107b3b93aa3865e678b3f6a215cf2493734ab4e22ecac3862c8331180979c99d64f8900c310df084a446b5eced27e4bbf75b993e9456876d503b2a4f1a87433cbad662818d571cbf5eb9a0696fec855dfb606578dd066bb8157b6f199fec8d74ff3f457e1fe4dc6fb093ae83c09c56a3f116e23a529244f2ad9e77d4068636a9b603ada989e00e0d6f89b4505d352db7c93477afde46a031a76c0df01a4455fdac1f157d3977faf5b900ee7ee259693c37a27212ef8849a90d20b809c6d4fcbbd3950c21caa5f1271fc210a552261d3a2276ea87d0508f17ffbf591d4cef0699e189e38b961b4816388bbbc6310d98f7f3415f4af3f3f61768418b8b356a91275bd2254bca990bd3402b8203c9da15a54f092128cac2f3c8426558bbfbe8b91b7e43f46ac69a3cabf7e37efff7a7e41c968cb376dc66df1c54498e591f99846eb0aa448d792fcc44796aade70f44e168928a1cd888943e7c8c5171bd59c046f1ed27af84dda9ea98903b72c19551602707567fd413d0b241830a010a386896ffd6c64f2268e2751697ac17eb46e20a8567460ca5c067fc41d91f11540ef3859bc52a8f30c092c125367baec4e9ea207f4c2a121bf640fd91cda65ed152',
                'storage' => true
            ))
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    public function testRoleDevSubscribePackageNotAbonnement()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');
        $packageRepository = self::$container->get(ForfaitRepository::class);
        $pack = $packageRepository->findOneBy(['name' => 'credit']);
        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid(),
                'account_uuid' => $MyAccount->getAccount()->getUuid()
            ))
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function badDataConnectVideoEngage()
    {
        $pwd = '4c072c5af87260592415a48264122a5fc70da1878e13ccef4d73d3b4e4f7987a9ec4faffb19eeb83b1ec3955b14ff179df63d90868383b5675a0b255784652d8926e45107b3b93aa3865e678b3f6a215cf2493734ab4e22ecac3862c8331180979c99d64f8900c310df084a446b5eced27e4bbf75b993e9456876d503b2a4f1a87433cbad662818d571cbf5eb9a0696fec855dfb606578dd066bb8157b6f199fec8d74ff3f457e1fe4dc6fb093ae83c09c56a3f116e23a529244f2ad9e77d4068636a9b603ada989e00e0d6f89b4505d352db7c93477afde46a031a76c0df01a4455fdac1f157d3977faf5b900ee7ee259693c37a27212ef8849a90d20b809c6d4fcbbd3950c21caa5f1271fc210a552261d3a2276ea87d0508f17ffbf591d4cef0699e189e38b961b4816388bbbc6310d98f7f3415f4af3f3f61768418b8b356a91275bd2254bca990bd3402b8203c9da15a54f092128cac2f3c8426558bbfbe8b91b7e43f46ac69a3cabf7e37efff7a7e41c968cb376dc66df1c54498e591f99846eb0aa448d792fcc44796aade70f44e168928a1cd888943e7c8c5171bd59c046f1ed27af84dda9ea98903b72c19551602707567fd413d0b241830a010a386896ffd6c64f2268e2751697ac17eb46e20a8567460ca5c067fc41d91f11540ef3859bc52a8f30c092c125367baec4e9ea207f4c2a121bf640fd91cda65ed152';
        yield [['email' => 'sofien2é@esprit.tn', 'pwd' => $pwd, 'storage' => true]];
        yield [['email' => 'sofien@esprit.tn', 'pwd' => 'pwd', 'storage' => true]];
    }
    public function passwordGoodPair()
    {
        yield [['password' => 'Azerty-45', '_password' => 'Azerty-45']];
        yield [['password' => 'ATrty-45', '_password' => 'ATrty-45']];
        yield [['password' => 'ATrty-45', '_password' => 'ATrty-45']];
        yield [['password' => 'ATrsy-45', '_password' => 'ATrsy-45']];
        yield [['password' => 'ATrty-4_e', '_password' => 'ATrty-4_e']];
        yield [['password' => '1Trty-è5', '_password' => '1Trty-è5']];
        yield [['password' => 'ATrt-445', '_password' => 'ATrt-445']];
    }
    public function userGoodData()
    {

        yield [['firstName' => 'sofiane', 'phone' => '']];
        yield [['firstName' => 'updated', 'lastName' => 'yes', 'phone' => '1234567890']];
    }
    public function userBadData()
    {

        yield [['firstName' => 'sofiane', 'phone' => 'A']];
        yield [['firstName' => 'updated', 'lastName' => 'yes', 'phone' => '12567890']];
    }
    public function passwordBadPair()
    {
        yield [['password' => 'Azerty-45', '_password' => 'Azderty-45']];
        yield [['password' => 'ATrty-45', '_password' => 'ATrdty-45']];
        yield [['password' => 'ATrty-45', '_password' => 'ATrdty-45']];
        yield [['password' => 'ATrsy-45', '_password' => 'ATrsdy-45']];
        yield [['password' => 'ATrty-4_e', '_password' => 'ATrdty-4_e']];
        yield [['password' => '1Trty-è5', '_password' => '1Trdy-è5']];
        yield [['password' => 'ATrt-445', '_password' => 'ATrdt-445']];
    }
}
