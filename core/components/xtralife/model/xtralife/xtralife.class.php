<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use modmore\XtraLife\Security\Csrf;
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
    private array $chunks = [];

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
//        $this->modx->lexicon->load('xtralife:default'); @fixme fails when logged in, because xlUser is loaded before the lexicon. Removed as not currently in use.

        // @todo For MODX3 support, grab the client from the service container instead.
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

        // @todo For MODX3 support, grab the factory from the service container instead.
        $this->factory = new HttpFactory();
    }

    /**
     * Gets a Guzzle instance to send requests to the XtraLife API.
     *
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Gets a RequestFactory to create new requests to send.
     *
     * @return RequestFactoryInterface
     */
    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->factory;
    }

    /**
     * Creates a fresh Csrf instance.
     *
     * @return Csrf
     */
    public function getCsrf(): Csrf
    {
        return new Csrf(new Csrf\SessionStorage());
    }


    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier development.
     *
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     *@author Shaun McCormick
     * @access public
     */
    public function getChunk(string $name, array $properties = array())
    {
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk', array('name' => $name));
            if (empty($chunk) || !is_object($chunk)) {
                $chunk = $this->_getTplChunk($name);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }

    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.tpl
     * @param string $postFix The postfix to append to the name
     * @return modChunk|boolean Returns the modChunk object if found, otherwise false.
     * @author Shaun "splittingred" McCormick
     */
    private function _getTplChunk(string $name, string $postFix = '.chunk.tpl')
    {
        $chunk = false;
        $f = $this->config['elementsPath'] . 'chunks/' . strtolower($name) . $postFix;
        if (file_exists($f)) {
            $o = file_get_contents($f);
            /* @var modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name', $name);
            $chunk->setContent($o);
        }

        return $chunk;
    }
}

