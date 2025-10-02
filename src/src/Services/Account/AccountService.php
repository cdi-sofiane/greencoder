<?php


namespace App\Services\Account;

use App\Form\AccountType;
use Symfony\Component\Form\FormFactoryInterface;

class AccountService
{

    private $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function edit($account, $body)
    {
        $form = $this->formFactory->create(AccountType::class, $account);

        $form->submit($body);

        return $form->getData();
    }

}
