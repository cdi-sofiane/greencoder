<?php

namespace App\Tests\ControllerTest;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Repository\AccountRepository;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HistoryControllerTest extends WebTestCase
{
  use UserAuthorizationTokenTrait;

  /**
   * @var WebTestCase $client
   */
  public $client;

  public function testFindAccountHistory()
  {

    $this->client = $this->createAuthenticatedClient("a@a1.com");
    $userRepository = self::$container->get(UserRepository::class);

    $this->client->request(
      'GET',
      '/api/historys/accounts'
    );
    $this->assertResponseStatusCodeSame(Response::HTTP_OK);
  }
}
