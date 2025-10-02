<?php

namespace App\Tests\ControllerTest;

use App\Entity\Forfait;
use App\Repository\ForfaitRepository;
use App\Tests\UserAuthorizationTokenTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PackageControllerTest extends WebTestCase
{
    use UserAuthorizationTokenTrait;

    public $client;

    /**
     * @dataProvider  createPackageWithGoodFields
     */

    public function testCreateFortaitRoleVidmizerWithGoodParams($createPackageWithGoodFields)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('POST', '/api/packages', $createPackageWithGoodFields);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  createPackageWithBadFields
     */

    public function testCreateFortaitRoleVidmizerWithBadParams($createPackageWithBadFields)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('POST', '/api/packages', $createPackageWithBadFields);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  filtersGoodData
     */
    public function testListForfaitRoleVidmizerWithFiltersGoodData($filtersGoodData)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('GET', '/api/packages', $filtersGoodData);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  patchForfaitGoodData
     */
    public function testUpdateForfaitRoleVidmizerWithGoodData($patchForfaitGoodData)
    {
        $this->client = $this->createAuthenticatedClient("a@a2.com",'Az1-tyze');
        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $forfait = $forfaitRepository->findAll();

        $this->client->request('PATCH', '/api/packages/' . $forfait[0]->getUuid(), [], [], [], json_encode($patchForfaitGoodData));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @dataProvider  patchForfaitBadData
     */
    public function testUpdateForfaitRoleVidmizerWithBadData($patchForfaitBadData)
    {
        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $forfaitRepository = self::$container->get(ForfaitRepository::class);
        $forfait = $forfaitRepository->findAll();
        $this->client->request('PATCH', '/api/packages/' . $forfait[0]->getUuid(), [], [], [], json_encode($patchForfaitBadData));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @dataProvider  filtersBadData
     */
    public function testListForfaitRoleVidmizerWithFiltersBadData($filtersBadData)
    {

        $this->client = $this->createAuthenticatedClient("a@a1.com");
        $this->client->request('GET', '/api/packages', $filtersBadData);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }


    public function createPackageWithGoodFields()
    {
        yield [["name" => "Forfait 1", "nature" => "encodage", "price" => "800.56", "duration" => '300', "type" => "Abonnement", "isActive" => "true", "isEntreprise" => "true", "isAutomatic" => "false"]];
        yield [["name" => "Forfait 2", "nature" => "encodage", "price" => "500.56", "duration" => '300', "type" => "Credit", "isActive" => "true", "isEntreprise" => "true", "isAutomatic" => "false"]];
        yield [["name" => "Forfait 3", "nature" => "stockage", "price" => "500.56", "sizeStorage" => "0.1", "type" => "Abonnement", "isActive" => "true", "isEntreprise" => "true", "isAutomatic" => "false"]];
        yield [["name" => "Forfait 4", "nature" => "stockage", "price" => "500.56", "sizeStorage" => "0.4", "type" => "Abonnement", "isActive" => "true", "isEntreprise" => "false", "isAutomatic" => "false"]];
        yield [["name" => "Forfait 5", "nature" => "hybride", "duration" => "300", "sizeStorage" => "0.4", "type" => "Gratuit", "isActive" => "false", "isAutomatic" => "true"]];
        yield [["name" => "Forfait 6", "nature" => "hybride", "duration" => "300", "sizeStorage" => "0.4", "type" => "Gratuit", "isActive" => "false", "isAutomatic" => "false"]];
    }

    public function createPackageWithBadFields()
    {
        yield [["name" => "gratuit encodage", "nature" => "encodage", "price" => "800,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "Gratuit"]];
        yield [["name" => "Forfait 3", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 10", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit"]];
        yield [["name" => "Forfait 9", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0,1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "che", "isActive" => "true"]];
        yield [["name" => "Forfait 8", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "e", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 7", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "e", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 6", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "e", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 5", "nature" => "stockage", "price" => "500,56", "bandWidth" => "0,9", "duration" => "z", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 4", "nature" => "stockage", "price" => "500,56", "bandWidth" => "e", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 3", "nature" => "test", "price" => "500,56", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 2", "nature" => "encodage", "price" => "te", "bandWidth" => "0,9", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forfait 11", "nature" => "hybride", "price" => "te", "duration" => "300", "sizeStorage" => "0.1", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];

    }

    public function filtersGoodData()
    {
        yield [["name" => "Forfait 1", "nature" => "encodage", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true", "startAt" => "2021-11-15"]];
        yield [["name" => "F", "nature" => "stockage", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "OneShot", "isActive" => "true"]];
        yield [["name" => "Forf", "nature" => "stockage", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "Credit", "isActive" => "true", "startAt" => "2021-11-15", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "isEntreprise" => "false", "isAutomatic" => "false", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Fait 2", "nature" => "encodage", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Abonnement", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "nature" => "stockage", "isEntreprise" => "false", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfdddait 2", "nature" => "stockage", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "nature" => "encodage", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2"]];

    }

    public function filtersBadData()
    {
        yield [["name" => "Forfait 2", "startAt" => "021/11-15"]];
        yield [["name" => "Forfait 1", "nature" => "encode", "isEntreprise" => 9, "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true",]];
        yield [["name" => "F", "nature" => "a", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "Gratuit", "isActive" => "true"]];
        yield [["name" => "Forf", "isEntreprise" => "toto", "isAutomatic" => "true", "type" => "test", "isActive" => "true", "startAt" => "2021-11-15", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "nature" => "storage", "isAutomatic" => "true", "type" => "d", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "nature" => "storage", "isEntreprise" => "false", "isAutomatic" => "d", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Fait 2", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "d", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "nature" => "storage", "isEntreprise" => "false", "type" => "Gratuit", "isActive" => "false", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfdddait 2", "nature" => "storage", "isEntreprise" => "true", "isAutomatic" => "true", "type" => "Gratuit", "endAt" => "2021-11-15"]];
        yield [["name" => "Forfait 2", "isEntreprise" => "false", "isAutomatic" => "true", "type" => "test", "isActive" => "false", "endAt" => "2021-11-15"]];
    }

    public function patchForfaitGoodData()
    {
        yield [["name" => "Forfait patch 10", "nature" => "stockage", "price" => "600.56", "duration" => 0, "sizeStorage" => "4.1", "isEntreprise" => "true", "type" => "Abonnement", "isActive" => "false"]];
        yield [["name" => "Forfait patch 9", "nature" => "encodage", "price" => "500.56", "duration" => "300", "sizeStorage" => 0, "isEntreprise" => "false", "type" => "Credit"]];
        yield [["name" => "Forfait patch 8", "nature" => "encodage", "price" => "500.56", "duration" => "300", "sizeStorage" => 0, "isEntreprise" => "false"]];
        yield [["name" => "Forfait patch 7", "nature" => "encodage", "price" => "500.56", "duration" => "300", "sizeStorage" => 0, "isEntreprise" => "false"]];
        yield [["name" => "Forfait patch 6", "nature" => "encodage", "price" => "500.56", "duration" => "300", "sizeStorage" => 0]];
        yield [["name" => "Forfait patch 5", "nature" => "encodage", "price" => "500.56", "duration" => "900"]];
        yield [["name" => "Forfait patch 4", "nature" => "encodage", "price" => "500.56"]];
        yield [["name" => "Forfait patch 3", "nature" => "encodage"]];
        yield [["name" => "Forfait patch 2"]];
        yield [["name" => "Forfait patch 1"]];

    }

    public function patchForfaitBadData()
    {
        yield [["name" => "Forfait patch 10", "nature" => "stockage", "price" => "600.56", "bandWidth" => "4.5", "duration" => "600", "sizeStorage" => "4.1", "isEntreprise" => "false", "type" => "OneShot", "isActive" => "gh"]];
        yield [["name" => "Forfait patch 9", "nature" => "encodage", "price" => "500.56", "bandWidth" => "0.5", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true", "type" => "pool"]];
        yield [["name" => "Forfait patch 8", "nature" => "hybride", "price" => "500.56", "bandWidth" => "0.5", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "true"]];
        yield [["name" => "Forfait patch 7", "nature" => "encodage", "price" => "500.56", "bandWidth" => "0.5", "duration" => "300", "sizeStorage" => "0.1", "isEntreprise" => "hero"]];
        yield [["name" => "Forfait patch 6", "nature" => "encodage", "price" => "500.56", "bandWidth" => "0.5", "duration" => "test", "sizeStorage" => "1"]];
        yield [["name" => "Forfait patch 5", "nature" => "encodage", "price" => "500.56", "bandWidth" => "0.5", "duration" => ""]];
        yield [["name" => "Forfait patch 4", "nature" => "encodage", "price" => "500.;56", "bandWidth" => "812.501"]];
        yield [["name" => "Forfait patch 3", "nature" => "encodage", "price" => "az"]];
        yield [["name" => "Forfait patch 2", "nature" => ""]];


    }
}
