<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Chat entre o cliente e o colaborador responsável pela reserva.
 *
 * - conversa: uma por reserva, criada quando a reserva é confirmada (ver Booking::afterSave).
 *   id_colaborador é o responsável: só ele escreve do lado do hotel. Um admin pode reatribuir.
 * - mensagem: tipo "texto" (escrita por alguém) ou "sistema" (ex.: "a conversa passou para o João").
 *
 * Também acrescenta a permissão "assignChat" (reatribuir conversas) ao papel admin.
 */
class m261124_120000_create_chat_tables extends Migration
{
    public function safeUp(): void
    {
        $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%conversa}}', [
            'id' => $this->primaryKey(),
            'id_booking' => $this->integer()->notNull()->unique(),
            'id_colaborador' => $this->integer()->null(),
            // última mensagem que cada lado já viu (para contar as não lidas)
            'cliente_leu_ate' => $this->integer()->notNull()->defaultValue(0),
            'colaborador_leu_ate' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $options);
        $this->addForeignKey('fk-conversa-id_booking', '{{%conversa}}', 'id_booking', '{{%booking}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-conversa-id_colaborador', '{{%conversa}}', 'id_colaborador', '{{%user}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%mensagem}}', [
            'id' => $this->primaryKey(),
            'id_conversa' => $this->integer()->notNull(),
            'id_autor' => $this->integer()->null(),
            'tipo' => $this->string(10)->notNull()->defaultValue('texto'),
            'texto' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('idx-mensagem-conversa-id', '{{%mensagem}}', ['id_conversa', 'id']);
        $this->addForeignKey('fk-mensagem-id_conversa', '{{%mensagem}}', 'id_conversa', '{{%conversa}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-mensagem-id_autor', '{{%mensagem}}', 'id_autor', '{{%user}}', 'id', 'SET NULL', 'CASCADE');

        $auth = Yii::$app->authManager;
        if ($auth->getPermission('assignChat') === null) {
            $assign = $auth->createPermission('assignChat');
            $assign->description = 'Reatribuir conversas a outro colaborador';
            $auth->add($assign);
            $admin = $auth->getRole('admin');
            if ($admin !== null) {
                $auth->addChild($admin, $assign);
            }
        }
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;
        $assign = $auth->getPermission('assignChat');
        if ($assign !== null) {
            $auth->remove($assign);
        }

        $this->dropTable('{{%mensagem}}');
        $this->dropTable('{{%conversa}}');
    }
}
