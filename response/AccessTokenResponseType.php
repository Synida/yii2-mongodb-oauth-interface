<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\response;

use OAuth2\ResponseType\AccessToken;
use synida\yii2\mongodb\oauth\storage\Yii2MongoDB;

/**
 * Class AccessTokenResponseType
 * @package synida\yii2\mongodb\oauth\response
 */
class AccessTokenResponseType extends AccessToken
{
    /**
     * MongoDB storage
     *
     * @var Yii2MongoDB
     */
    protected $tokenStorage;

    /**
     * @inheritDoc
     */
    public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true)
    {
        $token = $this->tokenStorage->findUserToken($client_id, $user_id, $scope);

        if (!$token) {
            // Handle the creation of access token, also issue refresh token if supported / desirable.
            return parent::createAccessToken($client_id, $user_id, $scope, $includeRefreshToken);
        }

        $accessToken = [
            "access_token" => $token->access_token,
            "expires_in" => $token->expires,
            "token_type" => $this->config['token_type'],
            "scope" => $token->scope
        ];

        if ($includeRefreshToken && $this->refreshStorage) {
            $refreshToken = $this->tokenStorage->findRefreshToken($client_id, $user_id, $scope);

            if ($refreshToken) {
                $accessToken["refresh_token"] = $refreshToken->refresh_token;
            } else {
                // Generates an unique refresh token
                $refreshToken = $this->generateRefreshToken();
                $expires = $this->config['refresh_token_lifetime'] > 0
                    ? time() + $this->config['refresh_token_lifetime']
                    : 0;

                // Take the provided refresh token values and store them somewhere.
                $this->refreshStorage->setRefreshToken($refreshToken, $client_id, $user_id, $expires, $scope);
                $accessToken["refresh_token"] = $refreshToken;
            }
        }

        return $accessToken;
    }
}