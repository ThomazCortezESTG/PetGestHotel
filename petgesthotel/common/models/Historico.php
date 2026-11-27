<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "historico".
 *
 * @property int $id
 * @property string $descricao
 * @property string $data_hora
 * @property int $id_booking_animal
 *
 * @property BookingAnimalQuarto $bookingAnimal
 */
class Historico extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'historico';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao', 'id_booking_animal'], 'required'],
            [['data_hora'], 'safe'],
            [['id_booking_animal'], 'integer'],
            [['descricao'], 'string', 'max' => 255],
            [['id_booking_animal'], 'exist', 'skipOnError' => true, 'targetClass' => BookingAnimalQuarto::class, 'targetAttribute' => ['id_booking_animal' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descricao' => 'Descricao',
            'data_hora' => 'Data Hora',
            'id_booking_animal' => 'Id Booking Animal',
        ];
    }

    /**
     * Gets query for [[BookingAnimal]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimal()
    {
        return $this->hasOne(BookingAnimalQuarto::class, ['id' => 'id_booking_animal']);
    }

}
