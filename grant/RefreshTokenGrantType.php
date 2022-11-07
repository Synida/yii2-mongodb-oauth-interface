<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\grant;

use OAuth2\GrantType\RefreshToken;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use synida\yii2\mongodb\oauth\storage\Yii2MongoDB;

/**
 * Class RefreshTokenGrantType
 * @package synida\yii2\mongodb\oauth\grant
 *
 * @property array $refreshToken
 * @property Yii2MongoDB $storage
 */
class RefreshTokenGrantType extends RefreshToken
{
    /**
     * Contains the refresh token
     *
     * @var array
     */
    protected $refreshToken;

    /**
     * MongoDB storage
     *
     * @var Yii2MongoDB
     */
    protected $storage;

    /**
     * @inheritDoc
     */
    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {
        $result = parent::validateRequest($request, $response);

        // store the refresh token locally, so we can delete it when a new refresh token is generated
        $this->refreshToken = $this->storage->getRefreshToken($request->request("refresh_token"));

        return $result;
    }

    /**
     * @inheritDoc
     * @param AccessTokenInterface $accessToken
     * @param $client_id
     * @param $user_id
     * @param $scope
     * @return mixed
     * @throws \Exception
     */
    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        $issueNewRefreshToken = $this->config['always_issue_new_refresh_token'];

        // Unset old refresh token
        if ($this->config['unset_refresh_token_after_use']) {
            $this->storage->unsetRefreshToken($this->refreshToken['refresh_token']);
        }

        // Remove old token if it exists
        $oldToken = $this->storage->findUserToken($client_id, $user_id, $scope);
        if ($oldToken) {
            $this->storage->unsetAccessToken($oldToken->access_token);
        }

        // Handle the creation of access token, also issue refresh token if supported / desirable.
        return $accessToken->createAccessToken($client_id, $user_id, $scope, $issueNewRefreshToken);
    }
}