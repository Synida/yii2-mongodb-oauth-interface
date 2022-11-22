<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\model;

/**
 * Class OauthAuthorizationCodes
 * @package synida\yii2\mongodb\oauth\model
 */
class OauthAuthorizationCodes
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'oauth_authorization_codes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['authorization_code', 'client_id', 'redirect_uri', 'expires'], 'required'],
            [['user_id'], 'integer'],
            [['expires'], 'safe'],
            [['authorization_code'], 'string', 'max' => 40],
            [['client_id'], 'string', 'max' => 32],
            [['redirect_uri'], 'string', 'max' => 1000],
            [['scope'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'authorization_code' => 'Authorization Code',
            'client_id' => 'Client ID',
            'user_id' => 'User ID',
            'redirect_uri' => 'Redirect Uri',
            'expires' => 'Expires',
            'scope' => 'Scope',
        ];
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getClient()
    {
        return $this->hasOne(OauthClients::class, ['client_id' => 'client_id']);
    }
}