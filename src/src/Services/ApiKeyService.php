<?php

namespace App\Services;

use App\Entity\Account;
use App\Entity\UserAccountRole;
use App\Repository\AccountRepository;
use App\Repository\UserRepository;
use App\Security\Voter\AccountVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class ApiKeyService
{

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    private $publicPath;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    private $authorizationService;
    private $em;
    private $request;
    private $security;
    private $accountVoter;

    public function __construct(
        UserRepository $userRepository,
        ParameterBagInterface $parameterBag,
        AccountRepository $accountRepository,
        AuthorizationService  $authorizationService,
        RequestStack  $requestStack,
        Security  $security,
        AccountVoter  $accountVoter,
        EntityManagerInterface $em
    ) {
        $this->authorizationService = $authorizationService;
        $this->userRepository = $userRepository;
        $this->parameterBag = $parameterBag;
        $this->publicPath = $this->parameterBag->get('kernel.project_dir');
        $this->accountRepository = $accountRepository;
        $this->request = $requestStack->getCurrentRequest();
        $this->security = $security;
        $this->accountVoter = $accountVoter;
        $this->em = $em;
    }

    public function encrypteApiKey($string = null): string
    {
        $simple_string = $string === null ? bin2hex(random_bytes(26)) : $string;
        openssl_public_encrypt($simple_string, $encrypted, file_get_contents($this->publicPath . $_ENV['RSA_PUBLIC_KEY']));
        return bin2hex($encrypted);
    }

    public function decrypteApiKey($encrypted): string
    {
        openssl_private_decrypt(hex2bin($encrypted), $decrypted, file_get_contents($this->publicPath . $_ENV['RSA_PRIVATE_KEY']));
        return $decrypted;
    }

    public function valide_user($apiKey)
    {
        $users = $this->userRepository->findAll();
        $accounts = $this->accountRepository->findAll();
        /**
         * @var UserAccountRoleRepository  $userAccountRoleRepository
         */
        $userAccountRoleRepository = $this->em->getRepository(UserAccountRole::class);
        foreach ($accounts as $account) {

            if (($account->getApiKey() != null) && $this->decrypteApiKey($account->getApiKey()) === $apiKey) {
                /**
                 * @var \App\Repository\UserAccountRoleRepository $userAccountRoleRepository
                 */
                return $userAccountRoleRepository->findAccountOwner($account)->getUser();
            }
            continue;
        }

        return null;
    }

    /**
     * Create a pair of private public cert file in only use 1 time config/rsa
     */
    public function keypairgeration()
    {


        $config = array(
            "digest_alg" => "SHA-512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);

        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res);

        $myfile = fopen($this->publicPath . $_ENV['RSA_PRIVATE_KEY'], "w") or die("Unable to open file!");
        fwrite($myfile, $privKey);
        fclose($myfile);
        $myfile2 = fopen($this->publicPath . $_ENV['RSA_PUBLIC_KEY'], "w") or die("Unable to open file!");
        fwrite($myfile2, $pubKey["key"]);
        fclose($myfile2);
    }

    public function getOrCreateApiKey(Account $account)
    {
        if (($account->getApiKey() == '' || $account->getApiKey() == null)) {
            $apiKey = $this->encrypteApiKey();
            $account->setApiKey($apiKey);

            $this->accountRepository->add($account);
        }

        $data = ['account_uuid' => $account->getUuid(), 'apiKey' => $this->decrypteApiKey($account->getApiKey())];

        return (new JsonResponseMessage())->setContent($data)->setError(['success'])->setCode(Response::HTTP_OK);
    }

    public function generateApiKey($user = null)
    {

        $account = $this->accountRepository->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_GENERATE_APIKEY]);


        return $this->getOrCreateApiKey($account);
    }
}
