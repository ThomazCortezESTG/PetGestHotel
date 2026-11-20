<?php

use yii\db\Migration;

/**
 * Corrige as chaves estrangeiras de id_user em "animal" e "booking".
 *
 * No SQL importado à mão estas chaves apontavam para uma tabela vazia "m" (em vez de "user"),
 * por isso não era possível registar um animal nem uma reserva. Os nomes das chaves variam entre
 * máquinas, por isso são procurados na base de dados. No fim, a tabela "m" é apagada se estiver
 * vazia e já nenhuma chave apontar para ela.
 */
class m261120_160000_fix_user_foreign_keys extends Migration
{
    private const TABELAS = ['animal', 'booking'];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $user = $this->db->getTableSchema('{{%user}}', true)->name;

        foreach (self::TABELAS as $tabela) {
            $schema = $this->db->getTableSchema("{{%$tabela}}", true);
            if ($schema === null || $schema->getColumn('id_user') === null) {
                echo "    > $tabela sem id_user: nada a fazer\n";
                continue;
            }

            // Já aponta para user: nada a fazer
            $chaves = $this->foreignKeysOn($schema->name, 'id_user');
            if (in_array($user, $chaves, true) && count($chaves) === 1) {
                echo "    > $tabela.id_user já aponta para $user\n";
                continue;
            }

            // Linhas cujo dono não existe impediriam a nova chave: avisar em vez de apagar dados
            $orfas = (int) $this->db->createCommand(
                "SELECT COUNT(*) FROM {{%$tabela}} t LEFT JOIN {{%user}} u ON u.id = t.id_user WHERE u.id IS NULL"
            )->queryScalar();
            if ($orfas > 0) {
                echo "    > $tabela tem $orfas linha(s) com id_user que não existe em $user. Corrija-as e volte a correr.\n";
                return false;
            }

            foreach ($chaves as $nome => $referencia) {
                if ($referencia !== $user) {
                    $this->dropForeignKey($nome, "{{%$tabela}}");
                }
            }
            if (!in_array($user, $chaves, true)) {
                $this->addForeignKey(
                    "fk-$tabela-id_user",
                    "{{%$tabela}}", 'id_user',
                    '{{%user}}', 'id',
                    'CASCADE', 'CASCADE'
                );
            }
        }

        $this->dropTabelaMSeSobrar();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Não volta a criar as chaves para "m": eram um erro. Só remove as chaves criadas aqui.
        foreach (self::TABELAS as $tabela) {
            $schema = $this->db->getTableSchema("{{%$tabela}}", true);
            if ($schema !== null && isset($schema->foreignKeys["fk-$tabela-id_user"])) {
                $this->dropForeignKey("fk-$tabela-id_user", "{{%$tabela}}");
            }
        }
    }

    /**
     * Chaves estrangeiras sobre uma coluna (MySQL), no formato [nome => tabela referenciada].
     *
     * @return array<string, string>
     */
    private function foreignKeysOn(string $table, string $column): array
    {
        $linhas = $this->db->createCommand(
            'SELECT DISTINCT CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
                AND REFERENCED_TABLE_NAME IS NOT NULL',
            [':t' => $table, ':c' => $column],
        )->queryAll();

        return array_column($linhas, 'REFERENCED_TABLE_NAME', 'CONSTRAINT_NAME');
    }

    /** Apaga a tabela "m" se existir, estiver vazia e nenhuma chave apontar para ela. */
    private function dropTabelaMSeSobrar(): void
    {
        $m = $this->db->getTableSchema('{{%m}}', true);
        if ($m === null) {
            return;
        }
        $usada = (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = :m',
            [':m' => $m->name],
        )->queryScalar();
        $linhas = (int) $this->db->createCommand('SELECT COUNT(*) FROM {{%m}}')->queryScalar();

        if ($usada === 0 && $linhas === 0) {
            $this->dropTable('{{%m}}');
        } else {
            echo "    > tabela m mantida (tem dados ou ainda é usada)\n";
        }
    }
}
