<?php
/**
 * @var modX $modx
 * @var LoginHooks $hook
 */

use Psr\Http\Client\ClientExceptionInterface;

$path = $modx->getOption('xtralife.core_path', null, $modx->getOption('core_path') . 'components/xtralife/');
$service = $modx->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
if (!($service instanceof XtraLife)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
    return false;
}

/** @var modUser $user */
$user = $hook->getValue('updateprofile.user');
if (!$user || !($user instanceof xlUser)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet does not have a register.user field');
    return true; // returning true here to allow regular users being edited normally
}

if (!$hook->getValue('updateprofile.usernameChanged')) {
    return true; // nothing to do
}

$email = $user->get('username');
$email = strtolower($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $hook->addError('xtralife', 'Invalid email address provided.');
    return false;
}

$request = $service->getRequestFactory()->createRequest('POST', 'v1/gamer/email');
$request = $user->addGamerAuth($request);
$request->getBody()->write(json_encode([
    'email' => $email,
]));

try {
    $response = $service->getClient()->sendRequest($request);
} catch (ClientExceptionInterface $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeUpdateProfile snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
    $hook->addError('xtralife', 'Unexpected issue updating gamer user.');
    return false;
}
$body = $response->getBody()->getContents();
$data = json_decode($body, true);

if (!in_array($response->getStatusCode(), [200, 201], true)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeUpdateProfile snippet response ' . $response->getStatusCode() . ': ' . $body);
    $hook->addError('xtralife', 'Could not update the gamer user.');
    return false;
}

if (!is_array($data) || !array_key_exists('done', $data)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body updating email for ' . $user->get('id') . ': ' . $body);
    $hook->addError('xtralife', 'Unexpected error creating user: ' . $data['message']);
    return false;
}

/**
 * Also update the users' profile
 */

$request = $service->getRequestFactory()->createRequest('POST', 'v1/gamer/profile');
$request = $user->addGamerAuth($request);
$request->getBody()->write(json_encode([
    'email' => $email,
]));

try {
    $response = $service->getClient()->sendRequest($request);
} catch (ClientExceptionInterface $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeUpdateProfile snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
    $hook->addError('xtralife', 'Unexpected issue updating gamer profile.');
    return false;
}
$body = $response->getBody()->getContents();
$data = json_decode($body, true);

if (!in_array($response->getStatusCode(), [200, 201], true)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeUpdateProfile snippet response ' . $response->getStatusCode() . ': ' . $body);
    $hook->addError('xtralife', 'Could not update the gamer profile.');
    return false;
}

if (!is_array($data) || !array_key_exists('done', $data)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body updating email for ' . $user->get('id') . ': ' . $body);
    $hook->addError('xtralife', 'Unexpected error creating user: ' . $data['message']);
    return false;
}

return true;