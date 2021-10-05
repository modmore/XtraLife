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
    return false;
}


if (isset($_POST) && !empty($_POST['reset_email'])) {
    $email = trim(strtolower($_POST['reset_email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '<p class="error">Invalid email address provided.</p>';
    }
    $user = $modx->getObject('xlUser', [
        'class_key' => 'xlUser',
        'username' => $email,
    ]);

    if (!($user instanceof xlUser)) {
        return '<p class="error">No user found with that email address.</p>';
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
        return '<p class="error">Unexpected error creating a password reset request.</p>';
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (!in_array($response->getStatusCode(), [200, 201], true)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeReset snippet response ' . $response->getStatusCode() . ': ' . $body);
        return '<p class="error">Unexpected response creating a password reset request.</p>';
    }

    return <<<HTML
<p>An email has been sent to you with a reset code. Please enter it below with a new password.</p>
<form action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="csrf_token" value="">
    
    <div class="form-field">
        <label for="reset-shortcode">Reset code:</label>
        <input type="text" id="reset-shortcode" name="code">
    </div>
    <div class="form-field">
        <label for="reset-new-password">New password:</label>
        <input type="password" id="reset-new-password" name="new_password">
    </div>
    
    <div class="form-actions">
        <button type="submit">Set new password</button>
    </div>
</form>
HTML;
}


/**
 * Handle checking code and setting password
 */
if (isset($_POST) && !empty($_POST['code']) && isset($_SESSION['_xtralife_reset']) && is_array($_SESSION['_xtralife_reset'])) {
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
        return '<p class="error">Unexpected response validating your code, please try again.</p>';
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (!in_array($response->getStatusCode(), [200, 201], true)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeRegister snippet response ' . $response->getStatusCode() . ': ' . $body);
        return '<p class="error">Unexpected response checking your code, please try again.</p>';
    }

    if (!is_array($data) || !array_key_exists('gamer_id', $data)) {
        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body validating password for ' . $this->get('id') . ': ' . $body);
        return '<p class="error">Unexpected response loading your gamer account.</p>';
    }

    /** @var xlUser $user */
    $user = $modx->getObject('xlUser', [
        'username' => $data['networkid'],
    ]);
    if (!$user || $user->get('id') !== $_SESSION['_xtralife_reset']['for']) {
        return '<p class="error">Could not load user account for the requested reset.</p>';
    }

    $user->setGamerID($data['gamer_id']);
    $user->setGamerSecret($data['gamer_secret']);
    $user->set('password', $data['gamer_secret']); // Set the internal password to the gamer_secret; as we don't use it locally.
    $user->save();

    $newPassword = (string)$_POST['new_password'];

    $request = $service->getRequestFactory()->createRequest('POST', 'v1/gamer/password');
    $request = $user->addGamerAuth($request);
    $request->getBody()->write(json_encode([
        'password' => $newPassword,
    ]));

    try {
        $response = $service->getClient()->sendRequest($request);
    } catch (ClientExceptionInterface $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'XtraLifeReset snippet got unexpected ' . get_class($e) . ': ' . $e->getMessage());
        return '<p class="error">Unexpected error saving new password.</p>';
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);
    if (!is_array($data) || !array_key_exists('done', $data)) {
        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body validating password for ' . $this->get('id') . ': ' . $body);
        return '<p class="error">Failed saving new password.</p>';
    }

    // Login the user right away
    $user->addSessionContext($modx->context->get('key'));

    return '<p class="success">Successfully updated your new password. You\'re now logged in.</p>';
}

return <<<HTML
<p>If you've forgotten your password, you may set a new password here. Please start by entering your emailaddress, we will send a reset code to your email.</p>
<form action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="csrf_token" value="">
    
    <div class="form-field">
        <label for="reset-email">Email address:</label>
        <input type="text" id="reset-email" name="reset_email">
    </div>
    
    <div class="form-actions">
        <button type="submit">Send reset email</button>
    </div>
</form>
HTML;