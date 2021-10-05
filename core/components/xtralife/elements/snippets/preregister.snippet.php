<?php
/**
 * @var modX $modx
 * @var LoginHooks $hook
 * @var array $scriptProperties
 */

$path = $modx->getOption('xtralife.core_path', null, $modx->getOption('core_path') . 'components/xtralife/');
$service = $modx->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
if (!($service instanceof XtraLife)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
    return false;
}

// Set class key
$hook->setValue('class_key', 'xlUser');

$email = $hook->getValue($scriptProperties['usernameField'] ?? 'username');
$email = trim(strtolower($email));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $hook->addError($hook->getValue('usernameField'), 'Not a valid email address.');
    return false;
}

return true;