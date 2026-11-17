<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Fotografia de perfil do cliente: guarda só o nome do ficheiro (em frontend/web/uploads/avatars).
 */
class m261117_150000_add_avatar_to_user_profile extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user_profile}}', 'avatar', $this->string(64)->null()->after('n_contribuinte'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user_profile}}', 'avatar');
    }
}
