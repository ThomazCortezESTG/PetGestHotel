<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use common\models\User;
use frontend\tests\Support\FunctionalTester;

final class ResendVerificationEmailCest
{
    protected string $formId = '#resend-verification-email-form';
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/resend-verification-email');
    }

    protected function formParams(string $email): array
    {
        return [
            'ResendVerificationEmailForm[email]' => $email,
        ];
    }

    public function checkPage(FunctionalTester $I): void
    {
        $I->see('Reenviar e-mail de confirmação', 'h1');
        $I->see('Insira o e-mail da sua conta e enviaremos um novo link de confirmação.');
    }

    public function checkEmptyField(FunctionalTester $I): void
    {
        $I->submitForm($this->formId, $this->formParams(''));
        $I->seeValidationError('“E-mail” não pode ficar em branco.');
    }

    public function checkWrongEmailFormat(FunctionalTester $I): void
    {
        $I->submitForm($this->formId, $this->formParams('abcd.com'));
        $I->seeValidationError('“E-mail” não é um endereço de e-mail válido.');
    }

    public function checkWrongEmail(FunctionalTester $I): void
    {
        $I->submitForm($this->formId, $this->formParams('wrong@email.com'));
        $I->dontSeeEmailIsSent();
        $I->see('Se existir uma conta por confirmar com este e-mail, vai receber um novo link de confirmação.');
    }

    public function checkAlreadyVerifiedEmail(FunctionalTester $I): void
    {
        $I->submitForm($this->formId, $this->formParams('test2@mail.com'));
        $I->dontSeeEmailIsSent();
        $I->see('Se existir uma conta por confirmar com este e-mail, vai receber um novo link de confirmação.');
    }

    public function checkSendSuccessfully(FunctionalTester $I): void
    {
        $I->submitForm($this->formId, $this->formParams('test@mail.com'));
        $I->canSeeEmailIsSent();
        $I->seeRecord(
            User::class, 
            [
                'email' => 'test@mail.com',
                'username' => 'test.test',
                'status' => User::STATUS_INACTIVE,
            ],
        );
        $I->see('Se existir uma conta por confirmar com este e-mail, vai receber um novo link de confirmação.');
    }
}
