<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "booking_animal_quarto".
 *
 * @property int $id
 * @property int $id_booking
 * @property int $id_animal
 * @property int $id_quarto
 *
 * @property Animal $animal
 * @property Booking $booking
 * @property BookingAnimalServico[] $bookingAnimalServicos
 * @property Historico[] $historicos
 * @property Quarto $quarto
 * @property ServicosExtras[] $servicos
 */
class BookingAnimalQuarto extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'booking_animal_quarto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_booking', 'id_animal', 'id_quarto'], 'required'],
            [['id_booking', 'id_animal', 'id_quarto'], 'integer'],
            [['id_booking'], 'exist', 'skipOnError' => true, 'targetClass' => Booking::class, 'targetAttribute' => ['id_booking' => 'id']],
            [['id_animal'], 'exist', 'skipOnError' => true, 'targetClass' => Animal::class, 'targetAttribute' => ['id_animal' => 'id']],
            [['id_quarto'], 'exist', 'skipOnError' => true, 'targetClass' => Quarto::class, 'targetAttribute' => ['id_quarto' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_booking' => 'Id Booking',
            'id_animal' => 'Id Animal',
            'id_quarto' => 'Id Quarto',
        ];
    }

    /**
     * Gets query for [[Animal]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAnimal()
    {
        return $this->hasOne(Animal::class, ['id' => 'id_animal']);
    }

    /**
     * Gets query for [[Booking]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBooking()
    {
        return $this->hasOne(Booking::class, ['id' => 'id_booking']);
    }

    /**
     * Gets query for [[BookingAnimalServicos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimalServicos()
    {
        return $this->hasMany(BookingAnimalServico::class, ['id_booking_animal' => 'id']);
    }

    /**
     * Gets query for [[Historicos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHistoricos()
    {
        return $this->hasMany(Historico::class, ['id_booking_animal' => 'id']);
    }

    /**
     * Gets query for [[Quarto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuarto()
    {
        return $this->hasOne(Quarto::class, ['id' => 'id_quarto']);
    }

    /**
     * Gets query for [[Servicos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServicos()
    {
        return $this->hasMany(ServicosExtras::class, ['id' => 'id_servico'])->viaTable('booking_animal_servico', ['id_booking_animal' => 'id']);
    }

}
