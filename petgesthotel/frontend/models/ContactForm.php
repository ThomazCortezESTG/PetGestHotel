<?php

declare(strict_types=1);

namespace frontend\models;

use yii\base\Model;
use yii\mail\MailerInterface;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    /** Assuntos do formulário (valor enviado => texto mostrado). */
    public const ASSUNTOS = [
        'Reservas e disponibilidade' => 'Reservas e disponibilidade',
        'Visitar o hotel' => 'Visitar o hotel',
        'Preços e serviços' => 'Preços e serviços',
        'Uma estadia a decorrer' => 'Uma estadia a decorrer',
        'Outro assunto' => 'Outro assunto',
    ];

    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $body = '';
    public string $verifyCode = '';
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'email', 'subject', 'body'], 'trim'],
            [['name', 'email', 'subject', 'body'], 'required'],
            ['name', 'string', 'max' => 100],
            ['email', 'email'],
            ['subject', 'in', 'range' => array_keys(self::ASSUNTOS)],
            ['body', 'string', 'min' => 10, 'max' => 3000],
            // verifyCode needs to be entered correctly
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'name' => 'Nome',
            'email' => 'Email',
            'subject' => 'Assunto',
            'body' => 'Mensagem',
            'verifyCode' => 'Código de verificação',
        ];
    }

    /**
     * Envia a mensagem para a caixa do hotel.
     *
     * O Gmail obriga a que o remetente seja a própria conta do hotel (aparece como "eu"),
     * por isso o nome e o email do cliente vão no assunto e no topo do texto.
     * O Reply-To é o email do cliente: ao carregar em "Responder", a resposta vai para ele.
     *
     * @param MailerInterface $mailer the mailer component.
     * @param string $email the target email address.
     * @param string $senderEmail the sender email address.
     * @param string $senderName the sender name.
     *
     * @return bool whether the email was sent.
     */
    public function sendEmail(MailerInterface $mailer, string $email, string $senderEmail, string $senderName): bool
    {
        $texto = "Nova mensagem do formulário de contacto do site.\n"
            . "Para responder ao cliente, use \"Responder\": a resposta vai para o email dele.\n\n"
            . "Nome: {$this->name}\n"
            . "Email: {$this->email}\n"
            . "Assunto: {$this->subject}\n"
            . "Enviada em: " . date('d/m/Y H:i') . "\n"
            . str_repeat('-', 40) . "\n\n"
            . $this->body . "\n";

        return $mailer->compose()
            ->setTo($email)
            ->setFrom([$senderEmail => "{$this->name} via {$senderName}"])
            ->setReplyTo([$this->email => $this->name])
            ->setSubject("[Contacto] {$this->subject} – {$this->name}")
            ->setTextBody($texto)
            ->send();
    }
}
