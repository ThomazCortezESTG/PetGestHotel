<?php

declare(strict_types=1);

namespace common\models;

use common\components\PetStorage;
use yii\db\ActiveRecord;

/**
 * Documento de vacinas de um animal (PDF ou imagem). Só o PetController cria e apaga estes registos.
 *
 * @property int $id
 * @property int $id_animal
 * @property string $ficheiro nome do ficheiro na pasta "animal.path"
 * @property string $nome_original nome que o cliente deu ao ficheiro (só para mostrar)
 * @property string $mime
 * @property int $tamanho bytes
 * @property int $created_at
 *
 * @property Animal $animal
 */
class AnimalDocumento extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%animal_documento}}';
    }

    public function getAnimal()
    {
        return $this->hasOne(Animal::class, ['id' => 'id_animal']);
    }

    public function isPdf(): bool
    {
        return $this->mime === PetStorage::PDF_MIME;
    }
}
