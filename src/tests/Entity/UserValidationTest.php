<?php

namespace App\Tests\Entity;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserValidationTest extends KernelTestCase
{
    public  $userPasswordEncoder;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
    }


    public function registerValideUser()
    {
        $user = new User();
        $user
            ->setEmail("valid@user.com")
            ->setRoles(["ROLE_DEV"])
            ->setPassword('Azerty-66')
            
            ->setFirstname('tototo')
            ->setLastname("maness")
           
            ->setPhone(611111111)
          
            ->setIsActive(1)
            ->setIsArchive(0)
            ->setUuid('')
            ->setUpdatedAt(new \DateTimeImmutable('now'))
            ->setCreatedAt(new \DateTimeImmutable('now'));
        return $user;
    }

    public function testValidateUser()
    {
        $user = $this->registerValideUser();
        self::bootKernel();
        $error = self::$container->get('validator')->validate($user);
        $this->assertCount(0, $error);
    }
}
