<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\model;

use common\models\oauth2\OauthClients;
use yii\mongodb\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "oauth_access_tokens".
 *
 * @property string $access_token
 * @property string $client_id
 * @property integer $user_id
 * @property string $expires
 * @property string $scope
 *
 * @property OauthClients $client
 * @property IdentityInterface user
 */
class OauthAccessTokens extends ActiveRecord
{
    /**
     * Declares the name of the Mongo collection associated with this AR class.
     *
     * Collection name can be either a string or array:
     *  - if string considered as the name of the collection inside the default database.
     *  - if array - first element considered as the name of the database, second - as
     *    name of collection inside that database
     *
     * By default, this method returns the class name as the collection name by calling [[Inflector::camel2id()]].
     * For example, 'Customer' becomes 'customer', and 'OrderItem' becomes
     * 'order_item'. You may override this method if the collection is not named after this convention.
     * @return string the collection name
     */
    public static function collectionName(): string
    {
        return 'oauth_access_tokens';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['access_token', 'client_id', 'expires'], 'required'],
            [['user_id'], 'integer'],
            [['expires'], 'safe'],
            [['access_token'], 'string', 'max' => 40],
            [['client_id'], 'string', 'max' => 32],
            [['scope'], 'string', 'max' => 2000]
        ];
    }


    /**
     * Returns the list of all attribute names of the model.
     * This method must be overridden by child classes to define available attributes.
     * Note: primary key attribute "_id" should be always present in returned array.
     * For example:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'name', 'address', 'status'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes(): array
    {
        return [
            '_id',
            'access_token',
            'user_id',
            'expires',
            'client_id',
            'scope',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'access_token' => 'Access Token',
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
