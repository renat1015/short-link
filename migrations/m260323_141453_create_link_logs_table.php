<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%link_logs}}`.
 */
class m260323_141453_create_link_logs_table extends Migration
{
    private const TABLE_NAME = '{{%link_logs}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'link_id' => $this->integer()->unsigned()->notNull(),
            'ip' => $this->string(45)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-link_logs-link_id', self::TABLE_NAME, 'link_id');

        $this->addForeignKey(
            'fk-link_logs-link_id',
            self::TABLE_NAME,
            'link_id',
            '{{%links}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-link_logs-link_id', self::TABLE_NAME);
        $this->dropTable(self::TABLE_NAME);
    }
}
