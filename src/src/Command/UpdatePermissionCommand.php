<?php

namespace App\Command;

use App\Entity\AccountRoleRight;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Repository\AccountRepository;
use App\Repository\AccountRoleRightRepository;
use App\Repository\RightRepository;
use App\Repository\RoleRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Services\Permission\Account\AccountRoleRightService;
use App\Utils\RoleBuilder;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePermissionCommand extends Command
{
    protected static $defaultName = 'app:permission';
    public $em;
    protected $videoRepository;
    protected $simulationRepository;
    protected $storage;
    private $output;
    private $encodeRepository;
    /**
     * @var VideoManager
     */
    private $videoManager;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    private $roleRepository;
    private $rightRepository;
    private $userRepository;
    private $accountRoleRightRepository;
    private $userAccountRoleRepository;
    private $accountRoleRightService;

    public function __construct(
        AccountRepository $accountRepository,
        RoleRepository $roleRepository,
        RightRepository $rightRepository,
        UserRepository $userRepository,
        AccountRoleRightRepository $accountRoleRightRepository,
        UserAccountRoleRepository $userAccountRoleRepository,
        AccountRoleRightService $accountRoleRightService,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
        $this->accountRepository = $accountRepository;
        $this->roleRepository = $roleRepository;
        $this->rightRepository = $rightRepository;
        $this->userRepository = $userRepository;
        $this->accountRoleRightRepository = $accountRoleRightRepository;
        $this->userAccountRoleRepository = $userAccountRoleRepository;
        $this->accountRoleRightService = $accountRoleRightService;
        parent::__construct();
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->getFormatter()->setStyle('good', $outputStyle2);

        $accounts = $this->accountRepository->findAll();
        $roles = $this->roleRepository->findAll();

        foreach ($accounts as $account) {


            foreach ($roles as $role) {
                $this->accountRoleRightService->initDefaultRight($role, $account);
                $output->writeln(
                    "<good>" .
                        (new DateTimeImmutable())->format("y:m:d H:i:s") .
                        " | INFO " .
                        " | Account uuid " . $account->getUuid() .
                        " | Account email " . $account->getEmail() .
                        " | Role code " . $role->getCode() .

                        "</good>"
                );
            }
        }
    }
}
