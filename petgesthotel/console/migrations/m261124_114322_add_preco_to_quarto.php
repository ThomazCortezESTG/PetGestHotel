<?php

use yii\db\Migration;

class m261124_114322_add_preco_to_quarto extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%quarto}}', 'preco', $this->decimal(10, 2)->notNull()->defaultValue(0)->after('tier'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%quarto}}', 'preco');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261124_114322_add_preco_to_quarto cannot be reverted.\n";

        return false;
    }
    */
}
