<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * - Anexos no chat: o colaborador pode enviar uma fotografia ou um vídeo numa mensagem
 *   (o texto passa a ser opcional nesse caso, como legenda).
 * - Notificações do cliente (sino no topo do site e na app). Para já só "mensagem";
 *   quando houver reservas, juntam-se outros tipos (reserva confirmada, check-in...).
 */
class m261124_160000_chat_anexos_e_notificacoes extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%mensagem}}', 'anexo', $this->string(64)->null()->comment('nome do ficheiro na pasta "chat.path"'));
        $this->addColumn('{{%mensagem}}', 'anexo_mime', $this->string(64)->null());
        $this->addColumn('{{%mensagem}}', 'anexo_tamanho', $this->integer()->null());

        $this->createTable('{{%notificacao}}', [
            'id' => $this->primaryKey(),
            'id_user' => $this->integer()->notNull(),
            // o que aconteceu: "mensagem" (ref_id = conversa)
            'tipo' => $this->string(30)->notNull(),
            'ref_id' => $this->integer()->null(),
            'titulo' => $this->string(255)->notNull(),
            'texto' => $this->string(500)->notNull()->defaultValue(''),
            // várias mensagens seguidas da mesma conversa juntam-se numa só notificação
            'quantidade' => $this->integer()->notNull()->defaultValue(1),
            'lida' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        $this->createIndex('idx-notificacao-user-lida', '{{%notificacao}}', ['id_user', 'lida', 'updated_at']);
        $this->addForeignKey('fk-notificacao-id_user', '{{%notificacao}}', 'id_user', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%notificacao}}');
        $this->dropColumn('{{%mensagem}}', 'anexo_tamanho');
        $this->dropColumn('{{%mensagem}}', 'anexo_mime');
        $this->dropColumn('{{%mensagem}}', 'anexo');
    }
}
