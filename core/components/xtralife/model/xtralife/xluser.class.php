<?php

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use modmore\XtraLife\Security\Encryption;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * XtraLife user integration for MODX.
 *
 * Copyright 2021 by modmore
 *
 * @package xtralife
 * @license See core/components/xtralife/docs/license.txt
 */
class xlUser extends modUser
{
    /**
     * @var XtraLife
     */
    protected $xtraLife;

    /**
     * @var ClientInterface
     */
    protected $xtraLifeClient;

    /**
     * @var RequestFactoryInterface
     */
    protected $xtraLifeRequestFactory;

    public function __construct(xPDO &$xpdo)
    {
        parent::__construct($xpdo);
        $this->set('class_key', 'xlUser');

        // Load the service for easy access
        $path = $this->xpdo->getOption('xtralife.core_path', null, $this->xpdo->getOption('core_path') . 'components/xtralife/');
        $service = $this->xpdo->getService('xtralife', 'XtraLife', $path . '/model/xtralife/');
        if (!($service instanceof XtraLife)) {
            $this->xpdo->log(modX::LOG_LEVEL_ERROR, 'XtraLife service class not available.');
            return;
        }
        $this->xtraLife = $service;
        $this->xtraLifeClient = $service->getClient();
        $this->xtraLifeRequestFactory = $service->getRequestFactory();
    }

    public function addGamerAuth(MessageInterface $request): MessageInterface
    {
        // Only add the authorization string if we have a gamer secret.
        if ($gamerSecret = $this->getGamerSecret()) {
            $gamerId = $this->get('remote_key');
            return $request->withHeader('Authorization', "Basic {$gamerId}:{$gamerSecret}");
        }
        return $request;
    }

    private function getGamerSecret()
    {
        $remoteData = $this->get('remote_data') ?? [];
        if (is_array($remoteData) && array_key_exists('gamer_secret', $remoteData) && !empty($remoteData['gamer_secret'])) {
            $encryptedSecret = $remoteData['gamer_secret'];
            try {
                $secret = Encryption::decrypt($encryptedSecret, Encryption::KEY_DEFAULT);
                return $secret;
            } catch (EnvironmentIsBrokenException | WrongKeyOrModifiedCiphertextException $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Failed decrypting gamer secret for user ' . $this->get('id') . ': ' . $e->getMessage());
            }
        }

        return false;
    }

    public function setGamerSecret(string $secret): void
    {
        $remoteData = $this->get('remote_data') ?? [];
        try {
            $encryptedSecret = Encryption::encrypt($secret, Encryption::KEY_DEFAULT);
            $remoteData['gamer_secret'] = $encryptedSecret;
            $this->set('remote_data', $remoteData);
        } catch (EnvironmentIsBrokenException $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Failed encrypting gamer secret for user ' . $this->get('id') . ': ' . $e->getMessage());
        }
    }

    public function setGamerID(string $id)
    {
        $this->set('remote_key', $id);
    }

    public function passwordMatches($password, array $options = array()): bool
    {
        $request = $this->xtraLifeRequestFactory->createRequest('POST', '/v1/login');
        $request->getBody()->write(json_encode([
            'network' => 'email',
            'id' => $this->get('username'),
            'secret' => $password,
            'options' => [
                'preventRegistration' => true,
            ]
        ]));

        try {
            $response = $this->xtraLifeClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'xlUser received ' . get_class($e) . ' trying to check password for user ' . $this->get('id') . ': ' . $e->getMessage());
            return false;
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if ($response->getStatusCode() !== 200) {
            // Invalid credentials
            return false;
        }

        if (!is_array($data) || !array_key_exists('gamer_id', $data)) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Received invalid body validating password for ' . $this->get('id') . ': ' . $body);
            return false;
        }

        $this->setGamerID($data['gamer_id']);
        $this->setGamerSecret($data['gamer_secret']);
        $this->set('password', $data['gamer_secret']); // Set the internal password to the gamer_secret; as we don't use it locally.
        return true;
    }
}
