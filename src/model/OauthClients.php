<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\model;

use yii\db\ActiveQueryInterface;
use yii\mongodb\ActiveRecord;

/**
 * Class OauthClients
 * @package synida\yii2\mongodb\oauth\model
 * @description This is the model class for table "oauth_clients".
 *
 * @property string $client_id
 * @property string $client_secret
 * @property string $redirect_uri
 * @property string $grant_types
 * @property string $scope
 * @property integer $user_id
 *
 * @property OauthAccessTokens[] $oauthAccessTokens
 * @property OauthAuthorizationCodes[] $oauthAuthorizationCodes
 * @property OauthRefreshTokens[] $oauthRefreshTokens
 */
class OauthClients extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function collectionName()
    {
        return 'oauth_clients';
    }

    /**
     * @inheritDoc
     */
    public function attributes(): array
    {
        return [
            '_id',
            'client_id',
            'client_secret',
            'redirect_uri',
            'grant_types',
            'user_id',
            'scope'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['client_id', 'client_secret', 'redirect_uri', 'grant_types'], 'required'],
            [['user_id'], 'integer'],
            [['client_id', 'client_secret'], 'string', 'max' => 32],
            [['redirect_uri'], 'string', 'max' => 1000],
            [['grant_types'], 'string', 'max' => 100],
            [['scope'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'redirect_uri' => 'Redirect Uri',
            'grant_types' => 'Grant Types',
            'scope' => 'Scope',
            'user_id' => 'User ID',
        ];
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getOauthAccessTokens()
    {
        return $this->hasMany(OauthAccessTokens::class, ['client_id' => 'client_id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getOauthAuthorizationCodes()
    {
        return $this->hasMany(OauthAuthorizationCodes::class, ['client_id' => 'client_id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getOauthRefreshTokens()
    {
        return $this->hasMany(OauthRefreshTokens::class, ['client_id' => 'client_id']);
    }
}