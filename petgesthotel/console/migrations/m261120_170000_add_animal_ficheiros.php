<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Fotografia do animal (animal.foto) e documentos de vacinas (tabela animal_documento: PDF ou imagem).
 * Só se guarda o nome do ficheiro; os ficheiros ficam na pasta "animal.path" (ver common/config/params.php).
 */
class m261120_170000_add_animal_ficheiros extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%animal}}', true)?->getColumn('foto') === null) {
            $this->addColumn('{{%animal}}', 'foto', $this->string(64)->null()->after('observacoes'));
        }

        $this->createTable('{{%animal_documento}}', [
            'id' => $this->primaryKey(),
            'id_animal' => $this->integer()->notNull(),
            'ficheiro' => $this->string(64)->notNull(),
            'nome_original' => $this->string(255)->notNull(),
            'mime' => $this->string(64)->notNull(),
            'tamanho' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-animal_documento-id_animal', '{{%animal_documento}}', 'id_animal');
        $this->addForeignKey(
            'fk-animal_documento-id_animal',
            '{{%animal_documento}}', 'id_animal',
            '{{%animal}}', 'id',
            'CASCADE', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%animal_documento}}');
        $this->dropColumn('{{%animal}}', 'foto');
    }
}
