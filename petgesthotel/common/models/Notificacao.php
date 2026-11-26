<?php

declare(strict_types=1);

namespace common\models;

use common\components\Mqtt;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Notificação para um utilizador (sino no topo do site e lista na app).
 *
 * Tipos:
 * - "mensagem" (ref_id = conversa): mensagens novas do hotel. Enquanto não é lida, as mensagens
 *   seguintes da mesma conversa juntam-se na mesma notificação ("3 mensagens novas de Ana").
 * - "reserva" (ref_id = reserva): a reserva foi confirmada, rejeitada, teve check-in ou check-out.
 *
 * Para acrescentar um tipo: uma constante, um método como novaMensagem() e o destino em url().
 *
 * Tempo real: cada notificação nova ou alterada é avisada no tópico <prefixo>/utilizador/<id>.
 *
 * @property int $id
 * @property int $id_user
 * @property string $tipo
 * @property int|null $ref_id
 * @property string $titulo
 * @property string $texto
 * @property int $quantidade
 * @property bool $lida
 * @property int $created_at
 * @property int $updated_at
 */
class Notificacao extends ActiveRecord
{
    public const TIPO_MENSAGEM = 'mensagem';
    public const TIPO_RESERVA = 'reserva';

    public static function tableName(): string
    {
        return '{{%notificacao}}';
    }

    /**
     * Avisa o cliente de que a reserva mudou de estado (chamado em Booking::afterSave).
     * Estados sem aviso (ex.: cancelada, que é o próprio cliente a fazer) não criam nada.
     */
    public static function mudancaReserva(Booking $booking): ?self
    {
        $fmt = \Yii::$app->formatter;
        $nomes = array_map(static fn (Animal $a) => $a->nome, $booking->animais);
        $animais = $nomes === [] ? 'o seu animal' : implode(', ', $nomes);
        $datas = $fmt->asDate($booking->datainicio, 'd MMM') . ' a ' . $fmt->asDate($booking->datafim, 'd MMM');

        switch ($booking->status) {
            case Booking::STATUS_CONFIRMADA:
                $resp = $booking->conversa?->colaborador;
                $titulo = 'Reserva confirmada';
                $texto = 'A estadia de ' . $animais . ' (' . $datas . ') está confirmada.'
                    . ($resp ? ' Quem vai acompanhar é ' . Conversa::pessoa($resp)['nome'] . ': pode falar diretamente em Mensagens.' : '');
                break;
            case Booking::STATUS_REJEITADA:
                $titulo = 'Reserva não aceite';
                $texto = 'A reserva de ' . $datas . ' não foi aceite'
                    . ($booking->motivo_rejeicao ? ': ' . $booking->motivo_rejeicao : '.');
                break;
            case Booking::STATUS_EM_CURSO:
                $titulo = 'Check-in feito';
                $texto = ucfirst($animais) . (count($nomes) > 1 ? ' já estão' : ' já está') . ' no hotel. Pode acompanhar a estadia em Mensagens.';
                break;
            case Booking::STATUS_CONCLUIDA:
                $titulo = 'Estadia terminada';
                $texto = 'O check-out de ' . $animais . ' está feito. Obrigado pela confiança!';
                break;
            default:
                return null;
        }

        $n = new self([
            'id_user' => $booking->id_user,
            'tipo' => self::TIPO_RESERVA,
            'ref_id' => $booking->id,
            'titulo' => $titulo,
            'texto' => mb_strimwidth($texto, 0, 500, '…'),
            'quantidade' => 1,
            'lida' => false,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $n->save(false);
        Mqtt::publicar(self::topico((int) $booking->id_user), ['tipo' => 'notificacao', 'notificacao' => $n->id]);
        return $n;
    }

    public static function topico(int $idUser): string
    {
        return Mqtt::prefixo() . '/utilizador/' . $idUser;
    }

    public static function novaMensagem(Conversa $conversa, Mensagem $mensagem): self
    {
        $idCliente = (int) $conversa->booking->id_user;
        $autor = $mensagem->id_autor !== null ? Conversa::pessoa($mensagem->autor)['nome'] : 'PetGestHotel';

        $n = self::find()->where([
            'id_user' => $idCliente,
            'tipo' => self::TIPO_MENSAGEM,
            'ref_id' => $conversa->id,
            'lida' => false,
        ])->one();

        if ($n === null) {
            $n = new self([
                'id_user' => $idCliente,
                'tipo' => self::TIPO_MENSAGEM,
                'ref_id' => $conversa->id,
                'quantidade' => 1,
                'lida' => false,
                'created_at' => $mensagem->created_at,
            ]);
        } else {
            $n->quantidade++;
        }
        $n->titulo = $n->quantidade === 1 ? 'Nova mensagem de ' . $autor : $n->quantidade . ' mensagens novas de ' . $autor;
        $n->texto = mb_strimwidth($mensagem->resumo(), 0, 200, '…');
        $n->updated_at = $mensagem->created_at;
        $n->save(false);

        Mqtt::publicar(self::topico($idCliente), ['tipo' => 'notificacao', 'notificacao' => $n->id]);
        return $n;
    }

    /**
     * Marca como lidas as notificações de um utilizador (todas, de um tipo, ou de um tipo e referência).
     */
    public static function marcarLidas(int $idUser, ?string $tipo = null, ?int $refId = null): int
    {
        $where = ['id_user' => $idUser, 'lida' => false];
        if ($tipo !== null) {
            $where['tipo'] = $tipo;
        }
        if ($refId !== null) {
            $where['ref_id'] = $refId;
        }
        $n = self::updateAll(['lida' => true], $where);
        if ($n > 0) {
            Mqtt::publicar(self::topico($idUser), ['tipo' => 'lidas']);
        }
        return $n;
    }

    public static function naoLidas(int $idUser): int
    {
        return (int) self::find()->where(['id_user' => $idUser, 'lida' => false])->count();
    }

    /**
     * @return self[] as mais recentes primeiro
     */
    public static function recentes(int $idUser, int $limite = 15): array
    {
        return self::find()
            ->where(['id_user' => $idUser])
            ->orderBy(['lida' => SORT_ASC, 'updated_at' => SORT_DESC])
            ->limit($limite)
            ->all();
    }

    /** Página do site para onde a notificação leva. */
    public function url(): ?string
    {
        return match ($this->tipo) {
            self::TIPO_MENSAGEM => Url::to(['/conversa/view', 'id' => $this->ref_id]),
            self::TIPO_RESERVA => Url::to(['/booking/view', 'id' => $this->ref_id]),
            default => null,
        };
    }

    /** Formato para o site (sino) e para a app. */
    public function dados(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'ref_id' => $this->ref_id,
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'quantidade' => $this->quantidade,
            'lida' => (bool) $this->lida,
            'created_at' => date('c', $this->created_at),
            'updated_at' => date('c', $this->updated_at),
        ];
    }
}
