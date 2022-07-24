<?php

use Aivec\WordPress\Routing\Errors;
use Aivec\WordPress\Routing\Middleware\JWT;

class JWTCest
{
    public function _before(FunctionalTester $I) {
        $res = JWT::generateRSAKeyPair();
        file_put_contents('/app/private-key.pem', $res['private_key']);
        file_put_contents('/app/pub-key.pem', $res['public_key']);
    }

    public function rs256KeyPairWorks(FunctionalTester $I) {
        $testp = ['itworks' => 'YES'];
        $I->sendAjaxPostRequest('/wp-router/test/jwt', [
            'payload' => json_encode(['jwt' => JWT::encode($testp, (string)file_get_contents('/app/private-key.pem'))]),
        ]);

        $I->dontSeeResponseCodeIs(401);
        $I->dontSeeResponseContains(Errors::JWT_UNAUTHORIZED);
        $I->seeResponseEquals(json_encode($testp));
    }
}
