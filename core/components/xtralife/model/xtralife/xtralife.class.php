<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class XtraLife
{
    /**
     * @var modX $modx
     */
    public $modx;

    /**
     * @var array
     */
    public $config = [];

    public const VERSION = '1.0.0-dev1';

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
}

