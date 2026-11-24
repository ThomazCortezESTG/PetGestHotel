<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "booking".
 *
 * @property int $id
 * @property string $datainicio
 * @property string $datafim
 * @property float|null $preco
 * @property string|null $status
 * @property int $id_user
 *
 * @property BookingAnimalQuarto[] $bookingAnimalQuartos
 * @property User $user
 */
class Booking extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'booking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['preco'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'pendente'],
            [['datainicio', 'datafim', 'id_user'], 'required'],
            [['datainicio', 'datafim'], 'safe'],
            [['preco'], 'number'],
            [['id_user'], 'integer'],
            [['status'], 'string', 'max' => 30],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'datainicio' => 'Datainicio',
            'datafim' => 'Datafim',
            'preco' => 'Preco',
            'status' => 'Status',
            'id_user' => 'Id User',
        ];
    }

    /**
     * Gets query for [[BookingAnimalQuartos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimalQuartos()
    {
        return $this->hasMany(BookingAnimalQuarto::class, ['id_booking' => 'id']);
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
