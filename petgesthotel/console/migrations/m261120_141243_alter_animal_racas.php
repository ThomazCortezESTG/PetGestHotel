<?php

use yii\db\Migration;

class m261120_141243_alter_animal_racas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Remover FK e índice antigos de id_raca
        $this->dropForeignKey('fk-animal-id_raca', '{{%animal}}');
        $this->dropIndex('idx-animal-id_raca', '{{%animal}}');

        // 2. Renomear id_raca -> id_raca_principal
        $this->renameColumn('{{%animal}}', 'id_raca', 'id_raca_principal');

        // 3. Criar id_raca_secundaria (opcional)
        $this->addColumn(
            '{{%animal}}',
            'id_raca_secundaria',
            $this->integer()->null()->after('id_raca_principal')
        );

        // 4. Índices
        $this->createIndex('idx-animal-id_raca_principal', '{{%animal}}', 'id_raca_principal');
        $this->createIndex('idx-animal-id_raca_secundaria', '{{%animal}}', 'id_raca_secundaria');

        // 5. Chaves estrangeiras
        $this->addForeignKey(
            'fk-animal-id_raca_principal',
            '{{%animal}}', 'id_raca_principal',
            '{{%raca}}', 'id',
            'RESTRICT', 'CASCADE'
        );

        $this->addForeignKey(
            'fk-animal-id_raca_secundaria',
            '{{%animal}}', 'id_raca_secundaria',
            '{{%raca}}', 'id',
            'SET NULL', 'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Desfazer pela ordem inversa
        $this->dropForeignKey('fk-animal-id_raca_secundaria', '{{%animal}}');
        $this->dropForeignKey('fk-animal-id_raca_principal', '{{%animal}}');

        $this->dropIndex('idx-animal-id_raca_secundaria', '{{%animal}}');
        $this->dropIndex('idx-animal-id_raca_principal', '{{%animal}}');

        $this->dropColumn('{{%animal}}', 'id_raca_secundaria');

        $this->renameColumn('{{%animal}}', 'id_raca_principal', 'id_raca');

        $this->createIndex('idx-animal-id_raca', '{{%animal}}', 'id_raca');
        $this->addForeignKey(
            'fk-animal-id_raca',
            '{{%animal}}', 'id_raca',
            '{{%raca}}', 'id',
            'RESTRICT', 'CASCADE'
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261120_141243_alter_animal_racas cannot be reverted.\n";

        return false;
    }
    */
}
