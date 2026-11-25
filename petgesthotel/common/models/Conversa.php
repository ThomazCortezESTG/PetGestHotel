<?php

declare(strict_types=1);

namespace common\models;

use common\components\ChatStorage;
use common\components\Mqtt;
use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Conversa entre o cliente e o colaborador responsável por uma reserva (uma por reserva).
 *
 * Regras:
 * - Abre quando a reserva é confirmada (Booking::afterSave) e fica com quem a confirmou como responsável.
 * - Do lado do hotel, só o responsável escreve. Um admin (permissão assignChat) vê todas e pode
 *   passar a conversa a outro colaborador; o cliente vê essa troca numa mensagem de sistema.
 * - Escreve-se enquanto a reserva está confirmada ou em curso; depois fica só para leitura.
 *
 * @property int $id
 * @property int $id_booking
 * @property int|null $id_colaborador responsável
 * @property int $cliente_leu_ate id da última mensagem vista pelo cliente
 * @property int $colaborador_leu_ate id da última mensagem vista pelo responsável
 * @property int $created_at
 * @property int $updated_at data da última mensagem
 *
 * @property Booking $booking
 * @property User|null $colaborador
 * @property Mensagem[] $mensagens
 * @property Mensagem|null $ultimaMensagem
 */
class Conversa extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%conversa}}';
    }

    public function getBooking()
    {
        return $this->hasOne(Booking::class, ['id' => 'id_booking']);
    }

    public function getColaborador()
    {
        return $this->hasOne(User::class, ['id' => 'id_colaborador']);
    }

    public function getMensagens()
    {
        return $this->hasMany(Mensagem::class, ['id_conversa' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getUltimaMensagem()
    {
        return $this->hasOne(Mensagem::class, ['id_conversa' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    // ------------------------------------------------------------------ criar e reatribuir

    /**
     * Abre a conversa de uma reserva acabada de confirmar.
     */
    public static function abrir(Booking $booking, ?int $idResponsavel): self
    {
        $conversa = new self([
            'id_booking' => $booking->id,
            'id_colaborador' => $idResponsavel,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $conversa->save(false);

        $nomes = array_map(static fn (Animal $a) => $a->nome, $booking->animais);
        $deQuem = $nomes === [] ? 'do seu animal' : 'de ' . self::juntarNomes($nomes);
        if ($conversa->colaborador !== null) {
            $texto = 'Reserva confirmada! ' . self::pessoa($conversa->colaborador)['nome']
                . ' vai acompanhar a estadia ' . $deQuem . ' e responder às suas mensagens.';
        } else {
            $texto = 'Reserva confirmada! Em breve um colaborador vai acompanhar a estadia ' . $deQuem . '.';
        }
        $conversa->mensagemSistema($texto);

        return $conversa;
    }

    /**
     * Passa a conversa a outro colaborador (só admins, ver permissão assignChat).
     */
    public function reatribuir(int $idColaborador): bool
    {
        if (!self::podeSerResponsavel($idColaborador)) {
            $this->addError('id_colaborador', 'Escolha um colaborador.');
            return false;
        }
        if ((int) $this->id_colaborador === $idColaborador) {
            return true;
        }

        $this->id_colaborador = $idColaborador;
        // as mensagens antigas não contam como "novas" para quem chega
        $this->colaborador_leu_ate = (int) Mensagem::find()->where(['id_conversa' => $this->id])->max('id');
        $this->save(false, ['id_colaborador', 'colaborador_leu_ate']);
        unset($this->colaborador);

        $this->mensagemSistema('A partir de agora, a sua conversa é acompanhada por ' . self::pessoa($this->colaborador)['nome'] . '.');
        return true;
    }

    // ------------------------------------------------------------------ permissões

    public static function podeSerResponsavel(int $idUser): bool
    {
        $auth = Yii::$app->authManager;
        return $auth->checkAccess($idUser, 'colaborador') && User::find()->where(['id' => $idUser, 'status' => User::STATUS_ACTIVE])->exists();
    }

    /**
     * Colaboradores ativos (inclui admins), para a lista de reatribuição: [id => nome].
     */
    public static function colaboradores(): array
    {
        $auth = Yii::$app->authManager;
        $ids = array_unique(array_merge($auth->getUserIdsByRole('colaborador'), $auth->getUserIdsByRole('admin')));
        $lista = [];
        foreach (User::find()->with('userProfile')->where(['id' => $ids, 'status' => User::STATUS_ACTIVE])->all() as $u) {
            $lista[$u->id] = self::pessoa($u)['nome'];
        }
        asort($lista);
        return $lista;
    }

    public function eCliente(int $idUser): bool
    {
        return (int) $this->booking->id_user === $idUser;
    }

    public function eResponsavel(int $idUser): bool
    {
        return $this->id_colaborador !== null && (int) $this->id_colaborador === $idUser;
    }

    public function podeVer(int $idUser): bool
    {
        return $this->eCliente($idUser) || $this->eResponsavel($idUser)
            || Yii::$app->authManager->checkAccess($idUser, 'assignChat');
    }

    /** A reserva ainda está ativa (confirmada ou com o animal no hotel). */
    public function estaAberta(): bool
    {
        return in_array($this->booking->status, [Booking::STATUS_CONFIRMADA, Booking::STATUS_EM_CURSO], true);
    }

    public function podeEscrever(int $idUser): bool
    {
        return $this->estaAberta() && ($this->eCliente($idUser) || $this->eResponsavel($idUser));
    }

    /**
     * Porque é que esta pessoa não pode escrever (para mostrar no lugar da caixa de texto), ou null se pode.
     */
    public function motivoSemEscrita(int $idUser): ?string
    {
        if ($this->podeEscrever($idUser)) {
            return null;
        }
        if (!$this->estaAberta()) {
            return 'A reserva está ' . mb_strtolower($this->booking->getStatusLabel()) . ', por isso a conversa já só pode ser lida.';
        }
        if ($this->id_colaborador === null) {
            return 'Esta conversa ainda não tem colaborador responsável. Atribua-a a alguém para poder responder.';
        }
        return 'Só ' . self::pessoa($this->colaborador)['nome'] . ', responsável por esta reserva, pode responder.';
    }

    // ------------------------------------------------------------------ mensagens

    /**
     * Fotografias e vídeos: só o responsável (permissão uploadMedia), para mostrar o animal ao cliente.
     */
    public function podeEnviarAnexo(int $idUser): bool
    {
        return $this->podeEscrever($idUser) && $this->eResponsavel($idUser)
            && Yii::$app->authManager->checkAccess($idUser, 'uploadMedia');
    }

    /**
     * Guarda uma mensagem escrita por $idUser (o cliente ou o responsável) e avisa pelo MQTT.
     * $anexo: fotografia ou vídeo (só o responsável); o texto passa a ser a legenda e pode ficar vazio.
     * Devolve a mensagem, com erros de validação (no campo "texto") se não ficou guardada.
     */
    public function enviar(int $idUser, string $texto, ?UploadedFile $anexo = null): Mensagem
    {
        $mensagem = new Mensagem([
            'id_conversa' => $this->id,
            'id_autor' => $idUser,
            'tipo' => Mensagem::TIPO_TEXTO,
            'texto' => $texto,
            'created_at' => time(),
        ]);
        if (!$this->podeEscrever($idUser)) {
            $mensagem->addError('texto', $this->motivoSemEscrita($idUser));
            return $mensagem;
        }
        if ($anexo !== null && !$this->podeEnviarAnexo($idUser)) {
            $mensagem->addError('texto', 'Só o colaborador responsável pode enviar fotografias e vídeos.');
            return $mensagem;
        }
        if ($anexo !== null) {
            try {
                [$mensagem->anexo, $mensagem->anexo_mime, $mensagem->anexo_tamanho] = ChatStorage::save($anexo);
            } catch (\RuntimeException $e) {
                $mensagem->addError('texto', $e->getMessage());
                return $mensagem;
            }
        }
        if (!$mensagem->save()) {
            ChatStorage::delete($mensagem->anexo);
            return $mensagem;
        }

        $this->depoisDeNovaMensagem($mensagem);
        // quem escreve já viu tudo até aqui
        $this->marcarLida($idUser);
        return $mensagem;
    }

    public function mensagemSistema(string $texto): Mensagem
    {
        $mensagem = new Mensagem([
            'id_conversa' => $this->id,
            'id_autor' => null,
            'tipo' => Mensagem::TIPO_SISTEMA,
            'texto' => $texto,
            'created_at' => time(),
        ]);
        $mensagem->save(false);
        $this->depoisDeNovaMensagem($mensagem);
        return $mensagem;
    }

    private function depoisDeNovaMensagem(Mensagem $mensagem): void
    {
        $this->updated_at = $mensagem->created_at;
        $this->save(false, ['updated_at']);
        Mqtt::publicar(Mqtt::topicoConversa($this->id), [
            'tipo' => 'mensagem',
            'conversa' => $this->id,
            'mensagem' => $mensagem->id,
        ]);

        // o cliente é avisado de tudo o que não foi ele a escrever (o responsável vê o contador no back-office)
        $idCliente = (int) $this->booking->id_user;
        if ($mensagem->id_autor === null || (int) $mensagem->id_autor !== $idCliente) {
            Notificacao::novaMensagem($this, $mensagem);
        }
    }

    /**
     * Mensagens depois de um id, já no formato do browser/app: o que os endpoints "mensagens" devolvem.
     */
    public function mensagensDados(int $idLeitor, int $depoisDe = 0): array
    {
        $idCliente = (int) $this->booking->id_user;
        return array_map(
            static fn (Mensagem $m) => $m->dados($idLeitor, $idCliente),
            $this->mensagensDepois($depoisDe)
        );
    }

    /**
     * Mensagens a partir de um id (exclusive), da mais antiga para a mais recente.
     *
     * @return Mensagem[]
     */
    public function mensagensDepois(int $depoisDe = 0, int $limite = 500): array
    {
        return Mensagem::find()
            ->with(['autor.userProfile'])
            ->where(['id_conversa' => $this->id])
            ->andWhere(['>', 'id', $depoisDe])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limite)
            ->all();
    }

    public function marcarLida(int $idUser): void
    {
        $ultima = (int) Mensagem::find()->where(['id_conversa' => $this->id])->max('id');
        if ($this->eCliente($idUser)) {
            // abrir a conversa conta como ter visto a notificação dela
            Notificacao::marcarLidas($idUser, Notificacao::TIPO_MENSAGEM, $this->id);
        }
        if ($this->eCliente($idUser) && $this->cliente_leu_ate < $ultima) {
            $this->cliente_leu_ate = $ultima;
            $this->save(false, ['cliente_leu_ate']);
        } elseif ($this->eResponsavel($idUser) && $this->colaborador_leu_ate < $ultima) {
            $this->colaborador_leu_ate = $ultima;
            $this->save(false, ['colaborador_leu_ate']);
        }
    }

    /**
     * Mensagens que esta pessoa ainda não viu. Para o cliente: as do hotel e as de sistema
     * (são para ele). Para o responsável: só as do cliente.
     */
    public function naoLidas(int $idUser): int
    {
        $query = Mensagem::find()->where(['id_conversa' => $this->id]);
        if ($this->eCliente($idUser)) {
            $query->andWhere(['>', 'id', $this->cliente_leu_ate])
                ->andWhere(['or', ['id_autor' => null], ['<>', 'id_autor', $idUser]]);
        } elseif ($this->eResponsavel($idUser)) {
            $query->andWhere(['>', 'id', $this->colaborador_leu_ate])
                ->andWhere(['id_autor' => $this->booking->id_user]);
        } else {
            return 0;
        }

        return (int) $query->count();
    }

    // ------------------------------------------------------------------ dados para o browser e para a app

    /**
     * Resumo da conversa visto por $idUser (lista de conversas, cabeçalho do chat, API).
     */
    public function dados(int $idUser): array
    {
        $booking = $this->booking;
        $ultima = $this->ultimaMensagem;

        return [
            'id' => $this->id,
            'reserva' => [
                'id' => $booking->id,
                'entrada' => $booking->datainicio,
                'saida' => $booking->datafim,
                'estado' => $booking->status,
                'estado_texto' => $booking->getStatusLabel(),
                'animais' => array_map(static fn (Animal $a) => ['id' => $a->id, 'nome' => $a->nome], $booking->animais),
            ],
            'cliente' => ['id' => $booking->user->id] + self::pessoa($booking->user),
            'responsavel' => $this->colaborador ? ['id' => $this->colaborador->id] + self::pessoa($this->colaborador) : null,
            'pode_escrever' => $this->podeEscrever($idUser),
            'pode_enviar_anexo' => $this->podeEnviarAnexo($idUser),
            'motivo_sem_escrita' => $this->motivoSemEscrita($idUser),
            'nao_lidas' => $this->naoLidas($idUser),
            'ultima_mensagem' => $ultima ? [
                'texto' => $ultima->resumo(),
                'tipo' => $ultima->tipo,
                'created_at' => date('c', $ultima->created_at),
                'minha' => $ultima->id_autor !== null && (int) $ultima->id_autor === $idUser,
            ] : null,
            'mqtt_topic' => Mqtt::topicoConversa($this->id),
        ];
    }

    /**
     * Nome, iniciais e fotografia (URL absoluto, ou null) de uma pessoa.
     */
    public static function pessoa(?User $user): array
    {
        if ($user === null) {
            return ['nome' => 'Utilizador removido', 'iniciais' => '?', 'foto_url' => null];
        }
        $nome = trim((string) ($user->userProfile->nome ?? '')) ?: $user->username;
        $foto = $user->userProfile?->getAvatarUrl();
        if ($foto !== null && Yii::$app instanceof \yii\web\Application && !str_starts_with($foto, 'http')) {
            $foto = Yii::$app->request->hostInfo . $foto;
        }

        return ['nome' => $nome, 'iniciais' => UserProfile::initialsOf($nome), 'foto_url' => $foto];
    }

    private static function juntarNomes(array $nomes): string
    {
        if (count($nomes) === 1) {
            return $nomes[0];
        }
        $ultimo = array_pop($nomes);
        return implode(', ', $nomes) . ' e ' . $ultimo;
    }
}
