<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));

$XtraLife = $modx->getService('xtralife','XtraLife', $componentPath.'/core/components/xtralife/model/xtralife/', array(
    'xtralife.core_path' => $componentPath.'/core/components/xtralife/',
));


/* Namespace */
if (!createObject('modNamespace',array(
    'name' => 'xtralife',
    'path' => $componentPath.'/core/components/xtralife/',
    'assets_path' => $componentPath.'/assets/components/xtralife/',
),'name', true)) {
    echo "Error creating namespace xtralife.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', array(
    'key' => 'xtralife.core_path',
    'value' => $componentPath.'/core/components/xtralife/',
    'xtype' => 'textfield',
    'namespace' => 'xtralife',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating xtralife.core_path setting.\n";
}

/* Fetch assets url */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/XtraLife/_bootstrap/index.php';
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/').'/';
$assetsUrl = "{$requestUri}assets/components/xtralife/";

if (!createObject('modSystemSetting', array(
    'key' => 'xtralife.assets_url',
    'value' => $assetsUrl,
    'xtype' => 'textfield',
    'namespace' => 'xtralife',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating xtralife.assets_url setting.\n";
}
if (!createObject('modPlugin', array(
    'name' => 'XtraLife',
    'static' => true,
    'static_file' => $componentPath.'/core/components/xtralife/elements/plugins/xtralife.plugin.php',
), 'name', true)) {
    echo "Error creating XtraLife Plugin.\n";
}
$plugin = $modx->getObject('modPlugin', array('name' => 'XtraLife'));
if ($plugin) {
    if (!createObject('modPluginEvent', array(
        'pluginid' => $plugin->get('id'),
        'event' => 'OnUserNotFound',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
    if (!createObject('modPluginEvent', array(
        'pluginid' => $plugin->get('id'),
        'event' => 'OnHandleRequest',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
}

if (!createObject('modSnippet', array(
    'name' => 'XtraLifeRegister',
    'static' => true,
    'static_file' => $componentPath.'/core/components/xtralife/elements/snippets/register.snippet.php',
), 'name', true)) {
    echo "Error creating XtraLifeRegister snippet.\n";
}

//$settings = include dirname(dirname(__FILE__)).'/_build/data/settings.php';
//foreach ($settings as $key => $opts) {
//    if (!createObject('modSystemSetting', array(
//        'key' => 'xtralife.' . $key,
//        'value' => $opts['value'],
//        'xtype' => (isset($opts['xtype'])) ? $opts['xtype'] : 'textfield',
//        'namespace' => 'xtralife',
//        'area' => $opts['area'],
//        'editedon' => time(),
//    ), 'key', false)) {
//        echo "Error creating xtralife.".$key." setting.\n";
//    }
//}


/* Create tables */
//$objectContainers = array(
//
//);
//echo "Creating tables...\n";
//$manager = $modx->getManager();
//foreach ($objectContainers as $oC) {
//    $manager->createObjectContainer($oC);
//}

echo "Adding to extension packages..\n";
$modx->addExtensionPackage('xtralife', $componentPath . '/core/components/xtralife/model/');

echo "Done.\n";

// Refresh the cache
$modx->cacheManager->refresh();


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = array(), $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        if (is_array($primaryField)) {
            $condition = array();
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        }
        else {
            $condition = array($primaryField => $data[$primaryField]);
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
