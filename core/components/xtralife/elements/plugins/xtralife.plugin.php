<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 */

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

    case 'OnHandleRequest':
        // @todo load the outline for current user and set placeholders
        break;
}

$bar = <<<HTML
<div id="am-frame-01fh3ne0afc2rrj37k35w8b4zq">
<p><a href="https://alpacamarkt.nl/alpaca-fokker/dutch-highline-alpacas" target="_blank" rel="noopener">Bekijk onze volledige verkooplijst op AlpacaMarkt.nl</a></p>
</div>
<script>(function() {var c = document.getElementById('am-frame-01fh3ne0afc2rrj37k35w8b4zq'), l = document.createElement('p'), f = document.createElement('iframe');l.innerText = 'De\u0020verkooplijst\u0020wordt\u0020geladen,\u0020een\u0020moment\u0020geduld...'; c.innerHTML = ''; c.appendChild(l);setTimeout(function () {f.setAttribute('src', 'https://alpacamarkt.nl/fokker-embed/dutch-highline-alpacas/01fh3ne0afc2rrj37k35w8b4zq');f.setAttribute('sandbox', 'allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts');f.setAttribute('allowTransparency', true);f.style.border = 'none';f.style.width = '100%';f.addEventListener('load', function(e) {l.style.display = 'none';});c.appendChild(f);});window.addEventListener('message', function(e) {if (e.data && e.data[0] === '01fh3ne0afc2rrj37k35w8b4zq/setHeight') { f.style.height = e.data[1] + 'px'; }}, false);})()</script>

HTML;


return;