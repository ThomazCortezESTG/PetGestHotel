<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Motivo quando o hotel rejeita uma reserva (o cliente vê-o na reserva e na notificação).
 */
class m261125_100000_add_motivo_to_booking extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%booking}}', 'motivo_rejeicao', $this->string(500)->null()->after('status'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%booking}}', 'motivo_rejeicao');
    }
}
