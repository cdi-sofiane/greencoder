<?php

namespace App\Tests\ControllerTest;

use App\Entity\Encode;
use App\Entity\Forfait;
use App\Entity\Video;
use App\Repository\ForfaitRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Tests\UserAuthorizationTokenTrait;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ReportControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;

    public $client;

    /**
     * @dataProvider  userMailForAdmin
     */
    public function testGetOrCreateReportConfigRoleVidmizer($userMailForAdmin)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail($userMailForAdmin);


        $response = $this->client->request('GET', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid());


        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }



    /**
     * @dataProvider  userMailForUserForbiden
     */
    public function testGetOrCreateReportConfigRoleUserForbiden($userMailForUserForbiden)
    {
        $this->client = $this->createAuthenticatedClient("s.vidmizer@gmail.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail($userMailForUserForbiden);

        $response = $this->client->request('GET', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid());


        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider  userMailForAdmin
     */
    public function testGetOrCreateReportConfigRoleUserAccepted($userMailForAdmin)
    {
        $this->client = $this->createAuthenticatedClient($userMailForAdmin);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail($userMailForAdmin);

        $response = $this->client->request('GET', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid());


        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    /**
     * @dataProvider  configReportValue
     */
    public function testEditReportConfigRoleUserGoodValue($configReportValue)
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

        $response = $this->client->request('PUT', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid(), [], [], [], json_encode($configReportValue));



        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  configReportValue
     */
    public function testEditReportConfigRoleAdminGoodValue($configReportValue)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

        $response = $this->client->request('PUT', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid(), [], [], [], json_encode($configReportValue));




        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  configReportBadValue
     */
    public function testEditReportConfigRoleAdminBadValue($configReportBadValue)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');

        $response = $this->client->request('PUT', '/api/reports-config?account_uuid=' . $myAccount->getAccount()->getUuid(), [], [], [], json_encode($configReportBadValue));


        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateReportRoleVidmizer()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userRepository = self::$container->get(UserRepository::class);
        $videoRepository = self::$container->get(VideoRepository::class);
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');


        $videos = $videoRepository->findBy(['account' => $myAccount->getAccount()]);
        $encodesDataForReport = [];
        /**
         *
         * @var Video $video
         */
        foreach ($videos as $video) {
            /**
             * @var Encode $encode
             */
            $encode = $video->getEncodes()[0];
            $encodesDataForReport['name'] = 'test';
            $encodesDataForReport['videos'][] = [
                "uuid" => $encode->getUuid(),
                "resolution" => $encode->getQuality(),
                "totalCompletion" => rand(1, 100),
                "totalViews" => rand(1, 100000),
                "mobileRepartition" => 45,
                "desktopRepartition" => 55,
                "mobileCarbonWeight" => rand(1, 10000),
                "desktopCarbonWeight" => rand(1, 10000)
            ];
        }

        $response = $this->client->request('POST', '/api/accounts/' . $myAccount->getAccount()->getUuid() . '/reports', [], [], [], json_encode($encodesDataForReport));


        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }


    public function testGetReportRoleVidmizer()
    {
        $this->client = $this->createAuthenticatedClient("a@a4.com");
        $userAccountRoleRepository = self::$container->get(UserAccountRoleRepository::class);
        $myAccount = $userAccountRoleRepository->findAccountByAnyUserEmail('a@a4.com');
        // search: string;
        // sortBy: string; (name, date, nb video, % economie)
        // direction: 'ASC' | 'DESC';
        $query = "?order=ASC&sort=video&isDeleted=false";

        $this->client->request('GET', '/api/accounts/' . $myAccount->getAccount()->getUuid() . '/reports' . $query);


        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function userMailForAdmin()
    {
        yield ['a@a1.com'];
        yield ['a@a4.com'];
    }

    public function userMailForUserForbiden()
    {
        yield ['a@a1.com'];
        yield ['a@a4.com'];
        yield ['a@a3.com'];
    }

    public function configReportValue()
    {
        yield [["totalCompletion" => 100, "totalViews" => 100000, "mobileRepartition" => 80, "desktopRepartition" => 20, "mobileCarbonWeight" => 1500, "desktopCarbonWeight" => 148]];
        yield [["totalCompletion" => 2, "totalViews" => 10, "mobileRepartition" => 80, "desktopRepartition" => 20, "mobileCarbonWeight" => 5090, "desktopCarbonWeight" => 48]];
        yield [["totalCompletion" => 3, "totalViews" => 100, "mobileRepartition" => 50, "desktopRepartition" => 50, "mobileCarbonWeight" => 5090]];
        yield [["totalCompletion" => 4, "totalViews" => 1000, "mobileRepartition" => 45, "desktopRepartition" => 55]];
        yield [["totalCompletion" => 5, "totalViews" => 10000, "mobileRepartition" => 45]];
        yield [["totalCompletion" => 6, "totalViews" => 11000]];
        yield [["totalCompletion" => 7]];
        yield [[]];
    }

    public function configReportBadValue()
    {
        yield [["totalCompletion" => "100", "totalViews" => 100000, "mobileRepartition" => 80, "desktopRepartition" => 20, "mobileCarbonWeight" => 1500, "desktopCarbonWeight" => "z"]];
        yield [["totalCompletion" => 2, "totalViews" => 10, "mobileRepartition" => 80, "desktopRepartition" => 20, "mobileCarbonWeight" => 5090, "desktopCarbonWeight" => "s"]];
        yield [["totalCompletion" => 3, "totalViews" => 100, "mobileRepartition" => 80, "desktopRepartition" => 50, "mobileCarbonWeight" => "s"]];
        yield [["totalCompletion" => 4, "totalViews" => 1000, "mobileRepartition" => 8, "desktopRepartition" => "e"]];
        yield [["totalCompletion" => 5, "totalViews" => -1, "mobileRepartition" => 1]];
        yield [["totalCompletion" => 6, "totalViews" => "d"]];
        yield [["totalCompletion" => "d"]];
    }




    public function videosDataForReport()
    {

        yield [
            "name" => 'test',
            "videos" =>
            [
                [
                    "uuid" => "27c12133-f89f-43e3-9910-4b81a5aeb383",
                    "resolution" => "1024*986",
                    "totalCompletion" => 56,
                    "totalViews" => 100000,
                    "mobileRepartition" => 0,
                    "desktopRepartition" => 100,
                    "mobileCarbonWeight" => 1500,
                    "desktopCarbonWeight" => 148
                ],
                [
                    "uuid" => "060bfb7c-1136-46f3-b87b-4be6dc6b42d8",
                    "resolution" => "1024*986",
                    "totalCompletion" => 98,
                    "totalViews" => 5678,
                    "mobileRepartition" => 100,
                    "desktopRepartition" => 0,
                    "mobileCarbonWeight" => 345,
                    "desktopCarbonWeight" => 123
                ],
                [
                    "uuid" => "8788f3c7-6dc4-4b43-9dea-83a0f3ec9ca1",
                    "resolution" => "1024*986",
                    "totalCompletion" => 34,
                    "totalViews" => 8753,
                    "mobileRepartition" => 90,
                    "desktopRepartition" => 10,
                    "mobileCarbonWeight" => 98,
                    "desktopCarbonWeight" => 45
                ],
                [
                    "uuid" => "263072cd-82a3-404a-96e5-c6165305f685",
                    "resolution" => "1024*986",
                    "totalCompletion" => 100,
                    "totalViews" => 40632,
                    "mobileRepartition" => 70,
                    "desktopRepartition" => 30,
                    "mobileCarbonWeight" => 45,
                    "desktopCarbonWeight" => 666
                ],
                [
                    "uuid" => "efe9c80f-44a9-4f3b-b96e-4e397904a734",
                    "resolution" => "1024*986",
                    "totalCompletion" => 58,
                    "totalViews" => 92235,
                    "mobileRepartition" => 80,
                    "desktopRepartition" => 20,
                    "mobileCarbonWeight" => 299,
                    "desktopCarbonWeight" => 657,
                ],
                [
                    "uuid" => "c61c57dc-297e-430b-8c75-72d9518b2754",
                    "resolution" => "1024*986",
                    "totalCompletion" => 100,
                    "totalViews" => 667788,
                    "mobileRepartition" => 45,
                    "desktopRepartition" => 65,
                    "mobileCarbonWeight" => 6666,
                    "desktopCarbonWeight" => 555
                ]
            ]
        ];
    }
}
