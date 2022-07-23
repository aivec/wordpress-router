<?php

use Aivec\WordPress\Routing\Errors;
use Aivec\WordPress\Routing\Middleware\JWT;

class JWTCest
{
    public function _before(FunctionalTester $I) {
        JWT::generateRSAKeyPair('/app/private-key.pem', '/app/pub-key.pem');
    }

    public function rs256KeyPairWorks(FunctionalTester $I) {
        $testp = ['itworks' => 'YES'];
        $res = $I->sendAjaxPostRequest('/wp-router/test/jwt', [
            'payload' => json_encode(['jwt' => JWT::encode($testp, (string)file_get_contents('/app/private-key.pem'))]),
        ]);

        $I->dontSeeResponseCodeIs(401);
        $I->dontSeeResponseContains(Errors::JWT_UNAUTHORIZED);
        $I->seeResponseEquals(json_encode($testp));
    }
}
