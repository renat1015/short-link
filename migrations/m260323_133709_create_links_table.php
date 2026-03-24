<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%links}}`.
 */
class m260323_133709_create_links_table extends Migration
{
    private const TABLE_NAME = '{{%links}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'url' => $this->text()->notNull(),
            'url_hash' => $this->string(64)->notNull()->unique(),
            'short_code' => $this->string(6)->notNull()->unique(),
            'clicks' => $this->integer()->defaultValue(0)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-links-url_hash', self::TABLE_NAME, 'url_hash');
        $this->createIndex('idx-links-short_code', self::TABLE_NAME, 'short_code');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);
    }
}
