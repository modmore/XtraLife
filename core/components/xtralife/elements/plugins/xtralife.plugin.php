<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 */

use Psr\Http\Client\ClientExceptionInterface;

$path = $modx->getOption('xtralife.core_path', null, $modx->getOption('core_path') . 'components/xtralife/');
$service = $modx->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
if (!($service instanceof XtraLife)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
    return;
}

switch ($modx->event->name) {
    case 'OnUserNotFound':
//        $username = $scriptProperties['username'] ?? '';
//        $password = $scriptProperties['password'] ?? '';
//
//        $request = $service->getRequestFactory()->createRequest('POST', 'v1/login');
//        $request->getBody()->write(json_encode([
//            'network' => 'email',
//            'id' => $username,
//            'secret' => $password,
//            'options' => [
//                'preventRegistration' => true,
//            ],
//        ]));
//
//        try {
//            $response = $service->getClient()->sendRequest($request);
//        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
//            $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife OnUserNotFound got unexpected ' . get_class($e) . ': ' . $e->getMessage());
//            return false;
//        }
//        $body = $response->getBody()->getContents();
//        $data = json_decode($body, true);
//
//        if (in_array($response->getStatusCode(), [200, 201], true)) {
//            $modx->log(modX::LOG_LEVEL_ERROR, 'FXtraLifeRegister found existing user, creating in MODX' . $body);
//
//            $user = $modx->newObject('xlUser');
//            $user
//
//            $modx->event->_output = $user;
//            return;
//        }

        break;

    case 'OnWebPageInit':
        if (!$modx->user || !($modx->user instanceof xlUser)) {
            return;
        }

        $cache = $modx->getCacheManager();
        $cacheKey = 'xtralife/outline/' . $modx->user->get('remote_key');

        $outline = $cache->get($cacheKey);
        if (!empty($outline) && is_array($outline)) {
            $modx->toPlaceholders($outline, 'outline');
            $modx->setPlaceholder('outline_dump', json_encode($outline, JSON_PRETTY_PRINT));
            return;
        }

        $request = $service->getRequestFactory()->createRequest('GET', 'v1/gamer/outline');
        $request = $modx->user->addGamerAuth($request);

        try {
            $response = $service->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife OnWebPageInit got unexpected ' . get_class($e) . ': ' . $e->getMessage());
            return false;
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if ($response->getStatusCode() === 200) {
            $outline = $data['outline'];
            $modx->toPlaceholders($outline, 'outline');
            $modx->setPlaceholder('outline_dump', json_encode($outline, JSON_PRETTY_PRINT));
            $cache->set($cacheKey, $outline, 5*60);
            return;
        }

        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLife OnWebPageInit received unexpected status ' . $response->getStatusCode() . ' loading outline: ' . $body);

        break;
}

return;