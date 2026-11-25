<?php

use yii\db\Migration;

class m261124_214022_insert_servicos_extras_default extends Migration
{
    /**
     * {@inheritdoc}
     */

    private $nomes = [
        'Banho',
        'Tosquia',
        'Passeio',
        'Treino básico',
        'Escovagem de dentes',
        'Corte de unhas',
        'Consulta veterinária',
        'Sessão de brincadeira',
    ];
    public function safeUp()
    {
        $this->batchInsert('{{%servicos_extras}}', ['nome', 'descricao', 'preco'], [
            ['Banho',                 'Banho completo com shampoo adequado ao tipo de pelo.',        15.00],
            ['Tosquia',               'Corte e arranjo do pelo, de acordo com a raça.',              25.00],
            ['Passeio',               'Passeio diário de 30 minutos acompanhado por um tratador.',   8.00],
            ['Treino básico',         'Sessão de treino de obediência básica (sentar, ficar, vir).', 20.00],
            ['Escovagem de dentes',   'Limpeza e escovagem dentária.',                               10.00],
            ['Corte de unhas',        'Corte e lima das unhas.',                                     7.00],
            ['Consulta veterinária',  'Avaliação de saúde por um veterinário durante a estadia.',    35.00],
            ['Sessão de brincadeira', 'Sessão de brincadeira e socialização com outros animais.',    10.00],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%servicos_extras}}', ['nome' => $this->nomes]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261124_214022_insert_servicos_extras_default cannot be reverted.\n";

        return false;
    }
    */
}
