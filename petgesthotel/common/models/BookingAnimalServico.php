<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "booking_animal_servico".
 *
 * @property int $id_booking_animal
 * @property int $id_servico
 *
 * @property BookingAnimalQuarto $bookingAnimal
 * @property ServicosExtras $servico
 */
class BookingAnimalServico extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'booking_animal_servico';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_booking_animal', 'id_servico'], 'required'],
            [['id_booking_animal', 'id_servico'], 'integer'],
            [['id_booking_animal', 'id_servico'], 'unique', 'targetAttribute' => ['id_booking_animal', 'id_servico']],
            [['id_booking_animal'], 'exist', 'skipOnError' => true, 'targetClass' => BookingAnimalQuarto::class, 'targetAttribute' => ['id_booking_animal' => 'id']],
            [['id_servico'], 'exist', 'skipOnError' => true, 'targetClass' => ServicosExtras::class, 'targetAttribute' => ['id_servico' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_booking_animal' => 'Id Booking Animal',
            'id_servico' => 'Id Servico',
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

    /**
     * Gets query for [[Servico]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServico()
    {
        return $this->hasOne(ServicosExtras::class, ['id' => 'id_servico']);
    }

}
