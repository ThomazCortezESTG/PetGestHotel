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
 *
 * Para acrescentar um tipo (ex.: reserva confirmada): uma constante, um método como novaMensagem()
 * e o destino em url().
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

    public static function tableName(): string
    {
        return '{{%notificacao}}';
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
