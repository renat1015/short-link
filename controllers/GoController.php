<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Link;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\RateLimiter;

/**
 * Controller for handling short link redirects.
 * Public access without authentication.
 * 
 * @package app\controllers
 */
class GoController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'only' => ['index'],
                'enableRateLimitHeaders' => true,
            ],
        ];
    }

    /**
     * Redirects to original URL by short code.
     * 
     * @param string $code Short link code
     * @return Response Redirect response
     * @throws NotFoundHttpException if link not found
     */
    public function actionIndex(string $code): Response
    {
        if (!$this->validateShortCode($code)) {
            throw new NotFoundHttpException('Invalid short code format');
        }

        return Link::processRedirect($code);
    }

    /**
     * Validates short code format.
     * 
     * @param string $code Short code to validate
     * @return bool
     */
    private function validateShortCode(string $code): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]{4,6}$/', $code);
    }
}
