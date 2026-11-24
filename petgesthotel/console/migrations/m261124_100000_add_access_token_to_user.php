<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Token de acesso da API (app móvel). É gerado no login da API (POST api/auth/login)
 * e enviado pela app em cada pedido no cabeçalho "Authorization: Bearer <token>".
 */
class m261124_100000_add_access_token_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'access_token', $this->string(64)->null()->after('auth_key'));
        $this->createIndex('idx-user-access_token', '{{%user}}', 'access_token', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-user-access_token', '{{%user}}');
        $this->dropColumn('{{%user}}', 'access_token');
    }
}
