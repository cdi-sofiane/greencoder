<?php

namespace App\Tests\ControllerTest;

use App\Entity\Video;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class VideoControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;

    public function setUp(): void
    {
        copy(__DIR__ . '/../Assets/upload/video avec un slug pour tester.mp4', __DIR__ . '/../Assets/video avec un slug pour tester.mp4');
    }


    //list video
    public function testList_RoleVIDMIZER_NoUuid_FindAllVideo()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('GET', '/api/videos');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testList_RoleVIDMIZER_Uuid_FindTargetVideo()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('GET', '/api/videos', ["user_uuid" => 111]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testList_RoleUser_Not_Good_Uuid_FindTargetVideo()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('GET', '/api/videos', ["user_uuid" => 111]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testList_RoleUser_Good_Uuid_FindTargetVideo()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $this->client->request('GET', '/api/videos', ["user_uuid" => $user->getUuid()]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // find one video

    public function testFindOne_RoleUser_FindTargetVideo_Good_Video_Uuid()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $videoRepository = self::$container->get(VideoRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $video = $videoRepository->findOneBy(['user' => $user]);
        $this->client->request('GET', '/api/videos/' . $video->getUuid());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testFindOne_RoleUser_FindTargetVideo_Bad_Video_Uuid()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $videoRepository = self::$container->get(VideoRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a3.com']);
        $video = $videoRepository->findOneBy(['user' => $user]);

        $this->client->request('GET', '/api/videos/' . $video->getUuid());
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testFindOne_RoleVidMizer_FindTargetVideo_Bad_Video_Uuid()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        $videoRepository = self::$container->get(VideoRepository::class);
        $user = $userRepository->findOneBy(['email' => 'a@a3.com']);
        $video = $videoRepository->findOneBy(['user' => $user]);
        $this->client->request('GET', '/api/videos/' . $video->getUuid());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    use traitEstimateVideo;


    use traitEncodeVideo;
}
