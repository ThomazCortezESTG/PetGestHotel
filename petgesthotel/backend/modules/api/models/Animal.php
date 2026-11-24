<?php

declare(strict_types=1);

namespace backend\modules\api\models;

use common\models\AnimalDocumento;
use yii\helpers\Url;

/**
 * O Animal tal como a API o devolve. A lógica fica toda em common\models\Animal;
 * aqui só se escolhe o que vai no JSON.
 *
 * Campos que a app pode enviar (POST/PUT): nome, data, vacinas, observacoes, id_raca_principal, id_raca_secundaria.
 * Campos extra (?expand=...): documentos, racaPrincipal, racaSecundaria.
 */
class Animal extends \common\models\Animal
{
    public function fields(): array
    {
        return [
            'id',
            'nome',
            'data',
            'idade' => fn () => $this->getIdadeTexto(),
            'especie' => fn () => $this->getEspecie(),
            'id_raca_principal',
            'id_raca_secundaria',
            'raca' => fn () => $this->getRacaTexto(),
            'vacinas',
            'observacoes',
            'foto_url' => fn () => $this->foto
                ? Url::to(['/api/animal/foto', 'id' => $this->id, 'v' => substr($this->foto, 0, 8)], true)
                : null,
        ];
    }

    public function extraFields(): array
    {
        return [
            'racaPrincipal',
            'racaSecundaria',
            'documentos' => fn () => array_map([self::class, 'documentoData'], $this->documentos),
        ];
    }

    public static function documentoData(AnimalDocumento $doc): array
    {
        return [
            'id' => $doc->id,
            'nome' => $doc->nome_original,
            'mime' => $doc->mime,
            'tamanho' => $doc->tamanho,
            'created_at' => $doc->created_at,
            'url' => Url::to(['/api/animal/documento', 'id' => $doc->id_animal, 'doc' => $doc->id], true),
        ];
    }
}
