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
 * @property string|null $foto nome do ficheiro na pasta "animal.path" (sem regra: só o PetController o altera)
 *
 * @property AnimalDocumento[] $documentos documentos de vacinas
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
            'data' => 'Data de Nascimento',
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
     * Documentos de vacinas, do mais antigo para o mais recente.
     */
    public function getDocumentos()
    {
        return $this->hasMany(AnimalDocumento::class, ['id_animal' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    /**
     * URL da fotografia (servida pelo PetController, só para o dono), ou null se não tiver.
     * O nome do ficheiro vai no URL para a cache do browser mudar quando a foto muda.
     */
    public function getFotoUrl(): ?string
    {
        return $this->foto ? \yii\helpers\Url::to(['/pet/foto', 'id' => $this->id, 'v' => substr($this->foto, 0, 8)]) : null;
    }

    /** Ficheiros a apagar do disco depois de o animal ser apagado da base de dados. */
    private array $ficheirosParaApagar = [];

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        // os documentos saem da BD em cascata; os ficheiros têm de ser apagados à mão
        $this->ficheirosParaApagar = array_filter(array_merge(
            [$this->foto],
            AnimalDocumento::find()->select('ficheiro')->where(['id_animal' => $this->id])->column()
        ));
        return true;
    }

    public function afterDelete()
    {
        parent::afterDelete();
        foreach ($this->ficheirosParaApagar as $nome) {
            \common\components\PetStorage::delete($nome);
        }
    }

    /**
     * Espécie, a partir da raça principal ("Cão", "Gato", "Outros"), ou null sem raça.
     */
    public function getEspecie(): ?string
    {
        return $this->racaPrincipal?->tipo;
    }

    /**
     * Raça em texto: "Labrador Retriever" ou "Labrador Retriever cruzado com Caniche (Poodle)".
     */
    public function getRacaTexto(): ?string
    {
        if ($this->racaPrincipal === null) {
            return null;
        }
        $texto = $this->racaPrincipal->nome;
        if ($this->racaSecundaria !== null) {
            $texto .= ' cruzado com ' . $this->racaSecundaria->nome;
        }
        return $texto;
    }

    /**
     * Idade a partir da data de nascimento: "3 anos", "8 meses", "2 semanas" ou null se não houver data.
     */
    public function getIdadeTexto(): ?string
    {
        if ($this->data === null || $this->data === '') {
            return null;
        }
        try {
            $nasc = new \DateTimeImmutable($this->data);
        } catch (\Exception) {
            return null;
        }
        $hoje = new \DateTimeImmutable('today');
        if ($nasc > $hoje) {
            return null;
        }
        $d = $nasc->diff($hoje);
        if ($d->y > 0) {
            return $d->y . ($d->y === 1 ? ' ano' : ' anos');
        }
        if ($d->m > 0) {
            return $d->m . ($d->m === 1 ? ' mês' : ' meses');
        }
        $semanas = intdiv($d->days, 7);
        return $semanas > 0 ? $semanas . ($semanas === 1 ? ' semana' : ' semanas') : 'Recém-nascido';
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