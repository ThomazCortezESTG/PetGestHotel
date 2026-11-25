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
            ['datafim', 'compare', 'compareAttribute' => 'datainicio', 'operator' => '>', 'type' => 'string',
                'message' => 'A data de fim tem de ser posterior à data de início.'],
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

    public function calcularPreco()
    {
        $inicio = new \DateTime($this->datainicio);
        $fim = new \DateTime($this->datafim);
        $dias = max(1, $inicio->diff($fim)->days);

        // um quarto por animal: soma o preço de cada linha
        $totalQuartos = (new \yii\db\Query())
            ->from('booking_animal_quarto baq')
            ->innerJoin('quarto q', 'q.id = baq.id_quarto')
            ->where(['baq.id_booking' => $this->id])
            ->sum('q.preco') ?: 0;

        // serviços extras (uma linha por animal e por serviço)
        $totalServicos = (new \yii\db\Query())
            ->from('booking_animal_servico bas')
            ->innerJoin('booking_animal_quarto baq', 'baq.id = bas.id_booking_animal')
            ->innerJoin('servicos_extras se', 'se.id = bas.id_servico')
            ->where(['baq.id_booking' => $this->id])
            ->sum('se.preco') ?: 0;

        $this->preco = ($totalQuartos * $dias) + $totalServicos;
        $this->save(false, ['preco']);
    }

}
