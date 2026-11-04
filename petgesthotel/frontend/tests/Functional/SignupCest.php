<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\User;
use frontend\tests\Support\FunctionalTester;

final class SignupCest
{
    protected string $formId = '#form-signup';
    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/signup');
    }

    public function signupWithEmptyFields(FunctionalTester $I): void
    {
        $I->see('Crie a sua conta', 'h1');
        $I->submitForm($this->formId, []);
        $I->seeValidationError('“Nome de utilizador” não pode ficar em branco.');
        $I->seeValidationError('“E-mail” não pode ficar em branco.');
        $I->seeValidationError('“Password” não pode ficar em branco.');
    }

    public function signupWithWrongEmail(FunctionalTester $I): void
    {
        $I->submitForm(
            $this->formId,
            [
                'SignupForm[username]' => 'tester',
                'SignupForm[email]' => 'ttttt',
                'SignupForm[password]' => 'tester_password',
                'SignupForm[password_repeat]' => 'tester_password',
            ]
        );
        $I->dontSee('“Nome de utilizador” não pode ficar em branco.', '.invalid-feedback');
        $I->dontSee('“Password” não pode ficar em branco.', '.invalid-feedback');
        $I->see('“E-mail” não é um endereço de e-mail válido.', '.invalid-feedback');
    }

    public function signupSuccessfully(FunctionalTester $I): void
    {
        $I->submitForm(
            $this->formId, 
            [
                'SignupForm[username]' => 'tester',
                'SignupForm[email]' => 'tester.email@example.com',
                'SignupForm[password]' => 'tester_password',
                'SignupForm[password_repeat]' => 'tester_password',
            ],
        );

        $I->seeRecord(
            User::class, 
            [
                'username' => 'tester',
                'email' => 'tester.email@example.com',
                'status' => User::STATUS_INACTIVE,
            ],
        );

        $I->seeEmailIsSent();
        $I->see('Obrigado por se registar! Verifique a sua caixa de correio para confirmar o e-mail.');
    }
}
