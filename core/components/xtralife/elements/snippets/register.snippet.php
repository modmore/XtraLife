<?php
/**
 * @var modX $modx
 * @var LoginHooks $hook
 */

$path = $modx->getOption('xtralife.core_path', null, $modx->getOption('core_path') . 'components/xtralife/');
$service = $modx->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
if (!($service instanceof XtraLife)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
    return false;
}

/** @var modUser $user */
$user = $hook->getValue('register.user');
if (!$user) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet does not have a register.user field');
    return false;
}


$user->set('class_key', 'xlUser');


$user->save();





return;