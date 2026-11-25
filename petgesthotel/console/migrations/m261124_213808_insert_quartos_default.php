<?php

use yii\db\Migration;

class m261124_213808_insert_quartos_default extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%quarto}}', ['categoria', 'capacidade', 'tier', 'preco'], [
            ['standart', 20, '1', 20],
            ['deluxe',   20, '2', 40],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%quarto}}', ['categoria' => ['standart', 'deluxe']]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261124_213808_insert_quartos_default cannot be reverted.\n";

        return false;
    }
    */
}
