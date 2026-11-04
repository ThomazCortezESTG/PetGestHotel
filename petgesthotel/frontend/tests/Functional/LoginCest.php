<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use frontend\tests\Support\FunctionalTester;

final class LoginCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
        ];
    }

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
    }

    protected function formParams(string $login, string $password): array
    {
        return [
            'LoginForm[username]' => $login,
            'LoginForm[password]' => $password,
        ];
    }

    public function checkEmpty(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('', ''));
        $I->seeValidationError('“E-mail ou nome de utilizador” não pode ficar em branco.');
        $I->seeValidationError('“Password” não pode ficar em branco.');
    }

    public function checkWrongPassword(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('admin', 'wrong'));
        $I->seeValidationError('E-mail, nome de utilizador ou password incorretos.');
    }

    public function checkInactiveAccount(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('test.test', 'Test1234'));
        $I->seeValidationError('E-mail, nome de utilizador ou password incorretos.');
    }

    public function checkValidLogin(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('erau', 'password_0'));
        $I->seeLink('Logout (erau)');
        $I->dontSeeLink('Login');
        $I->dontSeeLink('Signup');
    }
}
