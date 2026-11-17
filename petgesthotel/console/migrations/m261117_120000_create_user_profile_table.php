<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Cria a tabela user_profile (dados pessoais do cliente, 1:1 com user).
 * Usada pelo perfil do cliente no frontend e pela gestão de utilizadores no backend.
 */
class m261117_120000_create_user_profile_table extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%user_profile}}', true) !== null) {
            return; // já criada à mão nalguma BD local
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user_profile}}', [
            'user_id' => $this->integer()->notNull(),
            'nome' => $this->string(255)->null(),
            'data_nascimento' => $this->date()->null(),
            'morada' => $this->string(255)->null(),
            'telefone' => $this->string(9)->null(),
            'n_contribuinte' => $this->string(9)->null(),
            'PRIMARY KEY ([[user_id]])',
        ], $tableOptions);

        $this->addForeignKey(
            'fk-user_profile-user_id',
            '{{%user_profile}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user_profile}}');
    }
}
