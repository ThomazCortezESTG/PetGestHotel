<?php

namespace common\models;

use common\components\ChatStorage;
use Yii;

/**
 * This is the model class for table "booking".
 *
 * Ciclo de vida (constantes STATUS_*):
 *   pendente ──> confirmada ──> em_curso ──> concluida
 *       │             │
 *       └> rejeitada  └> cancelada
 *
 * Quando passa a "confirmada" (por confirmar() ou por qualquer save que mude o estado),
 * abre-se a conversa do chat com o cliente (ver afterSave e common\models\Conversa).
 * ATENÇÃO: se voltarem a gerar este modelo com o Gii, mantenham a parte do chat (marcada abaixo).
 *
 * @property int $id
 * @property string $datainicio
 * @property string $datafim
 * @property float|null $preco
 * @property string|null $status
 * @property string|null $motivo_rejeicao preenchido pelo hotel ao rejeitar
 * @property int $id_user
 *
 * @property BookingAnimalQuarto[] $bookingAnimalQuartos
 * @property User $user
 * @property Animal[] $animais
 * @property Conversa|null $conversa
 * @property-read int $noites
 */
class Booking extends \yii\db\ActiveRecord
{
    public const STATUS_PENDENTE = 'pendente';     // pedido do cliente, à espera do hotel
    public const STATUS_CONFIRMADA = 'confirmada'; // aceite por um colaborador (abre o chat)
    public const STATUS_EM_CURSO = 'em_curso';     // check-in feito: o animal está no hotel
    public const STATUS_CONCLUIDA = 'concluida';   // check-out feito
    public const STATUS_REJEITADA = 'rejeitada';   // recusada pelo hotel
    public const STATUS_CANCELADA = 'cancelada';   // cancelada pelo cliente

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'booking';
    }

    /**
     * Estados e o texto que se mostra ao utilizador.
     */
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

    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['preco'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['datainicio', 'datafim', 'id_user'], 'required'],
            [['datainicio', 'datafim'], 'safe'],
            [['preco'], 'number'],
            [['id_user'], 'integer'],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
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

    // ===================================================================== chat (não apagar)

    /**
     * Animais da reserva (através de booking_animal_quarto).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAnimais()
    {
        return $this->hasMany(Animal::class, ['id' => 'id_animal'])
            ->via('bookingAnimalQuartos');
    }

    /**
     * Conversa do chat desta reserva (existe a partir da confirmação).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConversa()
    {
        return $this->hasOne(Conversa::class, ['id_booking' => 'id']);
    }

    /** Quem confirmou (só durante o save, para abrir a conversa com o responsável certo). */
    private ?int $confirmadaPor = null;

    /**
     * Mudanças de estado que o hotel pode fazer no back-office: [estado atual => [estados seguintes]].
     */
    public static function transicoes(): array
    {
        return [
            self::STATUS_PENDENTE => [self::STATUS_CONFIRMADA, self::STATUS_REJEITADA],
            self::STATUS_CONFIRMADA => [self::STATUS_EM_CURSO],
            self::STATUS_EM_CURSO => [self::STATUS_CONCLUIDA],
        ];
    }

    public function podePassarPara(string $estado): bool
    {
        return in_array($estado, self::transicoes()[$this->status] ?? [], true);
    }

    /**
     * Aceita a reserva. $idColaborador fica responsável pela conversa com o cliente
     * (por omissão, quem aceita; no back-office pode escolher-se outra pessoa).
     */
    public function confirmar(int $idColaborador): bool
    {
        if (!Conversa::podeSerResponsavel($idColaborador)) {
            $this->addError('status', 'O responsável tem de ser um colaborador ativo.');
            return false;
        }
        if (!$this->mudarEstado(self::STATUS_CONFIRMADA)) {
            return false;
        }
        $this->confirmadaPor = $idColaborador;
        return $this->save(false, ['status']);
    }

    public function rejeitar(string $motivo): bool
    {
        if (!mb_check_encoding($motivo, 'UTF-8')) {
            $motivo = mb_convert_encoding($motivo, 'UTF-8', 'Windows-1252');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            $this->addError('motivo_rejeicao', 'Indique ao cliente porque é que a reserva não pode ser aceite.');
            return false;
        }
        if (!$this->mudarEstado(self::STATUS_REJEITADA)) {
            return false;
        }
        $this->motivo_rejeicao = mb_substr($motivo, 0, 500);
        return $this->save(false, ['status', 'motivo_rejeicao']);
    }

    /** O cliente só pode alterar o pedido enquanto o hotel ainda não respondeu. */
    public function podeSerEditadaPeloCliente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /** O cliente pode cancelar até ao check-in. */
    public function podeSerCanceladaPeloCliente(): bool
    {
        return in_array($this->status, [self::STATUS_PENDENTE, self::STATUS_CONFIRMADA], true);
    }

    /**
     * Cancelamento pelo cliente. Se já havia conversa, fica lá registado e passa a só leitura.
     */
    public function cancelar(): bool
    {
        if (!$this->podeSerCanceladaPeloCliente()) {
            $this->addError('status', 'Uma reserva ' . mb_strtolower($this->getStatusLabel()) . ' já não pode ser cancelada.');
            return false;
        }
        $this->status = self::STATUS_CANCELADA;
        if (!$this->save(false, ['status'])) {
            return false;
        }
        // o responsável vê no chat; o cliente já sabe (foi ele que cancelou)
        $this->conversa?->mensagemSistema('A reserva foi cancelada pelo cliente.', false);
        return true;
    }

    /** Noites da estadia (mínimo 1). */
    public function getNoites(): int
    {
        if (empty($this->datainicio) || empty($this->datafim)) {
            return 0;
        }
        return max(1, (int) (new \DateTime($this->datainicio))->diff(new \DateTime($this->datafim))->days);
    }

    /** O animal chegou ao hotel. */
    public function checkIn(): bool
    {
        return $this->mudarEstado(self::STATUS_EM_CURSO) && $this->save(false, ['status']);
    }

    /** O animal foi para casa: a estadia termina e o chat fica só para leitura. */
    public function checkOut(): bool
    {
        return $this->mudarEstado(self::STATUS_CONCLUIDA) && $this->save(false, ['status']);
    }

    private function mudarEstado(string $estado): bool
    {
        if (!$this->podePassarPara($estado)) {
            $this->addError('status', 'Uma reserva ' . mb_strtolower($this->getStatusLabel()) . ' não pode passar a ' . mb_strtolower(self::statusLabels()[$estado]) . '.');
            return false;
        }
        $this->status = $estado;
        return true;
    }

    /**
     * Seja qual for o código que muda o estado para "confirmada", abre-se a conversa.
     * Responsável: quem confirmou (confirmar()), ou o colaborador autenticado; sem nenhum dos dois,
     * a conversa fica sem responsável e um admin atribui-a no back-office.
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $mudouEstado = !$insert && array_key_exists('status', $changedAttributes)
            && $changedAttributes['status'] !== $this->status;

        if (($insert || $mudouEstado) && $this->status === self::STATUS_CONFIRMADA
            && !Conversa::find()->where(['id_booking' => $this->id])->exists()) {
            $responsavel = $this->confirmadaPor;
            if ($responsavel === null && Yii::$app instanceof \yii\web\Application && !Yii::$app->user->isGuest
                && Conversa::podeSerResponsavel((int) Yii::$app->user->id)) {
                $responsavel = (int) Yii::$app->user->id;
            }
            Conversa::abrir($this, $responsavel);
        }

        // o cliente é avisado de cada passo da reserva (sino no site e na app)
        if ($mudouEstado) {
            unset($this->conversa);
            Notificacao::mudancaReserva($this);
        }
        $this->confirmadaPor = null;
    }

    /** Fotografias e vídeos do chat a apagar do disco depois de a reserva sair da base de dados. */
    private array $anexosParaApagar = [];

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        // a conversa e as mensagens saem em cascata; os ficheiros e as notificações têm de ser apagados à mão
        $idsConversa = Conversa::find()->select('id')->where(['id_booking' => $this->id])->column();
        $this->anexosParaApagar = $idsConversa === [] ? [] : Mensagem::find()
            ->select('anexo')
            ->where(['id_conversa' => $idsConversa])
            ->andWhere(['not', ['anexo' => null]])
            ->column();
        if ($idsConversa !== []) {
            Notificacao::deleteAll(['tipo' => Notificacao::TIPO_MENSAGEM, 'ref_id' => $idsConversa]);
        }
        Notificacao::deleteAll(['tipo' => Notificacao::TIPO_RESERVA, 'ref_id' => $this->id]);
        return true;
    }

    public function afterDelete()
    {
        parent::afterDelete();
        foreach ($this->anexosParaApagar as $nome) {
            ChatStorage::delete($nome);
        }
    }
}
