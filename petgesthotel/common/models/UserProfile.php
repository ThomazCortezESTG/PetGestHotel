<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_profile".
 *
 * @property int $user_id
 * @property string|null $nome
 * @property string|null $data_nascimento
 * @property string|null $morada
 * @property string|null $telefone
 * @property string|null $n_contribuinte
 * @property string|null $avatar nome do ficheiro em frontend/web/uploads/avatars (não tem regra: só o PerfilController o altera)
 *
 * @property User $user
 */
class UserProfile extends \yii\db\ActiveRecord
{
    /** Pasta das fotografias de perfil, relativa ao webroot do frontend. */
    public const AVATAR_DIR = 'uploads/avatars';

    /**
     * URL público da fotografia, ou null se não tiver.
     */
    public function getAvatarUrl(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        $base = Yii::$app->has('urlManagerFrontend') && Yii::$app->id !== 'app-frontend'
            ? rtrim((string) Yii::$app->urlManagerFrontend->baseUrl, '/')
            : Yii::getAlias('@web');
        return $base . '/' . self::AVATAR_DIR . '/' . $this->avatar;
    }

    /**
     * Iniciais para o avatar quando não há fotografia ("Ana Silva" => "AS").
     */
    public static function initialsOf(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];
        $partes = array_values(array_filter($partes));
        if ($partes === []) {
            return '?';
        }
        $ini = mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr((string) end($partes), 0, 1) : '');
        return mb_strtoupper($ini);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Limpeza primeiro (antes do limite de 9 caracteres): "912 345 678" -> "912345678"
            [['nome', 'morada'], 'trim', 'on' => self::SCENARIO_CLIENTE],
            [['telefone', 'n_contribuinte'], 'filter', 'filter' => static fn($v) => $v === null ? null : preg_replace('/\s+/', '', (string) $v), 'on' => self::SCENARIO_CLIENTE],

            [['nome', 'data_nascimento', 'morada', 'telefone', 'n_contribuinte'], 'default', 'value' => null],
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['data_nascimento'], 'safe'],
            [['nome', 'morada'], 'string', 'max' => 255],
            [['telefone', 'n_contribuinte'], 'string', 'max' => 9],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],

            // Perfil preenchido pelo próprio cliente no frontend (RF-FO-10 / RF-FO-11)
            [['nome', 'telefone'], 'required', 'on' => self::SCENARIO_CLIENTE, 'message' => 'Preencha o campo «{attribute}».'],
            ['nome', 'string', 'min' => 3, 'on' => self::SCENARIO_CLIENTE],
            ['telefone', 'match', 'pattern' => '/^[239]\d{8}$/', 'on' => self::SCENARIO_CLIENTE, 'message' => 'Indique um número português com 9 algarismos.'],
            ['n_contribuinte', 'match', 'pattern' => '/^\d{9}$/', 'on' => self::SCENARIO_CLIENTE, 'message' => 'O NIF tem 9 algarismos.'],
            ['n_contribuinte', 'validateNif', 'on' => self::SCENARIO_CLIENTE],
            ['data_nascimento', 'date', 'format' => 'php:Y-m-d', 'max' => date('Y-m-d', strtotime('-18 years')), 'tooBig' => 'Tem de ter pelo menos 18 anos.', 'on' => self::SCENARIO_CLIENTE],
        ];
    }

    /** Cenário usado quando o cliente edita o seu próprio perfil no frontend. */
    public const SCENARIO_CLIENTE = 'cliente';

    /**
     * Valida o dígito de controlo do NIF português.
     */
    public function validateNif(string $attribute): void
    {
        $nif = (string) $this->$attribute;
        if (!preg_match('/^\d{9}$/', $nif)) {
            return; // o erro de formato já é dado pela regra "match"
        }
        $soma = 0;
        for ($i = 0; $i < 8; $i++) {
            $soma += (int) $nif[$i] * (9 - $i);
        }
        $controlo = 11 - ($soma % 11);
        if ($controlo >= 10) {
            $controlo = 0;
        }
        if ($controlo !== (int) $nif[8]) {
            $this->addError($attribute, 'Este NIF não é válido.');
        }
    }

    /**
     * O perfil está completo quando tem os dados necessários para reservar (RF: impedir reserva sem perfil completo).
     */
    public function isComplete(): bool
    {
        return trim((string) $this->nome) !== '' && trim((string) $this->telefone) !== '';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'nome' => 'Nome',
            'data_nascimento' => 'Data de nascimento',
            'morada' => 'Morada',
            'telefone' => 'Telefone',
            'n_contribuinte' => 'NIF',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
