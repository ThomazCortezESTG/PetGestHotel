<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Datas próprias de cada animal dentro de uma reserva (ex.: o Bobi fica de 1 a 10 e a Mia só de 3 a 7).
 * Vazias = o animal usa as datas da reserva (booking.datainicio / booking.datafim).
 */
class m261125_140000_add_datas_to_booking_animal_quarto extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%booking_animal_quarto}}', 'datainicio', $this->date()->null()->after('id_quarto'));
        $this->addColumn('{{%booking_animal_quarto}}', 'datafim', $this->date()->null()->after('datainicio'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%booking_animal_quarto}}', 'datafim');
        $this->dropColumn('{{%booking_animal_quarto}}', 'datainicio');
    }
}
