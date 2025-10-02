<?php

namespace App\Services\Users;

use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Forfait;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Services\Forfait\ForfaitManager;
use App\Services\JsonResponseMessage;
use App\Repository\UserRepository;
use App\Services\AbstactValidator;
use App\Services\MailerService;
use App\Services\Order\OrderPackage;
use App\Services\Permission\Account\AccountRoleRightService;
use App\Services\Permission\Account\UserAccountRolesService;
use App\Services\UserValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DateInterval;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class UserRegisterValidator extends AbstactValidator
{
    public $validator;
    public $user;
    public $request;
    private $account;

    protected $passwordEncoder;
    protected $userRepository;
    protected $mailerService;
    public $JWTTokenManager;
    private $forfaitManager;
    private $orderPackage;
    private $serializer;
    private $accountRepository;
    private $UserAccountRolesService;
    private $AccountRoleRightService;

    public function __construct(
        ValidatorInterface           $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        UserRepository               $userRepository,
        MailerService                $mailerService,
        JWTTokenManagerInterface     $JWTTokenManager,
        ForfaitManager               $forfaitManager,
        OrderPackage                 $orderPackage,
        RequestStack                 $requestStack,
        SerializerInterface          $serializer,
        AccountRepository            $accountRepository,
        UserAccountRolesService      $UserAccountRolesService,
        AccountRoleRightService      $AccountRoleRightService
    ) {
        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->mailerService = $mailerService;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->forfaitManager = $forfaitManager;
        $this->orderPackage = $orderPackage;
        $this->serializer = $serializer;
        $this->accountRepository = $accountRepository;
        $this->UserAccountRolesService = $UserAccountRolesService;
        $this->AccountRoleRightService = $AccountRoleRightService;
    }

    public function init($currentUser)
    {

        $fields = json_decode($this->request->getContent(), true);

        $user = new User();
        $hasUser = $this->userRepository->findOneBy(['email' => isset($fields["email"]) != null ? $fields["email"] : '']);
        $this->user = $hasUser;

        if (!$hasUser) {

            $user->setUuid("");
            isset($fields['email']) != null ? $user->setEmail($fields['email']) : null;
            isset($fields['password']) != null ? $user->setPassword($fields['password']) : null;
            $user->setRoles(["ROLE_USER"]);
            $user->setIsActive(0);
            $user->setIsArchive(0);
            $user->setTheme('LIGHT');
            $user->setLang('FR');
            $user->setUpdatedAt(new \DateTimeImmutable('now'));
            $user->setCreatedAt(new \DateTimeImmutable('now'));
            $user->setIsConditionAgreed(false);
            $user->setIsDelete(false);
            $err = $this->validator->validate($user, null, ['registration']);

            if (count($err) > 0) {

                return $this->err($err);
            }
            $this->user = $user;
        }

        $account = $this->createAccount();

        if ($account instanceof JsonResponseMessage) {

            return $account;
        }
        if (!$this->user->getIsActive()) {
            $data['subject'] = 'Création de votre compte GreenEncoder';
            $this->mailerService->sendMail($this->user, MailerService::MAIL_REGISTER, $data);
        }

        return (new JsonResponseMessage())->setCode(Response::HTTP_CREATED)->setError(['The user has been successfully registered!']);
    }


    public function createAccount()
    {
        $account = $this->createNewAccount();

        if ($account instanceof JsonResponseMessage) {
            return $account;
        }
        $registeredUser = $this->userRepository->register($this->user);

        $this->user = $registeredUser;
        $this->account->setUuid('');
        $this->account->setIsActive(true);
        $this->account->setMaxInvitations($account->getIsMultiAccount() ? 3 : 1);
        $this->account->setDisplayName($registeredUser);
        $this->accountRepository->add($this->account);
        $this->account = $this->account;

        $userAccountRole = $this->UserAccountRolesService->AdminRole($this->account, $this->user);

        $this->AccountRoleRightService->prepartAccountights($userAccountRole->getAccount());
    }

    private function createNewAccount()
    {
        $account = new Account();
        $data = $this->serializer->deserialize(
            $this->request->getContent(),
            Account::class,
            'json',
            [
                AbstractObjectNormalizer::OBJECT_TO_POPULATE => $account,
                "groups" => 'account:registration'
            ],
        );

        $err = $this->validator->validate($data, null, 'account:registration');

        if ($err->count() > 0) {
            return $this->err($err);
        }
        $this->account = $account;
        return $account;
    }
}
