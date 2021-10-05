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
$user = $hook->getValue('register.user');
if (!($user instanceof xlUser)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister user type is not xlUser, is the preHooks property set?');
    $hook->addError('xtralife', 'Failed creating gamer user.');
    return false;
}

$email = $user->get('username');
$email = trim(strtolower($email));

$request = $service->getRequestFactory()->createRequest('POST', 'v1/login');
$request->getBody()->write(json_encode([
    'network' => 'email',
    'id' => $email,
    'secret' => $hook->getValue('password'),
    'options' => [
        'preventRegistration' => false,
    ],
]));

try {
    $response = $service->getClient()->sendRequest($request);
} catch (ClientExceptionInterface $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
    $hook->addError('xtralife', 'Unexpected issue creating gamer user.');
    return false;
}
$body = $response->getBody()->getContents();
$data = json_decode($body, true);


if (!in_array($response->getStatusCode(), [200, 201], true)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet response ' . $response->getStatusCode() . ': ' . $body);
    $hook->addError('xtralife', 'Could not create the gamer user, email might already exist with a different password?');
    return false;
}

if (!is_array($data) || !array_key_exists('gamer_id', $data)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body validating password for ' . $user->get('id') . ': ' . $body);
    $hook->addError('xtralife', 'Unexpected response creating gamer user.');
    return false;
}

$user->setGamerID($data['gamer_id']);
$user->setGamerSecret($data['gamer_secret']);
$user->set('password', $data['gamer_secret']); // Set the internal password to the gamer_secret; as we don't use it locally.
$user->save();

return true;