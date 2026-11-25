<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Garante que user_profile tem PRIMARY KEY (user_id).
 * Em algumas bases de dados a tabela foi criada à mão sem chave primária, e o Yii não consegue atualizar linhas.
 */
class m261120_150000_ensure_user_profile_primary_key extends Migration
{
    public function safeUp(): bool
    {
        $schema = $this->db->getTableSchema('{{%user_profile}}', true);
        if ($schema === null) {
            echo "    > tabela user_profile não existe: nada a fazer\n";
            return true;
        }
        if ($schema->primaryKey !== []) {
            echo '    > user_profile já tem chave primária (' . implode(', ', $schema->primaryKey) . "): nada a fazer\n";
            return true;
        }

        // Uma chave primária não aceita repetidos nem NULL: confirmar antes de mexer.
        $duplicados = (new \yii\db\Query())
            ->select(['user_id', 'n' => 'COUNT(*)'])
            ->from('{{%user_profile}}')
            ->groupBy('user_id')
            ->having('COUNT(*) > 1')
            ->all($this->db);
        if ($duplicados !== []) {
            echo "    > ERRO: há perfis repetidos para o mesmo utilizador. Apague os repetidos e volte a correr:\n";
            foreach ($duplicados as $d) {
                echo "      user_id {$d['user_id']}: {$d['n']} linhas\n";
            }
            return false;
        }
        $semUser = (new \yii\db\Query())->from('{{%user_profile}}')->where(['user_id' => null])->count('*', $this->db);
        if ((int) $semUser > 0) {
            echo "    > ERRO: há {$semUser} perfis com user_id vazio. Apague-os e volte a correr.\n";
            return false;
        }

        // O MySQL não deixa mudar uma coluna usada numa chave estrangeira ("error 1832"):
        // tirar as FKs sobre user_id, criar a PRIMARY KEY e voltar a pôr as FKs exatamente como estavam.
        $fks = $this->userIdForeignKeys($schema->fullName ?? $schema->name);
        foreach ($fks as $fk) {
            $this->dropForeignKey($fk['name'], '{{%user_profile}}');
        }

        // a PRIMARY KEY torna a coluna NOT NULL automaticamente (já confirmámos que não há NULLs)
        $this->addPrimaryKey('pk-user_profile', '{{%user_profile}}', 'user_id');

        foreach ($fks as $fk) {
            $this->addForeignKey($fk['name'], '{{%user_profile}}', 'user_id', $fk['refTable'], $fk['refColumn'], $fk['onDelete'], $fk['onUpdate']);
        }
        return true;
    }

    /**
     * Chaves estrangeiras de user_profile.user_id, com as regras ON DELETE / ON UPDATE (MySQL).
     *
     * @return array<int, array{name: string, refTable: string, refColumn: string, onDelete: string, onUpdate: string}>
     */
    private function userIdForeignKeys(string $table): array
    {
        $rows = $this->db->createCommand(
            'SELECT k.CONSTRAINT_NAME AS name, k.REFERENCED_TABLE_NAME AS refTable, k.REFERENCED_COLUMN_NAME AS refColumn,
                    r.DELETE_RULE AS onDelete, r.UPDATE_RULE AS onUpdate
               FROM information_schema.KEY_COLUMN_USAGE k
               JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                 ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
              WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = :t AND k.COLUMN_NAME = :c
                AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            [':t' => $table, ':c' => 'user_id'],
        )->queryAll();

        return array_map(static fn(array $r): array => [
            'name' => (string) $r['name'],
            'refTable' => (string) $r['refTable'],
            'refColumn' => (string) $r['refColumn'],
            'onDelete' => (string) $r['onDelete'],
            'onUpdate' => (string) $r['onUpdate'],
        ], $rows);
    }

    public function safeDown(): bool
    {
        // não removemos a chave: a aplicação precisa dela
        return true;
    }
}
