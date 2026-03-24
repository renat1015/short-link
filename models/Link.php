<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * This is the model class for table "{{%links}}".
 * 
 * @property int $id
 * @property string $url
 * @property string $url_hash
 * @property string $short_code
 * @property int $clicks
 * @property string $created_at
 *
 * @property LinkLog[] $linkLogs
 * 
 * @package app\models
 */
class Link extends ActiveRecord
{
    private const MAX_GENERATION_ATTEMPTS = 10;
    private const HASH_VARIANTS_OFFSETS = [0, 2, 4, 6, 8];
    private const DEFAULT_CODE_LENGTH = 6;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%links}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url'], 'required'],
            [['url'], 'url', 'defaultScheme' => 'https', 'message' => 'Некорректный URL'],
            [['url'], 'validateUrlAccessible'],
            ['url_hash', 'unique'],
            ['short_code', 'string', 'length' => [4, 6]],
            ['short_code', 'unique'],
            ['short_code', 'default', 'value' => null],
            ['clicks', 'integer', 'min' => 0],
            ['clicks', 'default', 'value' => 0],
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
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        if ($insert) {
            $this->url_hash = $this->generateUrlHash();
            
            if (empty($this->short_code)) {
                $shortCode = $this->generateShortCode();
                
                if ($shortCode === null) {
                    $this->addError('short_code', 'Не удалось сгенерировать уникальный короткий код');
                    return false;
                }
                
                $this->short_code = $shortCode;
            }
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($insert) {
            $this->refresh();
        }
    }

    /**
     * Gets the link logs relation.
     * 
     * @return ActiveQuery
     */
    public function getLinkLogs(): ActiveQuery
    {
        return $this->hasMany(LinkLog::class, ['link_id' => 'id']);
    }

    /**
     * Increments the click counter atomically.
     */
    public function incrementClicks(): void
    {
        $this->updateCounters(['clicks' => 1]);
    }

    /**
     * Generates a unique short code based on URL hash.
     * 
     * @return string|null Unique short code or null if generation failed
     */
    public function generateShortCode(): ?string
    {
        foreach (self::HASH_VARIANTS_OFFSETS as $offset) {
            $code = substr($this->url_hash, $offset, self::DEFAULT_CODE_LENGTH);
            if ($this->isShortCodeUnique($code)) {
                return $code;
            }
        }

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $code = $this->generateRandomCode();
            if ($this->isShortCodeUnique($code)) {
                return $code;
            }
        }
        
        return null;
    }

    /**
     * Generates a random short code.
     * 
     * @return string
     */
    private function generateRandomCode(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        $length = random_int(4, 6);
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }

    /**
     * Checks if short code is unique.
     * 
     * @param string $code
     * @return bool
     */
    private function isShortCodeUnique(string $code): bool
    {
        return !static::find()->where(['short_code' => $code])->exists();
    }
    
    /**
     * Generates SHA256 hash from URL.
     * 
     * @return string
     */
    private function generateUrlHash(): string
    {
        return hash('sha256', $this->url);
    }

    /**
     * Validates URL accessibility.
     * 
     * @param string $attribute
     * @param array|null $params
     */
    public function validateUrlAccessible(string $attribute, ?array $params = null): void
    {
        $url = $this->$attribute;
        
        if (empty($url) || !is_string($url)) {
            return;
        }
        
        if (!$this->isUrlAccessible($url)) {
            $this->addError($attribute, 'Данный URL не доступен');
        }
    }

    /**
     * Checks if URL is accessible via HTTP request.
     * 
     * @param string $url
     * @return bool
     */
    private function isUrlAccessible(string $url): bool
    {
        $url = self::normalizeUrl($url);
        
        $ch = curl_init($url);
        
        if ($ch === false) {
            return false;
        }
        
        try {
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LinkChecker/1.0)',
            ]);
            
            curl_exec($ch);
            
            if (curl_errno($ch) !== 0) {
                return false;
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            return $httpCode >= 200 && $httpCode < 400;
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Finds or creates a link by URL.
     * 
     * @param string $url
     * @return static
     */
    public static function findOrCreateByUrl(string $url): static
    {
        $normalizedUrl = self::normalizeUrl($url);
        $link = self::findByUrl($normalizedUrl);
        
        if ($link !== null) {
            return $link;
        }
        
        $link = new self();
        $link->url = $normalizedUrl;
        $link->save();
        
        return $link;
    }

    /**
     * Processes redirect by short code.
     * 
     * @param string $code
     * @return Response
     * @throws NotFoundHttpException
     */
    public static function processRedirect(string $code): Response
    {
        $link = self::findByCode($code);
        
        if ($link === null) {
            throw new NotFoundHttpException('Ссылка не найдена');
        }
        
        LinkLog::logClick($link->id, Yii::$app->request->userIP);
        $link->incrementClicks();
        
        return Yii::$app->response->redirect($link->url);
    }

    /**
     * Normalizes URL by adding scheme if missing.
     * 
     * @param string $url
     * @return string
     */
    public static function normalizeUrl(string $url): string
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            return 'https://' . $url;
        }
        
        return $url;
    }

    /**
     * Finds link by URL using hash.
     * 
     * @param string $url
     * @return static|null
     */
    public static function findByUrl(string $url): ?static
    {
        $hash = hash('sha256', $url);
        
        return static::findOne(['url_hash' => $hash]);
    }

    /**
     * Finds link by short code.
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode(string $code): ?static
    {
        return static::findOne(['short_code' => $code]);
    }
}
