<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "animal".
 *
 * @property int $id
 * @property string $nome
 * @property string|null $data
 * @property string|null $vacinas
 * @property string|null $observacoes
 * @property int $id_user
 * @property int|null $id_raca_principal
 * @property int|null $id_raca_secundaria
 *
 * @property BookingAnimalQuarto[] $bookingAnimalQuartos
 * @property Raca $racaPrincipal
 * @property Raca $racaSecundaria
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
     *
     * Preenche automaticamente o id_user com o utilizador autenticado
     * no momento em que o animal é criado.
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'id_user',
                'updatedByAttribute' => false, // não altera o dono ao editar
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data', 'vacinas', 'observacoes', 'id_raca_principal', 'id_raca_secundaria'], 'default', 'value' => null],
            [['nome'], 'required'],
            [['data'], 'safe'],
            [['id_raca_principal', 'id_raca_secundaria'], 'integer'],
            [['nome'], 'string', 'max' => 100],
            [['vacinas', 'observacoes'], 'string', 'max' => 255],
            // A raça secundária não pode ser igual à principal
            [['id_raca_secundaria'], 'compare', 'compareAttribute' => 'id_raca_principal', 'operator' => '!=', 'message' => 'A raça secundária tem de ser diferente da principal.'],
            [['id_raca_principal'], 'exist', 'skipOnError' => true, 'targetClass' => Raca::class, 'targetAttribute' => ['id_raca_principal' => 'id']],
            [['id_raca_secundaria'], 'exist', 'skipOnError' => true, 'targetClass' => Raca::class, 'targetAttribute' => ['id_raca_secundaria' => 'id']],
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
            'observacoes' => 'Observações',
            'id_user' => 'Utilizador',
            'id_raca_principal' => 'Raça principal',
            'id_raca_secundaria' => 'Raça secundária',
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
     * Gets query for [[RacaPrincipal]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRacaPrincipal()
    {
        return $this->hasOne(Raca::class, ['id' => 'id_raca_principal']);
    }

    /**
     * Gets query for [[RacaSecundaria]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRacaSecundaria()
    {
        return $this->hasOne(Raca::class, ['id' => 'id_raca_secundaria']);
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