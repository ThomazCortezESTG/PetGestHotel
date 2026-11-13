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
 *
 * @property User $user
 */
class UserProfile extends \yii\db\ActiveRecord
{


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
            [['nome', 'data_nascimento', 'morada', 'telefone', 'n_contribuinte'], 'default', 'value' => null],
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['data_nascimento'], 'safe'],
            [['nome', 'morada'], 'string', 'max' => 255],
            [['telefone', 'n_contribuinte'], 'string', 'max' => 9],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'nome' => 'Nome',
            'data_nascimento' => 'Data Nascimento',
            'morada' => 'Morada',
            'telefone' => 'Telefone',
            'n_contribuinte' => 'N Contribuinte',
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
