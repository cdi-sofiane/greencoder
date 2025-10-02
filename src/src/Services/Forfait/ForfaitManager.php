<?php

namespace App\Services\Forfait;

use App\Entity\Forfait;
use App\Entity\User;
use App\Repository\ForfaitRepository;
use App\Repository\UserRepository;
use App\Services\AbstactValidator;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerResponse;
use App\Services\Forfait\PackageFormalizerResponse;
use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function GuzzleHttp\Promise\all;

class ForfaitManager extends AbstactValidator
{
    const CONV_SEC = 60;
    const CONV_BYTES = 1000000000;
    private $requestStack;
    private $forfaitRepository;
    private $validator;
    private $dataFormalizer;
    private $authorisation;
    private $userRepository;

    public function __construct(
        RequestStack              $requestStack,
        ForfaitRepository         $forfaitRepository,
        ValidatorInterface        $validator,
        DataFormalizerResponse $dataFormalizer,
        AuthorizationService      $authorisation,
        UserRepository $userRepository
    ) {
        $this->requestStack = $requestStack->getCurrentRequest();
        $this->forfaitRepository = $forfaitRepository;

        $this->validator = $validator;
        $this->dataFormalizer = $dataFormalizer;
        $this->authorisation = $authorisation;
        $this->userRepository = $userRepository;
    }

