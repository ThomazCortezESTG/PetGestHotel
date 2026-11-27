<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "animal".
 *
 * @property int $id
 * @property string $nome
 * @property string|null $data
 * @property string|null $vacinas
 * @property string|null $observacoes
 * @property int $id_user
 * @property int|null $id_raca
 *
 * @property BookingAnimalQuarto[] $bookingAnimalQuartos
 * @property Raca $raca
 * @property User $user
 */
class Animal extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'animal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data', 'vacinas', 'observacoes', 'id_raca'], 'default', 'value' => null],
            [['nome', 'id_user'], 'required'],
            [['data'], 'safe'],
            [['vacinas'], 'string'],
            [['id_user', 'id_raca'], 'integer'],
            [['nome'], 'string', 'max' => 100],
            [['observacoes'], 'string', 'max' => 255],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id']],
            [['id_raca'], 'exist', 'skipOnError' => true, 'targetClass' => Raca::class, 'targetAttribute' => ['id_raca' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'data' => 'Data',
            'vacinas' => 'Vacinas',
            'observacoes' => 'Observacoes',
            'id_user' => 'Id User',
            'id_raca' => 'Id Raca',
        ];
    }

    /**
     * Gets query for [[BookingAnimalQuartos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimalQuartos()
    {
        return $this->hasMany(BookingAnimalQuarto::class, ['id_animal' => 'id']);
    }

    /**
     * Gets query for [[Raca]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRaca()
    {
        return $this->hasOne(Raca::class, ['id' => 'id_raca']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'id_user']);
    }

}
