<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%raca}}`.
 */
class m261120_142951_create_raca_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%raca}}', ['id', 'nome', 'descricao', 'tipo'], [
            [1,  'Caniche (Poodle)', 'Pelo denso, encaracolado e lanoso, em cores sólidas.', 'Cão'],
            [2,  'Chihuahua', 'Minúsculo, cabeça arredondada em forma de maçã, olhos grandes.', 'Cão'],
            [3,  'Dachshund (Salsicha)', 'Corpo muito alongado, pernas curtas, orelhas caídas.', 'Cão'],
            [4,  'Beagle', 'Tricolor típico (branco, castanho e preto), orelhas caídas.', 'Cão'],
            [5,  'Husky Siberiano', 'Pelo duplo e denso, máscara facial característica, olhos claros.', 'Cão'],
            [6,  'Borzoi (Galgo Russo)', 'Corpo esguio e elegante, com peito fundo e estreito.', 'Cão'],
            [7,  'Siamês', 'Corpo esguio e elegante, cabeça em cunha, olhos azuis.', 'Gato'],
            [8,  'British Shorthair', 'Corpo robusto e musculado, cabeça redonda, bochechas cheias.', 'Gato'],
            [9,  'Bengal', 'Corpo musculado e atlético, pelo curto e brilhante com padrão de leopardo.', 'Gato'],
            [10, 'Abissínio', 'Corpo esguio, orelhas grandes e olhos amendoados.', 'Gato'],
            [11, 'Sphynx', 'Praticamente sem pelo, com pele enrugada, orelhas grandes.', 'Gato'],
            [12, 'Azul Russo', 'Corpo fino e elegante, pelo curto, denso e sedoso, azul-acinzentado.', 'Gato'],
            [13, 'Maine Coon', 'Gigante e robusto, pelo longo e sedoso, mais comprido no peito e na cauda.', 'Gato'],
            [14, 'Ragdoll', 'Grande e de corpo comprido, pelo semi-longo e sedoso, muito dócil.', 'Gato'],
            [15, 'Gato da Floresta Norueguesa', 'Corpo grande e musculado, pelo duplo e impermeável.', 'Gato'],
            [16, 'Birmanês (Sagrado da Birmânia)', 'Pelo semi-longo e sedoso, extremidades escuras, olhos azuis.', 'Gato'],
            [17, 'Persa', 'Pelo muito longo, denso e sedoso, cara redonda e achatada.', 'Gato'],
            [18, 'Angorá Turco', 'Corpo esguio e elegante, pelo longo e fino, normalmente branco.', 'Gato'],
            [19, 'Scottish Fold', 'Orelhas pequenas dobradas para a frente e para baixo.', 'Gato'],
            [20, 'Manx', 'Sem cauda ou com cauda muito curta, corpo arredondado.', 'Gato'],
            [21, 'Gato Europeu Comum', 'Corpo médio e equilibrado, pelo curto em muitas cores.', 'Gato'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%raca}}', ['id' => range(1, 21)]);
    }
}