    public function newPackage(User $user = null)
    {

        $data = $this->requestStack->request->all() != null ? $this->requestStack->request->all() : json_decode($this->requestStack->getContent(), true);
        $package = new Forfait();
        $package->setUuid('')
            ->setSizeStorage(isset($data['sizeStorage']) && $data['sizeStorage'] != "" ? $data['sizeStorage'] : null)
            ->setName(isset($data['name']) != null ? $data['name'] : null)
            ->setNature(isset($data['nature']) != null ? $data['nature'] : null)
            ->setDuration(isset($data['duration']) && $data['duration'] != "" ? $data['duration'] : null)
            ->setPrice(isset($data['price']) != null ? $data['price'] : null)
            ->setType(isset($data['type']) != null ? $data['type'] : null)
            ->setIsEntreprise(isset($data['isEntreprise']) != null ? $data['isEntreprise'] : '')
            ->setIsAutomatic(isset($data['isAutomatic']) != null ? $data['isAutomatic'] : '')
            ->setIsActive(isset($data['isActive']) != null ? $data['isActive'] : '')
            ->setUpdatedAt(new \DateTimeImmutable('now'))
            ->setCreatedAt(new \DateTimeImmutable('now'))
            ->setIsDelete(false)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);
        $err = $this->validator->validate($package, null, ['create']);
        if ($err->count() > 0) {
            return $this->err($err);
        } else {

            $package->setDuration($package->getDuration() * self::CONV_SEC);
            $package->setSizeStorage($package->getSizeStorage() * self::CONV_BYTES);
            $this->forfaitRepository->save($package);
            return (new JsonResponseMessage())->setCode(200)->setContent(['success!']);
        }
    }

    public function editPackage($user = null)
    {
        $package = $this->forfaitRepository->findOneBy(['uuid' => $this->requestStack->attributes->get('package_uuid')]);

        if ($package == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setContent(['not fount entity!']);
        }

        $body = json_decode($this->requestStack->getContent(), true);

        isset($body["sizeStorage"]) != null ? $package->setSizeStorage(is_numeric($body["sizeStorage"]) ? $body["sizeStorage"] * self::CONV_BYTES : $body["sizeStorage"]) : null;
        isset($body["name"]) != null ? $package->setName($body["name"]) : null;
        isset($body["nature"]) != null ? $package->setNature($body["nature"]) : null;
        isset($body["duration"]) != null ? $package->setDuration(is_numeric($body["duration"]) ? $body["duration"] * self::CONV_SEC : $body["duration"]) : null;
        isset($body["price"]) != null ? $package->setPrice($body["price"]) : null;
        isset($body["type"]) != null ? $package->setType($body["type"]) : null;
        isset($body["isEntreprise"]) != null ? $package->setIsEntreprise($body["isEntreprise"]) : null;
        isset($body["isAutomatic"]) != null ? $package->setIsAutomatic(isset($body["isAutomatic"]) != null ? $body["isAutomatic"] : '') : null;
        isset($body["isActive"]) != null ? $package->setIsActive($body["isActive"]) : null;
        $package->setUpdatedBy($user);
        $err = $this->validator->validate($package, null, ['update']);
        if ($err->count() > 0) {
            return $this->err($err);
        } else {
            $this->forfaitRepository->save($package);
            return (new JsonResponseMessage())->setCode(200)->setContent(['success!']);
        }
    }

    public function findOne($user = null, $filter = null)
    {

        return $this->forfaitRepository->findOneBy($filter);
    }

    public function findAll($user = null)
    {

        $package = new Forfait();
        $filters["page"] = !empty($this->requestStack->query->get("page")) != null && $this->requestStack->query->get("page") != 0 ? $this->requestStack->query->getInt("page") : 1;
        $filters["order"] = !empty($this->requestStack->query->get("order")) != null ? $this->requestStack->query->get("order") : 'ASC';
        $filters["limit"] = !empty($this->requestStack->query->get("limit")) != null ? $this->requestStack->query->getInt("limit") : 12;
        $filters["search"] = !empty($this->requestStack->query->get("search")) != null ? $this->requestStack->query->get("search") : null;
        $filters['package_uuid'] = !empty($this->requestStack->query->get("package_uuid")) != null ? $this->requestStack->query->get("package_uuid") : null;
        $filters['createdBy'] = !empty($this->requestStack->query->get("createdBy")) != null ? $user->getUuid() : null;
        $filters["startAt"] = !empty($this->requestStack->query->get("startAt")) != null ? $this->requestStack->query->get("startAt") : null;
        $filters["endAt"] = !empty($this->requestStack->query->get("endAt")) != null ? $this->requestStack->query->get("endAt") : null;
        $filters['isActive'] = !empty($this->requestStack->query->get("isActive")) != null ? $this->requestStack->query->get("isActive") : null;
        $filters['isDelete'] = !empty($this->requestStack->query->get("isDelete")) != null ? $this->requestStack->query->get("isDelete") : null;
        $filters['isEntreprise'] = !empty($this->requestStack->query->get("isEntreprise")) != null ? $this->requestStack->query->get("isEntreprise") : null;
        $filters['isAutomatic'] = !empty($this->requestStack->query->get("isAutomatic")) != null ? $this->requestStack->query->get("isAutomatic") : null;
        $filters['nature'] = !empty($this->requestStack->query->get("nature")) != null ? $this->requestStack->query->get("nature") : null;
        $filters['type'] = !empty($this->requestStack->query->get("type")) != null ? $this->requestStack->query->get("type") : null;


        $package->setStartAt($filters["startAt"]);
        $package->setEndAt($filters["endAt"]);
        $package->setIsAutomatic($filters["isAutomatic"]);
        $package->setIsEntreprise($filters["isEntreprise"]);
        $package->setIsActive($filters["isActive"]);
        $package->setIsDelete($filters["isDelete"]);
        $package->setNature($filters["nature"]);
        $package->setType($filters["type"]);

        $err = $this->validator->validate($package, null, ['filters']);
        if ($err->count() > 0) {
            return $this->err($err);
        } else {
            $filters["isAutomatic"] = false;
            $filters["isEntreprise"] = false;
            $filters["isActive"] = true;
            $filters["isDelete"] = false;
            $filters["createdBy"] = null;
            $packages = $this->forfaitRepository->findWithFilters(null, $filters, AuthorizationService::AS_USER);
            if ($user != null) {


                if ($user->getRoles()[0] != AuthorizationService::AS_USER) {
                    $filters["isAutomatic"] = $package->getIsAutomatic();
                    $filters["isEntreprise"] = $package->getIsEntreprise();
                    $filters["isActive"] = $package->getIsActive();
                    $filters["isDelete"] = $package->getIsDelete();

                    $targetUser = $this->userRepository->findBy(['uuid' => $filters["createdBy"]]);
                    $packages = $this->forfaitRepository->findWithFilters($targetUser, $filters);
                }
            }
            return $this->dataFormalizer->extract($packages, 'list_all', true, "'package(s) successfuly retrived!", Response::HTTP_OK, $filters);
        }
    }

    public function removePackage($user = null)
    {
        $package = $this->forfaitRepository->findOneBy(['uuid' => $this->requestStack->attributes->get('package_uuid')]);

        if ($package == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setContent(['not fount entity!']);
        }
        $package->setIsDelete(true);

        $packages = $this->forfaitRepository->save($package);

        $err = $this->validator->validate($package, null, ['delete']);

        if ($err->count() > 0) {
            return $this->err($err);
        }

        return $this->dataFormalizer->extract($packages, 'delete', false);
    }

    public function findPackageStorage()
    {
        $filtersStorage = [
            'search' => $_ENV['VIDEO_ENGAGE_STOKAGE'],
            'isActive' => true,
            'isEntreprise' => true,
            'type' => Forfait::TYPE_ABONNEMENT,
            'nature' => Forfait::NATURE_STOCKAGE
        ];

        return $this->forfaitRepository->findWithFilters(null, $filtersStorage);
    }

    public function findPackageEncodage()
    {
        $filtersEncodage = [
            'search' => $_ENV['VIDEO_ENGAGE_ENCODAGE'],
            'isActive' => true,
            'isEntreprise' => true,
            'type' => Forfait::TYPE_ABONNEMENT,
            'nature' => Forfait::NATURE_ENCODAGE
        ];
        return $this->forfaitRepository->findWithFilters(null, $filtersEncodage);
    }
}
