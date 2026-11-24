<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "quarto".
 *
 * @property int $id
 * @property string $categoria
 * @property int $capacidade
 * @property string|null $tier
 *
 * @property BookingAnimalQuarto[] $bookingAnimalQuartos
 */
class Quarto extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'quarto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tier'], 'default', 'value' => null],
            [['categoria', 'capacidade'], 'required'],
            [['capacidade'], 'integer'],
            [['categoria', 'tier'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoria' => 'Categoria',
            'capacidade' => 'Capacidade',
            'tier' => 'Tier',
        ];
    }

    /**
     * Gets query for [[BookingAnimalQuartos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimalQuartos()
    {
        return $this->hasMany(BookingAnimalQuarto::class, ['id_quarto' => 'id']);
    }

}
