<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{

    public function testOneUser()
    {

        self::bootKernel();
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a1.com']);
        $this->assertEquals('a@a1.com', $user->getEmail());
    }


}
