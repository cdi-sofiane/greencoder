<?php

namespace App\Tests\ControllerTest;

use App\Repository\UserRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TagsControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;


    /**
     * @dataProvider  tags
     */
    public function testRoleUserAddTagsUser_AssociateTagsVideos($tags)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('POST', '/api/tags', [], [], [], json_encode($tags));
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  badTags
     */
    public function testRoleUserAddTagsUser_AssociateTagsVideos_BadTags($badTags)
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('POST', '/api/tags', [], [], [], json_encode($badTags));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRoleDevFindTags()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a1.com']);
        $this->client->request('GET', '/api/tags');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleDevFindTagsUserUuid()
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('GET', '/api/tags');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleUserFindTags()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('GET', '/api/tags');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRoleUserFindTagsBadUuid()
    {

        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a1.com']);
        $this->client->request('GET', '/api/tags');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function tags()
    {
        yield [['tags' => ['TF1', 'MP4', 'WEBINAR', 'COVID'], 'videos' => ['0919d20a-fe0e-4a8c-963e-76283b1213ad', '2d9d1861-78f6-43d0-aafd-977a473e21e0']]];
        yield [['tags' => ['TF1', 'TRAINING', 'SERPENTS'], 'videos' => ['c12aac80-ff6a-4355-b837-b80647c76787', '2d9d1861-78f6-43d0-aafd-977a473e21e0']]];
        yield [['tags' => ['TF1', 'TRAINING', 'SERPENTS', 'TOTO'], 'videos' => ['c12aac80-ff6a-4355-b837-b80647c76787', '2d9d1861-78f6-43d0-aafd-977a473e21e0']]];
    }

    public function badTags()
    {
        yield [['tags' => [], 'videos' => ['c12aac80-ff6a-4355-b837-b80647c76787', '2d9d1861-78f6-43d0-aafd-977a473e21e0']]];
        yield [['videos' => ['c12aac80-ff6a-4355-b837-b80647c76787', '2d9d1861-78f6-43d0-aafd-977a473e21e0']]];
        yield [['videos' => []]];
        yield [['tags' => [],]];
        yield [['tags' => [], 'videos' => []]];
        yield [[]];
    }


}