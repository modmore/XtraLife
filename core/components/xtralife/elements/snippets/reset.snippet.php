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
    return '<p class="error">Reset temporarily unavailable.</p>';
}

$emailTpl = $scriptProperties['tplEnterEmail'] ?? 'reset/enter_email';
$codeTpl = $scriptProperties['tplEnterCode'] ?? 'reset/enter_code';
$successTpl = $scriptProperties['tplSuccess'] ?? 'reset/success';
$errorTpl = $scriptProperties['tplError'] ?? 'reset/error';

$csrf = $service->getCsrf();


if (isset($_POST) && !empty($_POST['reset_email'])) {
    if (!$csrf->checkOnce('reset_email', $_POST['csrf_token'] ?? '')) {
        return $service->getChunk($errorTpl, [
            'message' => 'invalid security token',
        ]);
    }

    $email = trim(strtolower($_POST['reset_email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $service->getChunk($errorTpl, [
            'message' => 'Invalid email address provided',
        ]);
    }
    $user = $modx->getObject('xlUser', [
        'class_key' => 'xlUser',
        'username' => $email,
    ]);

    if (!($user instanceof xlUser)) {
        return $service->getChunk($errorTpl, [
            'message' => 'No user found with that email address.',
        ]);
    }

    $_SESSION['_xtralife_reset'] = ['for' => $user->get('id'), 'on' => time()];

    $request = $service->getRequestFactory()->createRequest('POST', 'v1/login/' . urlencode($email));
    $request->getBody()->write(json_encode([
        'from' => $scriptProperties['emailFrom'] ?? $modx->getOption('emailsender'),
        'title' => $scriptProperties['emailTitle'] ?? 'Reset your password',
        'body' => $scriptProperties['emailBody'] ?? 'Your password reset code is: [[SHORTCODE]]',
    ]));

    try {
        $response = $service->getClient()->sendRequest($request);
    } catch (ClientExceptionInterface $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeReset snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
        return $service->getChunk($errorTpl, [
            'message' => 'Unexpected error occurred creating a password reset request',
        ]);
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (!in_array($response->getStatusCode(), [200, 201], true)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeReset snippet response ' . $response->getStatusCode() . ': ' . $body);
        return $service->getChunk($errorTpl, [
            'message' => 'Failed creating a password reset request.',
        ]);
    }

    return $service->getChunk($codeTpl, [
        'csrf_token' => $csrf->generate('reset_code'),
    ]);
}


/**
 * Handle checking code and setting password
 */
if (isset($_POST) && !empty($_POST['code']) && isset($_SESSION['_xtralife_reset']) && is_array($_SESSION['_xtralife_reset'])) {
    if (!$csrf->checkOnce('reset_code', $_POST['csrf_token'] ?? '')) {
        return $service->getChunk($errorTpl, [
            'message' => 'Invalid security token.',
        ]);
    }

    $request = $service->getRequestFactory()->createRequest('POST', 'v1/login');
    $request->getBody()->write(json_encode([
        'network' => 'restore',
        'id' => '',
        'secret' => trim((string)$_POST['code']),
        'options' => [
            'preventRegistration' => true,
        ],
    ]));

    try {
        $response = $service->getClient()->sendRequest($request);
    } catch (ClientExceptionInterface $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
        return $service->getChunk($errorTpl, [
            'message' => 'Unexpected error checking reset token.',
        ]);
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (!in_array($response->getStatusCode(), [200, 201], true)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet response ' . $response->getStatusCode() . ': ' . $body);
        return $service->getChunk($errorTpl, [
            'message' => 'Invalid reset token provided.',
        ]);
    }

    if (!is_array($data) || !array_key_exists('gamer_id', $data)) {
        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body validating password for ' . $this->get('id') . ': ' . $body);
        return $service->getChunk($errorTpl, [
            'message' => 'Unexpected error loading gamer profile.',
        ]);
    }

    /** @var xlUser $user */
    $user = $modx->getObject('xlUser', [
        'username' => $data['networkid'],
    ]);
    if (!$user || $user->get('id') !== $_SESSION['_xtralife_reset']['for']) {
        return $service->getChunk($errorTpl, [
            'message' => 'Could not find user or invalid request.',
        ]);
    }

    $user->setGamerID($data['gamer_id']);
    $user->setGamerSecret($data['gamer_secret']);
    $user->save();

    // Change the user password; this also updates XtraLife through xlUser::changePassword
    $user->changePassword((string)$_POST['new_password'], '', false);

    // Login the user right away
    $user->addSessionContext($modx->context->get('key'));

    return $service->getChunk($successTpl);
}


return $service->getChunk($emailTpl, [
    'csrf_token'  => $csrf->generate('reset_email'),
]);