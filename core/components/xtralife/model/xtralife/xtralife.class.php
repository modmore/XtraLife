<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class XtraLife
{
    public modX $modx;
    public array $config = [];
    public const VERSION = '1.0.0-dev1';
    private Client $client;
    private RequestFactoryInterface $factory;

    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('xtralife.core_path', $config, $this->modx->getOption('core_path') . 'components/xtralife/');
        $assetsUrl = $this->modx->getOption('xtralife.assets_url', $config, $this->modx->getOption('assets_url') . 'components/xtralife/');
        $assetsPath = $this->modx->getOption('xtralife.assets_path', $config, $this->modx->getOption('assets_path') . 'components/xtralife/');
        $this->config = array_merge([
            'basePath' => $corePath,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',
            'elementsPath' => $corePath . 'elements/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php',
            'version' => self::VERSION,
        ], $config);

        // Load configuration from .env into $_ENV
        \Dotenv\Dotenv::createImmutable($corePath)->load();

        // Load the xPDO Model
        $modelPath = $this->config['modelPath'];
        $this->modx->addPackage('xtralife', $modelPath);
        $this->modx->lexicon->load('xtralife:default');
    }

    /**
     * Gets a Guzzle instance to send requests to the XtraLife API.
     *
     * @todo For MODX3 support, grab the client from the service container instead.
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $_ENV['XTRALIFE_API_URL'] ?? '',
                'timeout' => 10.0,

                'headers' => [
                    'x-apikey' => $_ENV['XTRALIFE_API_KEY'] ?? '',
                    'x-apisecret' => $_ENV['XTRALIFE_API_SECRET'] ?? '',
                    'Content-Type' => 'application/json',
                    'Accepts' => 'application/json',
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Gets a RequestFactory to create new requests to send.
     *
     * @todo For MODX3 support, grab the factory from the service container instead.
     * @return RequestFactoryInterface
     */
    public function getRequestFactory(): RequestFactoryInterface
    {
        if (!$this->factory) {
            $this->factory = new HttpFactory();
        }
        return $this->factory;
    }
}

