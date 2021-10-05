<?php
$plugins = array();

/* create the plugin object */
$plugins[0] = $modx->newObject('modPlugin');
$plugins[0]->set('name', 'XtraLife');
$plugins[0]->set('description', 'Automatically loads the user outline from XtraLife on each pageview.');
$plugins[0]->set('plugincode', getSnippetContent($sources['plugins'] . 'xtralife.plugin.php'));

$events = include $sources['events'] . 'events.xtralife.php';
if (is_array($events) && !empty($events)) {
    $plugins[0]->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' Plugin Events for XtraLife plugin.');
    flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find plugin events for XtraLife!');
}
unset($events);

return $plugins;
