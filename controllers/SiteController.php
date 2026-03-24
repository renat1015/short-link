<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Link;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Url;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRImage;
use chillerlan\QRCode\Common\EccLevel;
use yii\filters\ContentNegotiator;
use yii\web\BadRequestHttpException;

/**
 * Site controller handles main page and short link creation.
 * 
 * @package app\controllers
 */
class SiteController extends Controller
{
    /**
     * QR code configuration constants
     */
    private const QR_VERSION = 5;
    private const QR_SCALE = 5;
    private const QR_ECC_LEVEL = EccLevel::L;
    
    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['create'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'create'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays homepage with short link creation form.
     * 
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * Creates a short link from provided URL.
     * 
     * @return array JSON response with link data
     * @throws BadRequestHttpException if URL is not provided
     */
    public function actionCreate(): array
    {
        $url = $this->getUrlFromRequest();
        
        if (empty($url)) {
            throw new BadRequestHttpException('URL parameter is required');
        }
        
        $link = Link::findOrCreateByUrl($url);
        
        if ($link->hasErrors()) {
            return $this->prepareErrorResponse($link);
        }
        
        return $this->prepareSuccessResponse($link);
    }

    /**
     * Prepares successful response with link data.
     * 
     * @param Link $link
     * @return array
     */
    private function prepareSuccessResponse(Link $link): array
    {
        return [
            'success' => true,
            'short_url' => $this->getShortUrl($link->short_code),
            'short_code' => $link->short_code,
            'qr_code' => $this->generateQrCodeBase64($link->short_code),
            'original_url' => $link->url,
            'clicks' => $link->clicks,
        ];
    }

    /**
     * Prepares error response with validation messages.
     * 
     * @param Link $link
     * @return array
     */
    private function prepareErrorResponse(Link $link): array
    {
        $errors = $link->getFirstErrors();
        $firstError = reset($errors);
        
        return [
            'success' => false,
            'message' => $firstError ?: 'Unknown error occurred',
            'errors' => $errors,
        ];
    }

    /**
     * Generates QR code for short link in base64 format.
     * 
     * @param string $code Short link code
     * @return string|null QR code as base64 or null on failure
     */
    private function generateQrCodeBase64(string $code): ?string
    {
        try {
            $url = $this->getShortUrl($code);
            
            $options = new QROptions([
                'version' => self::QR_VERSION,
                'outputType' => QRImage::class,
                'eccLevel' => self::QR_ECC_LEVEL,
                'scale' => self::QR_SCALE,
                'imageBase64' => true,
                'quietzone' => true,
                'addQuietzone' => true,
            ]);
            
            $qrCode = new QRCode($options);
            
            return $qrCode->render($url);
        } catch (\Throwable $e) {
            Yii::error("QR code generation failed: {$e->getMessage()}", __METHOD__);
            return null;
        }
    }

    /**
     * Gets short URL by code.
     * 
     * @param string $code
     * @return string
     */
    private function getShortUrl(string $code): string
    {
        return Url::to(['/go/' . $code], true);
    }
    
    /**
     * Extracts URL from request.
     * 
     * @return string|null
     */
    private function getUrlFromRequest(): ?string
    {
        return Yii::$app->request->post('url');
    }
}
