<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Reserva (tabela "booking").
 *
 * Por agora só tem o essencial para o chat: estados, relações e a abertura da conversa
 * quando a reserva é confirmada. O resto (criar, preço, quartos...) vem com o módulo das reservas.
 *
 * Ciclo de vida:
 *   pendente ──> confirmada ──> em_curso ──> concluida
 *       │             │
 *       └> rejeitada  └> cancelada
 *
 * @property int $id
 * @property string $datainicio
 * @property string $datafim
 * @property string|null $preco
 * @property string $status
 * @property int $id_user
 *
 * @property User $user
 * @property Animal[] $animais
 * @property Conversa|null $conversa
 */
class Booking extends ActiveRecord
{
    public const STATUS_PENDENTE = 'pendente';     // pedido do cliente, à espera do hotel
    public const STATUS_CONFIRMADA = 'confirmada'; // aceite por um colaborador (abre o chat)
    public const STATUS_EM_CURSO = 'em_curso';     // check-in feito: o animal está no hotel
    public const STATUS_CONCLUIDA = 'concluida';   // check-out feito
    public const STATUS_REJEITADA = 'rejeitada';   // recusada pelo hotel
    public const STATUS_CANCELADA = 'cancelada';   // cancelada pelo cliente

    public static function tableName(): string
    {
        return '{{%booking}}';
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_CONFIRMADA => 'Confirmada',
            self::STATUS_EM_CURSO => 'Em curso',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_REJEITADA => 'Rejeitada',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    public function rules(): array
    {
        return [
            [['datainicio', 'datafim', 'id_user'], 'required'],
            [['datainicio', 'datafim'], 'date', 'format' => 'php:Y-m-d'],
            ['datafim', 'compare', 'compareAttribute' => 'datainicio', 'operator' => '>=', 'type' => 'date', 'message' => 'A data de saída tem de ser igual ou posterior à de entrada.'],
            ['preco', 'number', 'min' => 0],
            ['status', 'default', 'value' => self::STATUS_PENDENTE],
            ['status', 'in', 'range' => array_keys(self::statusLabels())],
            ['id_user', 'exist', 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'datainicio' => 'Entrada',
            'datafim' => 'Saída',
            'preco' => 'Preço',
            'status' => 'Estado',
            'id_user' => 'Cliente',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'id_user']);
    }

    public function getAnimais()
    {
        return $this->hasMany(Animal::class, ['id' => 'id_animal'])
            ->viaTable('{{%booking_animal_quarto}}', ['id_booking' => 'id']);
    }

    public function getConversa()
    {
        return $this->hasOne(Conversa::class, ['id_booking' => 'id']);
    }

    /**
     * Confirma a reserva. O colaborador que confirma fica responsável pela conversa com o cliente.
     */
    public function confirmar(int $idColaborador): bool
    {
        $this->status = self::STATUS_CONFIRMADA;
        $this->confirmadaPor = $idColaborador;
        return $this->save(false, ['status']);
    }

    /** Quem confirmou (só durante o save, para abrir a conversa com o responsável certo). */
    private ?int $confirmadaPor = null;

    /**
     * Seja qual for o código que muda o estado para "confirmada", abre-se a conversa.
     * Responsável: quem confirmou (confirmar()), ou o colaborador autenticado; sem nenhum dos dois,
     * a conversa fica sem responsável e um admin atribui-a no back-office.
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        $passouAConfirmada = ($insert || array_key_exists('status', $changedAttributes))
            && $this->status === self::STATUS_CONFIRMADA;
        if (!$passouAConfirmada || Conversa::find()->where(['id_booking' => $this->id])->exists()) {
            return;
        }

        $responsavel = $this->confirmadaPor;
        if ($responsavel === null && Yii::$app instanceof \yii\web\Application && !Yii::$app->user->isGuest
            && Conversa::podeSerResponsavel((int) Yii::$app->user->id)) {
            $responsavel = (int) Yii::$app->user->id;
        }
        Conversa::abrir($this, $responsavel);
    }

    /** Fotografias e vídeos do chat a apagar do disco depois de a reserva sair da base de dados. */
    private array $anexosParaApagar = [];

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        // a conversa e as mensagens saem em cascata; os ficheiros têm de ser apagados à mão
        $this->anexosParaApagar = Mensagem::find()
            ->alias('m')
            ->innerJoin(['c' => Conversa::tableName()], 'c.id = m.id_conversa')
            ->select('m.anexo')
            ->where(['c.id_booking' => $this->id])
            ->andWhere(['not', ['m.anexo' => null]])
            ->column();
        Notificacao::deleteAll([
            'tipo' => Notificacao::TIPO_MENSAGEM,
            'ref_id' => Conversa::find()->select('id')->where(['id_booking' => $this->id])->column(),
        ]);
        return true;
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
        foreach ($this->anexosParaApagar as $nome) {
            \common\components\ChatStorage::delete($nome);
        }
    }
}
