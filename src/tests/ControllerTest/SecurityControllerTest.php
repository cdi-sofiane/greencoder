<?php

namespace App\Tests\ControllerTest;

use App\Entity\User;
use App\Entity\Account;
use App\Repository\UserRepository;
use App\Repository\AccountRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use App\DataFixtures\UserFixtures;

use App\Services\ApiKeyService;

class SecurityControllerTest extends WebTestCase
{
    protected $databaseTool;
    use UserAuthorizationTokenTrait;


    public function testRegisterWithoutParameter()
    {

        $client = static::createClient();
        $crawler = $client->request('POST', 'api/register', [], [], [], '');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider newUser
     */
    public function testRegisterWithGoodParameter($newUser)
    {

        $client = static::createClient();
        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures(['App\DataFixtures\UserFixtures']);
        $crawler = $client->request('POST', 'api/register', [], [], [], json_encode($newUser));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testActiveUser()
    {
        $this->client = $this->createAuthenticatedClient('a@a1.com');
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'xsekio@gmail.com']);
        $user2 = $userRepository->findOneBy(['email' => 'cdi.sofiane@gmail.com']);
        /**@var User $user */
        $user->setIsActive(true)->setIsConditionAgreed(true);
        $userRepository->update($user);
        $user2->setIsActive(true)->setIsConditionAgreed(true);
        $userRepository->update($user2);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    public function testRegisterWithBadParameter()
    {
        $client = static::createClient();
        $crawler = $client->request('POST', 'api/register', [], [], [], '{
    "email":"s.vidmizer@gmail.com",
    "password":"azerty",
    "usages":"Professional",

}');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterExistingUser()
    {

        $client = static::createClient();
        $crawler = $client->request('POST', 'api/register', [], [], [], '{"email":"a@a1.com",
    "password":"Az1-tyze",
    "usages":"Professional"}');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    //LOGIN

    public function testLoginGoodEmailPassword()
    {
        $this->client = $this->createAuthenticatedClient('a@a4.com');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginGoodEmailPasswordIsNotActif()
    {
        $this->client = $this->createAuthenticatedClient('a@a3.com');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLoginBadEmailPassword()
    {
        $this->client = $this->createAuthenticatedClient('a@a7.com');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginGoodEmailBadPassword()
    {
        $this->client = $this->createAuthenticatedClient('a@a1.com', "azerty");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginBadApiKey()
    {
        $this->client = $this->createAuthenticatedClientApiKey('zae');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginGoodApiKeyIsNoActive()
    {
        $this->client = $this->createAuthenticatedClientApiKey('5a56d275949eb284b4884f30ed88a045');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLoginGoodApiKeyIsActived()
    {
        $client = static::createClient();
        $accountRepository = self::$container->get(AccountRepository::class);

        $apiKeyService = self::$container->get(ApiKeyService::class);
        $account =   $accountRepository->findOneBy(['email' => 'a@a4.com']);

        $key = $apiKeyService->decrypteApiKey($account->getApiKey());

        $this->client = $this->createAuthenticatedClientApiKey($key);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    public function newUser()
    {
        yield [["email" => "sofiane.lamara@vidmizer.com", "password" => "Az1-tyze", "usages" => "Professional", "company" => "Soso-Company"]];
        yield [["email" => "cdi.sofiane@gmail.com", "password" => "Az1-tyze", "usages" => "Individual"]];
    }
}
