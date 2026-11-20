<?php

use yii\db\Migration;

/**
 * Insere as raças base, apenas as que ainda não existem.
 */
class m261120_142951_create_raca_table extends Migration
{
    private function rows(): array
    {
        return [
            ['Caniche (Poodle)', 'Pelo denso, encaracolado e lanoso, em cores sólidas.', 'Cão'],
            ['Chihuahua', 'Minúsculo, cabeça arredondada em forma de maçã, olhos grandes.', 'Cão'],
            ['Dachshund (Salsicha)', 'Corpo muito alongado, pernas curtas, orelhas caídas.', 'Cão'],
            ['Beagle', 'Tricolor típico (branco, castanho e preto), orelhas caídas.', 'Cão'],
            ['Husky Siberiano', 'Pelo duplo e denso, máscara facial característica, olhos claros.', 'Cão'],
            ['Borzoi (Galgo Russo)', 'Corpo esguio e elegante, com peito fundo e estreito.', 'Cão'],
            ['Siamês', 'Corpo esguio e elegante, cabeça em cunha, olhos azuis.', 'Gato'],
            ['British Shorthair', 'Corpo robusto e musculado, cabeça redonda, bochechas cheias.', 'Gato'],
            ['Bengal', 'Corpo musculado e atlético, pelo curto e brilhante com padrão de leopardo.', 'Gato'],
            ['Abissínio', 'Corpo esguio, orelhas grandes e olhos amendoados.', 'Gato'],
            ['Sphynx', 'Praticamente sem pelo, com pele enrugada, orelhas grandes.', 'Gato'],
            ['Azul Russo', 'Corpo fino e elegante, pelo curto, denso e sedoso, azul-acinzentado.', 'Gato'],
            ['Maine Coon', 'Gigante e robusto, pelo longo e sedoso, mais comprido no peito e na cauda.', 'Gato'],
            ['Ragdoll', 'Grande e de corpo comprido, pelo semi-longo e sedoso, muito dócil.', 'Gato'],
            ['Gato da Floresta Norueguesa', 'Corpo grande e musculado, pelo duplo e impermeável.', 'Gato'],
            ['Birmanês (Sagrado da Birmânia)', 'Pelo semi-longo e sedoso, extremidades escuras, olhos azuis.', 'Gato'],
            ['Persa', 'Pelo muito longo, denso e sedoso, cara redonda e achatada.', 'Gato'],
            ['Angorá Turco', 'Corpo esguio e elegante, pelo longo e fino, normalmente branco.', 'Gato'],
            ['Scottish Fold', 'Orelhas pequenas dobradas para a frente e para baixo.', 'Gato'],
            ['Manx', 'Sem cauda ou com cauda muito curta, corpo arredondado.', 'Gato'],
            ['Gato Europeu Comum', 'Corpo médio e equilibrado, pelo curto em muitas cores.', 'Gato'],
        ];
    }

    public function safeUp()
    {
        $existing = (new \yii\db\Query())
            ->select('nome')
            ->from('{{%raca}}')
            ->column();

        $rows = array_values(array_filter(
            $this->rows(),
            fn($r) => !in_array($r[0], $existing, true)
        ));

        if ($rows) {
            $this->batchInsert('{{%raca}}', ['nome', 'descricao', 'tipo'], $rows);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%raca}}', ['nome' => array_column($this->rows(), 0)]);
    }
}