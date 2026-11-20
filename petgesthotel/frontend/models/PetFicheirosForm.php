<?php

declare(strict_types=1);

namespace frontend\models;

use common\components\PetStorage;
use common\models\Animal;
use common\models\AnimalDocumento;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Ficheiros enviados no formulário do animal: fotografia e documentos de vacinas (PDF ou imagem).
 *
 * Uso no PetController:
 *   $ficheiros = new PetFicheirosForm($animal);
 *   $ficheiros->loadFromRequest();
 *   if ($animal->validate() && $ficheiros->validate()) { ...transação: $animal->save(); $ficheiros->apply(); }
 */
class PetFicheirosForm extends Model
{
    /** Lado máximo das imagens guardadas (px). */
    private const FOTO_LADO = 640;
    private const DOC_LADO = 2400;

    public ?UploadedFile $foto = null;
    /** @var UploadedFile[] */
    public array $documentos = [];
    public bool $removerFoto = false;
    /** @var int[] ids de documentos a remover */
    public array $removerDocs = [];

    /** Ficheiros a apagar do disco só depois de a transação ser confirmada. */
    private array $apagarDepois = [];
    /** Ficheiros escritos neste pedido (apagados se algo falhar). */
    private array $escritos = [];

    public function __construct(private Animal $animal, array $config = [])
    {
        parent::__construct($config);
    }

    public function attributeLabels(): array
    {
        return ['foto' => 'Fotografia', 'documentos' => 'Documentos de vacinas'];
    }

    public function loadFromRequest(): void
    {
        $req = Yii::$app->request;
        $this->foto = UploadedFile::getInstanceByName('foto');
        $this->documentos = array_values(array_filter(
            UploadedFile::getInstancesByName('documentos'),
            static fn(UploadedFile $f) => $f->error !== UPLOAD_ERR_NO_FILE
        ));
        $this->removerFoto = (bool) $req->post('remover_foto');
        $this->removerDocs = array_map('intval', (array) $req->post('remover_docs', []));
    }

    /** Documentos que ficam depois de guardar (os atuais menos os marcados para remover). */
    public function documentosQueFicam(): array
    {
        if ($this->animal->isNewRecord) {
            return [];
        }
        return array_values(array_filter(
            $this->animal->documentos,
            fn(AnimalDocumento $d) => !in_array($d->id, $this->removerDocs, true)
        ));
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if ($clearErrors) {
            $this->clearErrors();
        }
        $max = PetStorage::maxBytes();
        $maxTxt = PetStorage::humanSize($max);

        if ($this->foto !== null) {
            $erro = $this->erroUpload($this->foto, $max, $maxTxt);
            if ($erro !== null) {
                $this->addError('foto', 'A fotografia ' . $erro);
            } elseif (!in_array(PetStorage::mimeOf($this->foto->tempName), PetStorage::IMAGE_MIMES, true)) {
                $this->addError('foto', 'Use uma imagem JPG, PNG ou WebP.');
            }
        }

        $total = count($this->documentosQueFicam()) + count($this->documentos);
        if ($total > PetStorage::MAX_DOCS) {
            $this->addError('documentos', 'Pode ter no máximo ' . PetStorage::MAX_DOCS . ' documentos. Remova algum antes de juntar mais.');
        }
        foreach ($this->documentos as $f) {
            $mime = $f->error === UPLOAD_ERR_OK ? PetStorage::mimeOf($f->tempName) : '';
            $erro = $this->erroUpload($f, $max, $maxTxt)
                ?? (in_array($mime, [...PetStorage::IMAGE_MIMES, PetStorage::PDF_MIME], true) ? null : 'Use PDF, JPG, PNG ou WebP.');
            if ($erro !== null) {
                $this->addError('documentos', '«' . $f->name . '»: ' . $erro);
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Grava os ficheiros. Chamar dentro da transação, depois de $animal->save().
     *
     * @throws \RuntimeException se um ficheiro não puder ser gravado (mensagem para o cliente)
     */
    public function apply(): void
    {
        $a = $this->animal;

        // Fotografia
        if ($this->foto !== null) {
            $nome = PetStorage::saveImage($this->foto->tempName, self::FOTO_LADO, true);
            $this->escritos[] = $nome;
            $this->apagarDepois[] = $a->foto;
            $a->foto = $nome;
            $a->updateAttributes(['foto']);
        } elseif ($this->removerFoto && $a->foto) {
            $this->apagarDepois[] = $a->foto;
            $a->foto = null;
            $a->updateAttributes(['foto']);
        }

        // Documentos a remover (só os deste animal)
        if ($this->removerDocs !== []) {
            $docs = AnimalDocumento::find()->where(['id_animal' => $a->id, 'id' => $this->removerDocs])->all();
            foreach ($docs as $d) {
                $this->apagarDepois[] = $d->ficheiro;
                $d->delete();
            }
        }

        // Documentos novos
        foreach ($this->documentos as $f) {
            $mime = PetStorage::mimeOf($f->tempName);
            $nome = $mime === PetStorage::PDF_MIME
                ? PetStorage::savePdf($f->tempName)
                : PetStorage::saveImage($f->tempName, self::DOC_LADO, false);
            $this->escritos[] = $nome;
            $ext = pathinfo($nome, PATHINFO_EXTENSION);
            $guardado = match ($ext) {
                'pdf' => PetStorage::PDF_MIME,
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
            // o nome mostrado fica com a extensão do ficheiro gravado (uma imagem pode passar de .png a .webp)
            $mostrar = preg_replace('/\.[^.]*$/', '', self::nomeLimpo($f->name)) ?: 'documento';

            $doc = new AnimalDocumento([
                'id_animal' => $a->id,
                'ficheiro' => $nome,
                'nome_original' => mb_substr($mostrar, 0, 250) . '.' . $ext,
                'mime' => $guardado,
                'tamanho' => (int) filesize(PetStorage::path($nome)),
                'created_at' => time(),
            ]);
            if (!$doc->save(false)) {
                throw new \RuntimeException('Não foi possível guardar «' . $f->name . '».');
            }
        }
    }

    /** Depois do commit: apaga os ficheiros substituídos ou removidos. */
    public function afterCommit(): void
    {
        foreach (array_filter($this->apagarDepois) as $nome) {
            PetStorage::delete($nome);
        }
    }

    /** Depois de um rollback: apaga o que foi escrito neste pedido. */
    public function afterRollback(): void
    {
        foreach ($this->escritos as $nome) {
            PetStorage::delete($nome);
        }
        $this->escritos = [];
    }

    private function erroUpload(UploadedFile $f, int $max, string $maxTxt): ?string
    {
        return match (true) {
            in_array($f->error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true), $f->size > $max => 'é maior do que ' . $maxTxt . '.',
            $f->error !== UPLOAD_ERR_OK => 'não chegou inteiro. Tente outra vez.',
            default => null,
        };
    }

    /** Nome só para mostrar: sem caminhos nem caracteres de controlo. */
    private static function nomeLimpo(string $nome): string
    {
        $nome = basename(str_replace('\\', '/', $nome));
        $nome = preg_replace('/[\x00-\x1F\x7F]/u', '', $nome) ?? '';
        return $nome !== '' ? $nome : 'documento';
    }
}
