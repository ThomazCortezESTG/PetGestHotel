<?php

use yii\db\Migration;

/**
 * Animal passa a ter raça principal (obrigatória no formulário) e raça secundária (opcional, para cruzamentos).
 *
 * As chaves e índices antigos de id_raca são procurados na base de dados em vez de usar nomes fixos:
 * a tabela "animal" foi criada à mão (SQL importado), e os nomes variam entre máquinas
 * (ex.: "fk-animal-id_raca" numa, "animal_ibfk_2" noutra).
 */
class m261120_141243_alter_animal_racas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%animal}}', true);
        if ($schema === null) {
            echo "    > tabela animal não existe: nada a fazer\n";
            return true;
        }
        $table = $schema->name;

        // Já aplicada noutra forma (ex.: base de dados onde a coluna já foi mudada à mão)
        if ($schema->getColumn('id_raca') === null && $schema->getColumn('id_raca_principal') !== null) {
            echo "    > animal já tem id_raca_principal: nada a fazer\n";
            return true;
        }

        // 1. Remover as chaves estrangeiras e os índices que existirem sobre id_raca (seja qual for o nome)
        foreach ($this->foreignKeysOn($table, 'id_raca') as $fk) {
            $this->dropForeignKey($fk, '{{%animal}}');
        }
        foreach ($this->indexesOn($table, 'id_raca') as $idx) {
            $this->dropIndex($idx, '{{%animal}}');
        }

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

    /**
     * Nomes das chaves estrangeiras sobre uma coluna (MySQL).
     *
     * @return string[]
     */
    private function foreignKeysOn(string $table, string $column): array
    {
        return $this->db->createCommand(
            'SELECT DISTINCT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
                AND REFERENCED_TABLE_NAME IS NOT NULL',
            [':t' => $table, ':c' => $column],
        )->queryColumn();
    }

    /**
     * Nomes dos índices (não a PRIMARY KEY) que usam uma coluna (MySQL).
     *
     * @return string[]
     */
    private function indexesOn(string $table, string $column): array
    {
        return $this->db->createCommand(
            "SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
                AND INDEX_NAME <> 'PRIMARY'",
            [':t' => $table, ':c' => $column],
        )->queryColumn();
    }
}
