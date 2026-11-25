<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "servicos_extras".
 *
 * @property int $id
 * @property string $nome
 * @property string|null $descricao
 * @property float $preco
 *
 * @property BookingAnimalServico[] $bookingAnimalServicos
 * @property BookingAnimalQuarto[] $bookingAnimals
 */
class ServicosExtras extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'servicos_extras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao'], 'default', 'value' => null],
            [['nome', 'preco'], 'required'],
            [['descricao'], 'string'],
            [['preco'], 'number'],
            [['nome'], 'string', 'max' => 100],
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
            'descricao' => 'Descricao',
            'preco' => 'Preco',
        ];
    }

    /**
     * Gets query for [[BookingAnimalServicos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimalServicos()
    {
        return $this->hasMany(BookingAnimalServico::class, ['id_servico' => 'id']);
    }

    /**
     * Gets query for [[BookingAnimals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookingAnimals()
    {
        return $this->hasMany(BookingAnimalQuarto::class, ['id' => 'id_booking_animal'])->viaTable('booking_animal_servico', ['id_servico' => 'id']);
    }

}
