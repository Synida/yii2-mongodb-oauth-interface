<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\model;

use yii\db\ActiveQueryInterface;
use yii\mongodb\ActiveRecord;
use common\models\User;

/**
 * Class OauthRefreshTokens
 * @package synida\yii2\mongodb\oauth\model
 */
class OauthRefreshTokens extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public static function collectionName()
    {
        return 'oauth_refresh_tokens';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['refresh_token', 'client_id', 'expires'], 'required'],
            [['user_id'], 'integer'],
            [['expires'], 'safe'],
            [['refresh_token'], 'string', 'max' => 40],
            [['client_id'], 'string', 'max' => 32],
            [['scope'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributes(): array
    {
        return [
            '_id',
            'refresh_token',
            'client_id',
            'user_id',
            'expires',
            'scope'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'refresh_token' => 'Refresh Token',
            'client_id' => 'Client ID',
            'user_id' => 'User ID',
            'expires' => 'Expires',
            'scope' => 'Scope',
        ];
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['_id' => 'user_id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getClient()
    {
        return $this->hasOne(OauthClients::class, ['client_id' => 'client_id']);
    }
}