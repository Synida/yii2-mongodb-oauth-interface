<?php
/**
 * Created by Synida Pry.
 * Copyright © 2022. All rights reserved.
 */

namespace synida\yii2\mongodb\oauth\filter;

use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * Class ExtendedHttpBearerAuth
 * @package synida\yii2\mongodb\oauth\filter
 */
class ExtendedHttpBearerAuth extends HttpBearerAuth
{
    /**
     * {@inheritdoc}
     */
    public function authenticate($user, $request, $response)
    {
        // Fix for Barer in lowercase
        $authorization = Yii::$app->request->headers->get($this->header);
        if ($authorization && ctype_lower($authorization[0])) {
            Yii::$app->request->headers->set($this->header, ucfirst($authorization));
        }

        return parent::authenticate($user, $request, $response);
    }
}