<?php

declare(strict_types=1);

namespace common\models;

use common\components\ChatStorage;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Mensagem de uma conversa. Criadas só através de Conversa::enviar() e Conversa::mensagemSistema().
 *
 * @property int $id
 * @property int $id_conversa
 * @property int|null $id_autor null nas mensagens de sistema
 * @property string $tipo "texto" ou "sistema"
 * @property string $texto pode ficar vazio quando há anexo (é a legenda)
 * @property string|null $anexo fotografia ou vídeo (nome do ficheiro na pasta "chat.path")
 * @property string|null $anexo_mime
 * @property int|null $anexo_tamanho bytes
 * @property int $created_at
 *
 * @property Conversa $conversa
 * @property User|null $autor
 */
class Mensagem extends ActiveRecord
{
    public const TIPO_TEXTO = 'texto';
    public const TIPO_SISTEMA = 'sistema';

    public const MAX_CHARS = 2000;

    public static function tableName(): string
    {
        return '{{%mensagem}}';
    }

    public function rules(): array
    {
        return [
            // texto que não é UTF-8 válido partia o JSON da conversa inteira
            ['texto', 'filter', 'filter' => static fn ($v) => is_string($v) && !mb_check_encoding($v, 'UTF-8') ? mb_convert_encoding($v, 'UTF-8', 'Windows-1252') : $v],
            ['texto', 'trim'],
            ['texto', 'default', 'value' => ''],
            ['texto', 'required', 'message' => 'Escreva uma mensagem.', 'when' => fn () => $this->anexo === null],
            ['texto', 'string', 'max' => self::MAX_CHARS, 'tooLong' => 'A mensagem pode ter no máximo {max} caracteres.'],
        ];
    }

    public function getConversa()
    {
        return $this->hasOne(Conversa::class, ['id' => 'id_conversa']);
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'id_autor']);
    }

    public function eVideo(): bool
    {
        return ChatStorage::isVideoMime($this->anexo_mime);
    }

    /**
     * Texto curto para listas e notificações ("📷 Fotografia: legenda").
     */
    public function resumo(): string
    {
        if ($this->anexo === null) {
            return $this->texto;
        }
        $tipo = $this->eVideo() ? '🎬 Vídeo' : '📷 Fotografia';
        return $this->texto !== '' ? $tipo . ': ' . $this->texto : $tipo;
    }

    /**
     * Endereço do anexo. Na API leva o token (a app pede-o com o cabeçalho Authorization);
     * no site é servido pelo ConversaController de cada aplicação, com a sessão.
     */
    public function anexoUrl(): ?string
    {
        if ($this->anexo === null) {
            return null;
        }
        $naApi = Yii::$app->controller?->module?->id === 'api';
        return $naApi
            ? Url::to(['/api/conversa/anexo', 'id' => $this->id_conversa, 'mensagem' => $this->id], true)
            : Url::to(['/conversa/anexo', 'id' => $this->id]);
    }

    /**
     * A mensagem como vai para o browser e para a app.
     *
     * @param int $idLeitor quem está a ler ("minha" = foi esta pessoa que escreveu)
     * @param int $idCliente dono da reserva (para saber se o autor é o cliente ou do hotel)
     */
    public function dados(int $idLeitor, int $idCliente): array
    {
        $autor = null;
        if ($this->tipo === self::TIPO_TEXTO && $this->autor !== null) {
            $autor = [
                'id' => $this->autor->id,
                'papel' => (int) $this->autor->id === $idCliente ? 'cliente' : 'colaborador',
            ] + Conversa::pessoa($this->autor);
        }

        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'texto' => $this->texto,
            'anexo' => $this->anexo === null ? null : [
                'tipo' => $this->eVideo() ? 'video' : 'imagem',
                'mime' => $this->anexo_mime,
                'tamanho' => $this->anexo_tamanho,
                'url' => $this->anexoUrl(),
            ],
            'created_at' => date('c', $this->created_at),
            'minha' => $this->id_autor !== null && (int) $this->id_autor === $idLeitor,
            'autor' => $autor,
        ];
    }
}
