<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Encode;
use App\Entity\Forfait;
use App\Entity\Right;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Entity\Video;
use App\Repository\AccountRepository;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\RoleRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Services\ApiKeyService;
use App\Services\AuthorizationService;
use App\Services\Order\OrderPackage;
use App\Services\Permission\Account\AccountRoleRightService;
use App\Services\Permission\Account\UserAccountRolesService;
use App\Utils\RoleBuilder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    protected $passwordEncoder;
    private $fileVideoData = __FILE__ . "./.././video.csv";
    private $fileUserData = __FILE__ . "./.././user.csv";
    private $fileEncodeData = __FILE__ . "./.././encode.csv";
    private $filePackageData = __FILE__ . "./.././package.csv";
    private $_om;
    private $userRepository;
    private $forfaitRepository;
    private $orderPackage;
    /**
     * @var ApiKeyService
     */
    private $apiKeyService;
    private $accountRepository;
    private $roleRepository;
    private $accountRoleRightService;
    private $userAccountRolesService;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UserRepository               $userRepository,
        ForfaitRepository            $forfaitRepository,
        OrderPackage                 $orderPackage,
        ApiKeyService                $apiKeyService,
        AccountRepository            $accountRepository,
        AccountRoleRightService      $accountRoleRightService,
        UserAccountRolesService      $userAccountRolesService,
        RoleRepository               $roleRepository
    ) {
        $this->passwordEncoder = $passwordEncoder;

        $this->userRepository = $userRepository;
        $this->forfaitRepository = $forfaitRepository;
        $this->orderPackage = $orderPackage;
        $this->apiKeyService = $apiKeyService;
        $this->accountRepository = $accountRepository;
        $this->roleRepository = $roleRepository;
        $this->accountRoleRightService = $accountRoleRightService;
        $this->userAccountRolesService = $userAccountRolesService;
    }

    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->_om = $manager;
        $this->setRoles();
        $this->setRights();
        $this->setUser();
        $this->setVideo();
        $this->setPackage();
        $this->setOrder();
    }

    private function setRoles()
    {
        $roles = [
            'admin' => ["uuid" => "", 'code' => "admin", "name" => "admin"],
            'editor' => ["uuid" => "", 'code' => "editor", "name" => "editeur"],
            'reader' => ["uuid" => "", 'code' => "reader", "name" => "lecteur"],
        ];
        $i = 0;
        foreach ($roles as $role) {
            $newRole = new Role();
            $newRole->setCode($role['code'])->setName($role['name'])->setUuid('');
            $this->_om->persist($newRole);
            $this->_om->flush();
        }
    }
    private function setRights()
    {
        $defaultRights = [
            "dashboard" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'dashboard',
                'code' => 'dashboard',
            ],
            "profile" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'profile',
                'code' => 'profile',
            ],
            "video_library" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'librarie de videos',
                'code' => 'video_library',
            ],
            "video_detail" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'detail video',
                'code' => 'video_detail',
            ],
            "video_stream" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'lire une video',
                'code' => 'video_stream',
            ],
            "video_download" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'telecharger video',
                'code' => 'video_download',
            ],
            "video_delete" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'supprimer video',
                'code' => 'video_delete',
            ],
            "video_encode" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'encoder video',
                'code' => 'video_encode',
            ],
            "video_recode" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 're-encoder video',
                'code' => 'video_recode',
            ],
            "account_invite" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'inviter une personne',
                'code' => 'account_invite',
            ],
            "report_encode" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'rapport d\'encodage',
                'code' => 'report_encode',
            ],
            "report_config" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'config rapport encodage',
                'code' => 'report_config',
            ],
            "account_invoice" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'facturation',
                'code' => 'account_invoice',
            ],
            "account_payment" => [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'paiement',
                'code' => 'account_payment',
            ],
        ];
        foreach ($defaultRights as  $defaultRight) {
            $right = new Right();
            $right->setCode($defaultRight['code'])->setName($defaultRight['name'])->setUuid($defaultRight['uuid']);
            $this->_om->persist($right);
        }
        $this->_om->flush();
        /**
         * @var \App\Entity\Account $account
         */
    }

    private function accountRight($account)
    {
        // $accounts = $this->accountRepository->findAll();
        // $roles = $this->roleRepository->findAll();
        // foreach ($accounts as $account) {
        //     foreach ($roles as $role) {
        $this->accountRoleRightService->prepartAccountights($account);
        //     }
        // }
    }
    private function setUser()
    {
        $dataVideo = fopen($this->fileUserData, 'r');
        $i = 0;
        while (($line = fgetcsv($dataVideo)) !== false) {

            $user[$i] = new User();
            $user[$i]->setUuid('');
            $user[$i]->setEmail($line[2]);
            $user[$i]->setRoles([$line[3]]);
            $user[$i]->setPassword($this->passwordEncoder->encodePassword($user[$i], $line[4]));
            $user[$i]->setFirstName($line[6]);
            $user[$i]->setLastName($line[7]);
            $user[$i]->setPhone($line[9]);
            $user[$i]->setIsActive($line[18]);
            $user[$i]->setIsArchive(false);
            $user[$i]->setIsDelete(false);
            $user[$i]->setIsConditionAgreed($line[19]);
            $user[$i]->setUpdatedAt(new \DateTimeImmutable('now'));
            $user[$i]->setCreatedAt(new \DateTimeImmutable('now'));

            $this->_om->persist($user[$i]);
            $this->_om->flush();
            $this->addOrCreateAccount($user[$i], $line[5]);
        }
        $this->_om->flush();
        fclose($dataVideo);
    }

    public function setVideo()
    {
        // $user_a4 = $this->_om->getRepository(User::class)->findOneBy(['email' => 'a@a4.com']);
        // $user_a3 = $this->_om->getRepository(User::class)->findOneBy(['email' => 'a@a3.com']);
        // $dataVideo = fopen($this->fileVideoData, 'r');

        // $j = 0;
        // while (($line = fgetcsv($dataVideo)) !== false) {
        //     $video[$j] = new Video();
        //     $video[$j]->setUser($j < 7 ? $user_a4 : $user_a3);
        //     $video[$j]->setUuid('');
        //     $video[$j]->setName((string)$line[3]);
        //     $video[$j]->setDuration((int)$line[4]);
        //     $video[$j]->setIsMultiEncoded($line[5]);
        //     $video[$j]->setIsStored($line[6]);
        //     $video[$j]->setQualityNeed($line[7]);
        //     $video[$j]->setSize((int)$line[8]);
        //     $video[$j]->setVideoQuality($line[9]);
        //     $video[$j]->setCreatedAt(new \DateTimeImmutable($line[10]));
        //     $video[$j]->setUpdatedAt(new \DateTimeImmutable($line[11]));
        //     $video[$j]->setLink($line[12]);
        //     $video[$j]->setDownloadLink($line[13]);
        //     $video[$j]->setJobId($line[14]);
        //     $video[$j]->setMediaType($line[15]);
        //     $video[$j]->setGainOptimisation(0);
        //     $video[$j]->setIsUploadComplete(true);
        //     $video[$j]->setAccount($j < 7 ? $user_a4->getAccount() : $user_a3->getAccount());
        //     $video[$j]->setEncodingState('ENCODED');
        //     $video[$j]->setIsArchived($line[16]);
        //     $video[$j]->setIsDeleted($line[17]);
        //     $video[$j]->setThumbnail($line[18]);
        //     $video[$j]->setThumbnailHd($line[19]);
        //     $video[$j]->setExtension($line[20]);
        //     $video[$j]->setProgress(100);
        //     $this->_om->persist($video[$j]);
        //     $this->_om->flush();
        //     $this->setEncode($video[$j]);

        //     $j++;
        // }
        // fclose($dataVideo);
    }

    public function setEncode(Video $video)
    {
        $count = 4;
        for ($i = 1; $i <= $count; $i++) {
            $encode[$i] = new Encode();
            $encode[$i]
                ->setUuid('')
                ->setExtension('mp4')
                ->setExternalId($encode[$i]->getUuid())
                ->setQuality('1234x1234')
                ->setSize($video->getSize() * 1 / $i)
                ->setName($video->getName() . '-' . $i)
                ->setCreatedAt(new \DateTimeImmutable($video->getCreatedAt()->format('Y-m-d')))
                ->setUpdatedAt(new \DateTimeImmutable($video->getCreatedAt()->format('Y-m-d')))
                ->setLink($video->getLink())
                ->setIsDeleted(false)
                ->setDownloadLink(null)
                ->setStreamLink(null)
                ->setSlugName($video->getSlugName() . '-' . $i);
            $this->_om->persist($encode[$i]);
            $this->_om->flush();
            $video->addEncode($encode[$i]);
        }
    }

    private function setPackage()
    {
        $dataPackage = fopen($this->filePackageData, 'r');
        $adminUser = $this->_om->getRepository('App\Entity\User')->findOneBy(['email' => 'a@a1.com']);

        $i = 1;
        while (($line = fgetcsv($dataPackage)) !== false) {
            $package[$i] = new Forfait();
            $package[$i]->setUuid('');
            $package[$i]->setName($line[2]);
            $package[$i]->setPrice($line[3]);
            $package[$i]->setDuration($line[5]);
            $package[$i]->setNature($line[6]);
            $package[$i]->setUpdatedAt(new \DateTimeImmutable('now'));
            $package[$i]->setCreatedAt(new \DateTimeImmutable('now'));
            $package[$i]->setCreatedBy($adminUser);
            $package[$i]->setUpdatedBy($adminUser);
            $package[$i]->setCode($line[7]);
            $package[$i]->setSizeStorage((float)$line[12]);
            $package[$i]->setType($line[13]);
            $package[$i]->setIsActive($line[14]);
            $package[$i]->setIsDelete(0);
            $package[$i]->setIsEntreprise($line[15]);
            $package[$i]->setIsAutomatic($line[16]);
            $this->_om->persist($package[$i]);
            $this->_om->flush();
            $i++;
        }
        fclose($dataPackage);
    }
    public function addOrCreateAccount($user, $line)
    {
        $accountRepository = $this->_om->getRepository(Account::class);
        $account = new Account();
        // if ($user->getRoles()[0] != AuthorizationService::AS_USER) {
        $name = $account->getUsages()  == Account::USAGE_INDIVIDUEL ? $user->getEmail() : $account->getCompany();
        $account->setUuid('');
        $account->setEmail($user->getEmail());
        $account->setName($name);
        $account->setCompany($user->getEmail());
        $account->setIsMultiAccount(true);
        $account->setUsages(Account::USAGE_PRO);
        $account->setIsActive(true);
        $account->setMaxInvitations($account->getIsMultiAccount() ? 3 : 1);
        $account->setApiKey($this->apiKeyService->encrypteApiKey($line));
        $userAccountRole = $this->userAccountRolesService->AdminRole($account, $user);
        $this->accountRoleRightService->prepartAccountights($userAccountRole->getAccount());
        $this->_om->persist($account);
        $this->_om->flush();

        // if ($user->getRoles()[0] == AuthorizationService::AS_USER) {
        // $accountUser4 = $accountRepository->findOneBy(['email' => 'a@a4.com']);
        // dd($accountUser4);
        // $userAccountRole = $this->userAccountRolesService->EditorRole($accountUser4, $user);
        // }
        // $this->_om->persist($account);
    }
    public function setOrder()
    {

        $user4 = $this->userRepository->findOneBy(['email' => 'a@a4.com']);
        $listPacks = $this->forfaitRepository->findAll();
        $i = 0;
        foreach ($listPacks as $pack) {
            if ($pack->getType() != Forfait::TYPE_GRATUIT && $i < 3) {
                // $this->orderPackage->orderPack($pack, $user4->getAccount());
            }
            $i++;
        }
    }
}
