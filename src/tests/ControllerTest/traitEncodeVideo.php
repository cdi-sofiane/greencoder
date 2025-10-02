<?php

namespace App\Tests\ControllerTest;

use App\Entity\Account;
use App\Entity\UserAccountRole;
use App\Entity\Video;
use App\Repository\AccountRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

trait traitEncodeVideo
{
    //video encode

    //vidmizer
    /**
     * @dataProvider  videoGoodParam
     */
    public function testEncodeFileRoleVidMizerGoodMimeTypeGoodParam($videoGoodParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a1.com"]);


        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoGoodParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoGoodParam, ['file' => $uploadedFile], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  videoBadParam
     */
    public function testEncodeFileRoleVidMizerGoodMimeTypeBadParam($videoBadParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a1.com"]);


        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoBadParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoBadParam, ['file' => $uploadedFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  videoGoodParam
     */
    public function testEncodeFileRoleVidMizerNoFileGoodParam($videoGoodParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "xa@a1.com"]);


        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoGoodParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoGoodParam, [], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  videoGoodParam
     */
    public function testEncodeFileRoleVidMizerNodFileBadParam($videoGoodParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a1.com"]);


        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoGoodParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/document.png', 'document.png');
        $this->client->request('POST', '/api/videos/encode', $videoGoodParam, ['file' => $uploadedFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    //user

    /**
     * @dataProvider  videoGoodParam
     */
    public function testEncodeFileRoleUserGoodMimeTypeGoodParam($videoGoodParam)
    {
        $this->client = $this->createAuthenticatedClient("xsekio@gmail.com");
        $em = $this->$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "xsekio@gmail.com"]);
        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoGoodParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoGoodParam, ['file' => $uploadedFile], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  videoBadParam
     */
    public function testEncodeFileRoleUserGoodMimeTypeBadParam($videoBadParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a4.com"]);

        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoBadParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoBadParam, ['file' => $uploadedFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  videoGoodParam
     */
    public function testEncodeFileRoleUserNoFileGoodParam($videoGoodParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");

        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a4.com"]);
        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoGoodParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $this->client->request('POST', '/api/videos/encode', $videoGoodParam, [], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  videoBadParam
     */
    public function testEncodeFileRoleUserNodFileBadParam($videoBadParam)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $em = self::$container->get('doctrine')->getManager();
        $accountRepo = $em->getRepository(Account::class);

        $account = $accountRepo->findOneBy(["email" => "a@a4.com"]);

        $videoGoodParam = array_merge(['account_uuid' => $account->getUuid()], $videoBadParam);
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/document.png', 'document.png');
        $this->client->request('POST', '/api/videos/encode', $videoBadParam, ['file' => $uploadedFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }


    public function testStoreUserUnstoredVideoRoleVidmizer()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'xsekio@gmail.com']);
        $videoRepository = self::$container->get(VideoRepository::class);
        $video = $videoRepository->findOneBy(['account' => $user->getAccount(), 'isStored' => false, 'isDeleted' => false]);

        $this->client->request('PUT', '/api/videos/' . $video->getUuid() . '/store');



        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    public function testStoreVidmizerUnstoredVideoRoleVidmizer()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $videoRepository = self::$container->get(VideoRepository::class);
        $video = $videoRepository->findOneBy(['account' => $user->getAccount(), 'isStored' => false, 'isDeleted' => false]);

        $this->client->request('PUT', '/api/videos/' . $video->getUuid() . '/store');



        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testStoreMyAccountUnstoredVideoRolePilote()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'a@a4.com']);
        $creditBits = $user->getAccount()->getCreditStorage();
        $creditEncodage = $user->getAccount()->getCreditEncode();

        $videoRepository = self::$container->get(VideoRepository::class);
        $video = $videoRepository->findOneBy(['account' => $user->getAccount(), 'isStored' => false, 'isDeleted' => false]);

        $this->client->request('PUT', '/api/videos/' . $video->getUuid() . '/store');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testStoreNotMyAccountUnstoredVideoRoleUser()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'a@a3.com']);
        $videoRepository = self::$container->get(VideoRepository::class);
        $video = $videoRepository->findOneBy(['account' => $user->getAccount(), 'isStored' => false, 'isDeleted' => false]);

        $this->client->request('PUT', '/api/videos/' . $video->getUuid() . '/store');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
    public function testRemoveVideo()
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $videoRepository = self::$container->get(VideoRepository::class);
        $videos = $videoRepository->findAll();
        foreach ($videos as $video) {
            if ($video->getDownloadLink() != null) {
                $this->client->request('DELETE', '/api/videos/' . $video->getUuid());
            }
            foreach ($video->getEncodes() as $encode) {
                if ($encode->getDownloadLink() != null) {
                    $this->client->request('DELETE', '/api/videos/' . $encode->getUuid());
                }
            }
        }
        $this->assertTrue(true);
    }


    //case
    public function videoBadParam()
    {
        yield [['qualityNeed' => '', 'isMultiEncoded' => '', 'isStored' => '']];
        yield [['qualityNeed' => '', 'isMultiEncoded' => '', 'isStored' => '']];
        yield [['width' => 'c123123', 'height' => '111', 'isMultiEncoded' => 'truse', 'isStored' => 'false']];
        yield [[]];
        yield [['height' => '1231c23']];
        yield [['qualityNeed' => 'c23x12']];
        yield [['qualityNeed' => 'c12x112', 'isMultiEncoded' => true, 'isStored' => true]];
        yield [['qualityNeed' => '12x11s2', 'isMultiEncoded' => true, 'isStored' => true]];
        yield [['qualityNeed' => '12x112x', 'isMultiEncoded' => true, 'isStored' => true]];
        yield [['qualityNeed' => '12x112', 'isMultiEncoded' => 'trued', 'isStored' => 'tdrue']];
        yield [['qualityNeed' => '12x112', 'isMultiEncoded' => 'true', 'isStored' => 'tdrue']];
        yield [['qualityNeed' => '12x112', 'isMultiEncoded' => '1', 'isStored' => 'tdrue']];
        yield [['qualityNeed' => '12x112', 'isMultiEncoded' => '1', 'isStored' => 'azaze']];
        yield [['qualityNeed' => '12x112', 'isMultiEncoded' => '', 'isStored' => '']];
    }

    public function videoGoodParam()
    {
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'false', 'isStored' => true, 'mediaType' => 'WEBINAR', 'title' => 'testCredit']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'false', 'isStored' => 'false', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'false', 'isStored' => 'true', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'true', 'isStored' => 'false', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'true', 'isStored' => 'true', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'false', 'isStored' => '0', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'false', 'isStored' => '1', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'true', 'isStored' => '0', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'true', 'isStored' => '1', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => true, 'isStored' => true, 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => 'true', 'isStored' => 'true', 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => true, 'isStored' => true, 'mediaType' => 'FIXED_SHOT']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => true, 'isStored' => false, 'mediaType' => 'HIGH_RESOLUTION']];
        yield [['qualityNeed' => '1234x234', 'isMultiEncoded' => "false", 'isStored' => false, 'mediaType' => 'WEBINAR']];
        yield [['qualityNeed' => '', 'isMultiEncoded' => "false", 'isStored' => false, 'mediaType' => 'WEBINAR']];
        yield [['isMultiEncoded' => true, 'isStored' => true, 'mediaType' => 'HIGH_RESOLUTION']];
    }
}
