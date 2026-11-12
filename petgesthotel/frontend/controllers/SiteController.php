<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\LoginForm;
use frontend\models\ContactForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\mail\MailerInterface;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $this->layout = 'landing';
        return $this->render('index');
    }

    /**
     * Logs in a user.
     *
     * @return string|Response
     */
    public function actionLogin(): string|Response
    {
        $this->layout = 'auth';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goHome();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string|Response
     */
    public function actionContact(): string|Response
    {
        $model = new ContactForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $sent = $model->sendEmail(
                $this->mailer,
                Yii::$app->params['adminEmail'],
                Yii::$app->params['senderEmail'],
                Yii::$app->params['senderName'],
            );

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        $this->layout = 'landing';
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return string|Response
     */
    public function actionSignup(): string|Response
    {
        $this->layout = 'auth';

        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post())) {
            $signed = $model->signup(
                $this->mailer,
                Yii::$app->params['supportEmail'],
                Yii::$app->name,
            );

            if ($signed) {
                Yii::$app->session->setFlash('success', 'Obrigado por se registar! Verifique a sua caixa de correio para confirmar o e-mail.');
                return $this->redirect(['site/login']);
            }

            // a conta foi criada, mas o e-mail falhou: sem isto o utilizador ficava preso em "nome já usado"
            if ($signed === false) {
                Yii::$app->session->setFlash('error', 'Não foi possível enviar o e-mail de confirmação. Tente reenviá-lo aqui.');
                return $this->redirect(['site/resend-verification-email']);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return string|Response
     */
    public function actionRequestPasswordReset(): string|Response
    {
        $this->layout = 'auth';

        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // a mensagem é sempre a mesma, para não revelar se o e-mail tem conta
            $model->sendEmail(
                $this->mailer,
                Yii::$app->params['supportEmail'],
                Yii::$app->name,
            );

            Yii::$app->session->setFlash('success', 'Se existir uma conta com este e-mail, vai receber as instruções para recuperar a password.');

            return $this->redirect(['site/login']);
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return string|Response
     * @throws BadRequestHttpException
     */
    public function actionResetPassword(string $token): string|Response
    {
        $this->layout = 'auth';

        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'A nova password foi guardada.');

            return $this->redirect(['site/login']);
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionVerifyEmail(string $token): Response
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->verifyEmail()) {
            $model->sendWelcomeEmail($this->mailer, Yii::$app->params['supportEmail'], Yii::$app->name);
            Yii::$app->session->setFlash('success', 'O seu e-mail foi confirmado!');
            return $this->redirect(['site/login']);
        }

        Yii::$app->session->setFlash('error', 'Não foi possível confirmar a sua conta com este link.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return string|Response
     */
    public function actionResendVerificationEmail(): string|Response
    {
        $this->layout = 'auth';

        $model = new ResendVerificationEmailForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // a mensagem é sempre a mesma, para não revelar se o e-mail tem conta
            $model->sendEmail(
                $this->mailer,
                Yii::$app->params['supportEmail'],
                Yii::$app->name,
            );

            Yii::$app->session->setFlash('success', 'Se existir uma conta por confirmar com este e-mail, vai receber um novo link de confirmação.');

            return $this->redirect(['site/login']);
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model,
        ]);
    }
}
