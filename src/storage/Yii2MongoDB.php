<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\storage;

use Exception;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\JwtBearerInterface;
use OAuth2\Storage\PublicKeyInterface;
use OAuth2\Storage\RefreshTokenInterface;
use OAuth2\Storage\UserCredentialsInterface;
use synida\yii2\mongodb\oauth\model\OauthAccessTokens;
use synida\yii2\mongodb\oauth\model\OauthRefreshTokens;
use Yii;
use yii\mongodb\Collection;
use yii\mongodb\Connection;
use yii\mongodb\Database;

/**
 * Class Yii2MongoDB
 * @package synida\yii2\mongodb\oauth\storage
 *
 * @property string $dns
 * @property string $connection
 * @property Database $database
 * @property array $config
 */
class Yii2MongoDB implements
    AuthorizationCodeInterface,
    UserCredentialsInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    RefreshTokenInterface,
    JwtBearerInterface,
    PublicKeyInterface
{
    public $dsn;

    public $connection = 'mongodb';

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var array
     */
    protected $config;

    /**
     * Yii2MongoDB constructor.
     *
     * @param null $connection
     * @param array $config
     * @throws \Exception
     */
    public function __construct($connection = null, array $config = [])
    {
        if ($connection === null) {
            if (!empty($this->connection)) {
                /** @var Connection $connection */
                $connection = Yii::$app->get($this->connection);

                if (!$connection->getIsActive()) {
                    $connection->open();
                }

                $this->database = $connection->getDatabase();
            } else {
                throw new Exception("Initialize by passing MongoDb configuration not implemented.");
            }
        }

        $this->config = array_merge([
            'client_table' => 'oauth_clients',
            'access_token_table' => 'oauth_access_tokens',
            'refresh_token_table' => 'oauth_refresh_tokens',
            'code_table' => 'oauth_authorization_codes',
            'user_table' => 'oauth_users',
            'jwt_table'  => 'oauth_jwt',
            'scope_table'  => 'oauth_scopes',
            'public_key_table'  => 'oauth_public_keys',
        ], $config);
    }

    /**
     * Select particular Oauth2 collection from config
     *
     * @param string $name
     * @return Collection
     */
    protected function collection(string $name): Collection
    {
        return $this->database->getCollection($this->config[$name]);
    }

    /**
     * Make sure that the client credentials is valid.
     *
     * @inheritdoc
     */
    public function checkClientCredentials($client_id, $client_secret = null): bool
    {
        $result = $this->collection('client_table')->findOne(['client_id' => $client_id]);

        return $result && $result['client_secret'] === $client_secret;
    }

    /**
     * Determine if the client is a "public" client, and therefore
     * does not require passing credentials for certain grant types
     *
     * @inheritdoc
     */
    public function isPublicClient($client_id): bool
    {
        if (!$result = $this->collection('client_table')->findOne(['client_id' => $client_id])) {
            return false;
        }

        return empty($result['client_secret']);
    }

    /**
     * Get client details corresponding client_id.
     *
     * @inheritdoc
     */
    public function getClientDetails($client_id)
    {
        $result = $this->collection('client_table')->findOne(['client_id' => $client_id]);

        return is_null($result) ? false : $result;
    }

    /**
     * Store the supplied client details to storage.
     *
     * @param string $client_id
     * @param string|null $client_secret
     * @param string|null $redirect_uri
     * @param string|null $grant_types
     * @param string|null $scope
     * @param string|null $user_id
     * @return bool
     * @throws Exception
     */
    public function setClientDetails(
        $client_id,
        $client_secret = null,
        $redirect_uri = null,
        $grant_types = null,
        $scope = null,
        $user_id = null
    ): bool {
        if ($this->getClientDetails($client_id)) {
            return $this->collection('client_table')->update(
                ['client_id' => $client_id],
                [
                    'client_secret' => $client_secret,
                    'redirect_uri' => $redirect_uri,
                    'grant_types' => $grant_types,
                    'scope' => $scope,
                    'user_id' => $user_id,
                ]
                ) > 0;
        }
        $client = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_types' => $grant_types,
            'scope' => $scope,
            'user_id' => $user_id,
        ];
        try {
            $this->collection('client_table')->insert($client);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Look up the supplied oauth_token from storage.
     *
     * @inheritdoc
     */
    public function getAccessToken($access_token)
    {
        $token = $this->collection('access_token_table')->findOne(compact('access_token'));

        return is_null($token) ? false : $token;
    }

    /**
     * @param $client_id
     * @param $user_id
     * @param $scope
     * @return OauthAccessTokens|null
     */
    public function findUserToken($client_id, $user_id, $scope)
    {
        return OauthAccessTokens::find()
            ->where(compact('client_id', 'user_id', 'scope'))
            ->andWhere(['expires' => ['$gt' => time()]])
            ->orderBy(['expires' => SORT_ASC])
            ->one();
    }

    /**
     * @param $client_id
     * @param $user_id
     * @param $scope
     * @return OauthRefreshTokens|null
     */
    public function findRefreshToken($client_id, $user_id, $scope)
    {
        return OauthRefreshTokens::find()
            ->where(compact('client_id', 'user_id', 'scope'))
            ->andWhere(['expires' => ['$gt' => time()]])
            ->orderBy(['expires' => SORT_ASC])
            ->one();
    }

    /**
     * Store the supplied access token values to storage.
     *
     * @inheritdoc
     * @throws Exception
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null): bool
    {
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            return $this->collection('access_token_table')->update(
                compact('access_token'),
                [
                    'client_id' => $client_id,
                    'expires' => $expires,
                    'user_id' => $user_id,
                    'scope' => $scope
                ]
                ) > 0;
        }

        try {
            $this->collection('access_token_table')->insert([
                'access_token' => $access_token,
                'client_id' => $client_id,
                'expires' => $expires,
                'user_id' => $user_id,
                'scope' => $scope
            ]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Remove access_token from the storage
     *
     * @param string $access_token
     * @return bool
     * @throws Exception
     */
    public function unsetAccessToken($access_token): bool
    {
        return $this->collection('access_token_table')->remove(compact('access_token')) > 0;
    }

    /**
     * Fetch authorization code data (probably the most common grant type).
     *
     * @inheritdoc
     */
    public function getAuthorizationCode($code)
    {
        $code = $this->collection('code_table')->findOne(['authorization_code' => $code]);

        return is_null($code) ? false : $code;
    }

    /**
     * Take the provided authorization code values and store them somewhere.
     *
     * @inheritdoc
     * @throws Exception
     */
    public function setAuthorizationCode(
        $code,
        $client_id,
        $user_id,
        $redirect_uri,
        $expires,
        $scope = null,
        $id_token = null
    ): bool {
        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            return $this->collection('code_table')->update(
                ['authorization_code' => $code],
                [
                    'client_id' => $client_id,
                    'user_id' => $user_id,
                    'redirect_uri' => $redirect_uri,
                    'expires' => $expires,
                    'scope' => $scope,
                    'id_token' => $id_token,
                ]
                ) > 0;
        }

        try {
            $this->collection('code_table')->insert([
                'authorization_code' => $code,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'redirect_uri' => $redirect_uri,
                'expires' => $expires,
                'scope' => $scope,
                'id_token' => $id_token
            ]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Once an Authorization Code is used, it must be expired
     *
     * @inheritdoc
     * @throws Exception
     */
    public function expireAuthorizationCode($code): bool
    {
        return $this->collection('code_table')->remove([
            'authorization_code' => $code
        ]) > 0;
    }

    /**
     * Grant refresh access tokens.
     *
     * @inheritdoc
     */
    public function getRefreshToken($refresh_token)
    {
        $token = $this->collection('refresh_token_table')->findOne(compact('refresh_token'));

        return is_null($token) ? false : $token;
    }

    /**
     * Take the provided refresh token values and store them somewhere.
     *
     * @inheritdoc
     */
    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null): bool
    {
        try {
            $this->collection('refresh_token_table')->insert([
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'expires' => $expires,
                'scope' => $scope
            ]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Expire a used refresh token.
     *
     * @inheritdoc
     * @throws Exception
     */
    public function unsetRefreshToken($refresh_token): bool
    {
        return $this->collection('refresh_token_table')->remove(compact('refresh_token')) > 0;
    }

    /**
     * Get user collection by email
     *
     * @param $email
     * @return array|bool|null
     */
    public function getUser($email)
    {
        $result = $this->collection('user_table')->findOne(['email' => strtolower($email)]);

        return is_null($result) ? false : $result;
    }

    /**
     * Store user data
     *
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @return bool
     * @throws Exception
     */
    public function setUser($email, $password, $firstName = null, $lastName = null): bool
    {
        if ($this->getUser($email)) {
            return $this->collection('user_table')->update(
                compact('email'),
                ['password' => $password, 'first_name' => $firstName, 'last_name' => $lastName]
                ) > 0;
        }

        try {
            $this->collection('user_table')->insert([
                'email' => strtolower($email),
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the public key associated with a client_id
     *
     * @inheritdoc
     */
    public function getClientKey($client_id, $subject)
    {
        $result = $this->collection('jwt_table')->findOne([
            'client_id' => $client_id,
            'subject' => $subject
        ]);

        return is_null($result) ? false : $result['key'];
    }

    /**
     * Specify where the OAuth2 Server should get public/private key information
     *
     * @inheritdoc
     */
    public function getPublicKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(['client_id' => $client_id]);
            if ($result) {
                return $result['public_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(['client_id' => null]);
        return is_null($result) ? false : $result['public_key'];
    }

    /**
     * Specify where the OAuth2 Server should get public/private key information
     *
     * @inheritdoc
     */
    public function getPrivateKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(['client_id' => $client_id]);
            if ($result) {
                return $result['private_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(['client_id' => null]);
        return is_null($result) ? false : $result['private_key'];
    }

    /**
     * Specify where the OAuth2 Server should get public/private key information
     *
     * @inheritdoc
     */
    public function getEncryptionAlgorithm($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(['client_id' => $client_id]);
            if ($result) {
                return $result['encryption_algorithm'];
            }
        }

        $result = $this->collection('key_table')->findOne(['client_id' => null]);
        return is_null($result) ? 'RS256' : $result['encryption_algorithm'];
    }

    /**
     * Checking password
     *
     * @param array $user
     * @param string $password
     * @return bool
     */
    protected function checkPassword($user, $password): bool
    {
        return $user['password'] === $password;
    }

    /**
     * @inheritdoc
     */
    public function checkUserCredentials($email, $password): bool
    {
        $user = $this->getUser($email);

        return $user && $this->checkPassword($user, $password);
    }

    /**
     * @inheritdoc
     */
    public function getUserDetails($email)
    {
        $user = $this->getUser($email);
        if ($user) {
            $user['user_id'] = $user['email'];
        }

        return $user;
    }

    /**
     * Get the scope associated with this client
     *
     * @inheritdoc
     */
    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        return $clientDetails['scope'] ?? null;
    }

    /**
     * Check restricted grant types of corresponding client identifier.
     *
     * @inheritdoc
     */
    public function checkRestrictedGrantType($client_id, $grant_type): bool
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grantTypes = explode(' ', $details['grant_types']);
            return in_array($grant_type, $grantTypes, true);
        }
        // if grant_types are not defined, then none are restricted
        return true;
    }

    /**
     * Get a jti (JSON token identifier) by matching against the client_id, subject, audience and expiration.
     *
     * @inheritdoc
     * @throws \Exception
     */
    public function getJti($client_id, $subject, $audience, $expiration, $jti)
    {
        throw new \Exception('getJti() for the MongoDB driver is currently unimplemented.');
    }

    /**
     * Store a used jti so that we can check against it to prevent replay attacks.
     *
     * @inheritdoc
     * @throws \Exception
     */
    public function setJti($client_id, $subject, $audience, $expiration, $jti)
    {
        throw new \Exception('setJti() for the MongoDB driver is currently unimplemented.');
    }
}
