<?php
/**
 * @var modX $modx
 */

$path = $modx->getOption('xtralife.core_path', null, $modx->getOption('core_path') . 'components/xtralife/');
$service = $modx->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
if (!($service instanceof XtraLife)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
    return;
}

switch ($modx->event->name) {
    case 'OnUserNotFound':
        // @todo Try looking for an existing user in XtraLife
        break;

    case 'OnHandleRequest':
        // @todo load the outline for current user and set placeholders
        break;
}

return;