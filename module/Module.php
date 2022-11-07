<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\module;

use \filsh\yii2\oauth2server\Module as OAuthServerModule;
use filsh\yii2\oauth2server\Server;
use ReflectionException;
use synida\yii2\mongodb\oauth\response\AccessTokenResponseType;
use synida\yii2\mongodb\oauth\storage\Yii2MongoDB;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

/**
 * Class Module
 * @package synida\yii2\mongodb\oauth\module
 */
class Module extends OAuthServerModule
{
    /**
     * Gets Oauth2 Server
     *
     * @return Server
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function getServer()
    {
        $server = parent::getServer();

        /** @var Yii2MongoDB $accessTokenStorage */
        $accessTokenStorage = $server->getStorage('access_token');

        /** @var Yii2MongoDB $refreshTokenStorage */
        $refreshTokenStorage = $server->getStorage('refresh_token');

        $accessTokenResponseType = new AccessTokenResponseType($accessTokenStorage, $refreshTokenStorage, [
            'use_jwt_access_tokens' => $this->useJwtToken,
            'token_param_name' => $this->tokenParamName,
            'access_lifetime' => $this->tokenAccessLifetime,
        ]);
        $server->addResponseType($accessTokenResponseType, 'token');

        return $server;
    }
}