<?php

namespace Aivec\WordPress\Routing;

use Aivec\ResponseHandler\ErrorStore;
use Aivec\ResponseHandler\GenericError;

/**
 * Default routing errors
 */
class Errors extends ErrorStore
{
    const JWT_UNAUTHORIZED = 'JWTUnauthorized';

    /**
     * Adds errors
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function populate() {
        $emes = $this->getErrorCodeMap()[parent::UNAUTHORIZED]->message;

        $this->addError(new GenericError(
            self::JWT_UNAUTHORIZED,
            $this->getConstantNameByValue(self::JWT_UNAUTHORIZED),
            401,
            function ($message) {
                return $message;
            },
            $emes
        ));
    }
}
