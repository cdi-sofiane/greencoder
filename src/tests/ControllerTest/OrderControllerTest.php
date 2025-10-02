<?php

namespace App\Tests\ControllerTest;

use App\Entity\Forfait;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OrderControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;

    /**
     * @var WebTestCase $client
     */
    public $client;

    public function testUpgradeSubsciptedOrder()
    {
        /**
         */
        $this->client = $this->createAuthenticatedClient("a@a1.com");

        $userRepository = self::$container->get(UserRepository::class);
        $user2 = $userRepository->findOneBy(['email' => 'cdi.sofiane@gmail.com']);
        $user2->setIsActive(true)->setIsConditionAgreed(true);
        $userRepository->update($user2);

        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);

        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');


        $orderRepository = self::$container->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['account' => $MyAccount->getAccount(), 'isConsumed' => false]);

        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $pack = $forfaitRepository->findOneBy(['type' => Forfait::TYPE_ABONNEMENT, 'nature' => $order->getForfait()->getNature()]);

        $this->client->request(
            'POST',
            '/api/orders/' . $order->getUuid() . '/swap',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid()
            ))
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    public function testUpgradeSubsciptedOrderBadNature()
    {

        $this->client = $this->createAuthenticatedClient("cdi.sofiane@gmail.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');
        $orderRepository = self::$container->get(OrderRepository::class);
        $order = $orderRepository->findOneBy(['account' => $MyAccount->getAccount(), 'isConsumed' => false]);

        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $pack = $forfaitRepository->findOneBy(['type' => Forfait::TYPE_ABONNEMENT, 'nature' => Forfait::NATURE_ENCODAGE]);

        $this->client->request(
            'POST',
            '/api/orders/' . $order->getUuid() . '/swap',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid()
            ))
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }

    public function testDowngradeSubsciptedOrder()
    {

        $this->client = $this->createAuthenticatedClient("cdi.sofiane@gmail.com");

        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');

        $orderRepository = self::$container->get(OrderRepository::class);

        $order = $orderRepository->findOneBy(['account' => $MyAccount->getAccount(), 'isConsumed' => false]);

        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $pack = $forfaitRepository->findOneBy(['name' => 'pack stockage inf', 'nature' => Forfait::NATURE_STOCKAGE]);

        $this->client->request(
            'POST',
            '/api/orders/' . $order->getUuid() . '/swap',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid()
            ))
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    public function test0DowngradeSubsciptedOrderBadPack()
    {


        $this->client = $this->createAuthenticatedClient("cdi.sofiane@gmail.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $MyAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('cdi.sofiane@gmail.com');

        $orderRepository = self::$container->get(OrderRepository::class);

        $order = $orderRepository->findOneBy(['account' => $MyAccount->getAccount(), 'isConsumed' => false]);


        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $pack = $forfaitRepository->findOneBy(['nature' => Forfait::NATURE_ENCODAGE]);

        $this->client->request(
            'POST',
            '/api/orders/' . $order->getUuid() . '/swap',
            [],
            [],
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'package_uuid' => $pack->getUuid()
            ))
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }
}
