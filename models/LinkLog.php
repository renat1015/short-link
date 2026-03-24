<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%link_logs}}".
 *
 * @property int $id
 * @property int $link_id
 * @property string $ip
 * @property string $created_at
 *
 * @property Link $link
 * 
 * @package app\models
 */
class LinkLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%link_logs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['link_id', 'ip'], 'required'],
            [['link_id'], 'integer'],
            [['ip'], 'string', 'max' => 45],
            [['link_id'], 'exist', 'targetClass' => Link::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Gets the link relation.
     * 
     * @return ActiveQuery
     */
    public function getLink(): ActiveQuery
    {
        return $this->hasOne(Link::class, ['id' => 'link_id']);
    }

    /**
     * Logs a click.
     * 
     * @param int $linkId
     * @param string $ip
     * @return bool
     */
    public static function logClick(int $linkId, string $ip): bool
    {
        $log = new static();
        $log->link_id = $linkId;
        $log->ip = $ip;
        
        return $log->save();
    }
}
