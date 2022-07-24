<?php

namespace Aivec\WordPress\Routing\Middleware;

use Firebase\JWT\JWT as FirebaseJWT;

/**
 * JWT authentication middleware
 */
class JWT
{
    /**
     * SHA256 public key string
     *
     * @var string
     */
    private $publicKey;

    /**
     * Sets `publicKey` member var with the contents of the public key file
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $publicKeyPath Absolute path to public key file
     * @return void
     */
    public function __construct($publicKeyPath) {
        $this->publicKey = (string)file_get_contents($publicKeyPath);
    }

    /**
     * Encodes payload for JWT protected APIs
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array  $payload
     * @param string $privateKey
     * @return string
     */
    public static function encode($payload, $privateKey) {
        return FirebaseJWT::encode($payload, $privateKey, 'RS256');
    }

    /**
     * Decodes a JWT payload
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $payload
     * @return array
     */
    public function decode($payload) {
        return (array)FirebaseJWT::decode($payload, $this->publicKey, ['RS256']);
    }

    /**
     * Generates RSA key pair
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public static function generateRSAKeyPair() {
        $res = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey['key'];

        return [
            'private_key_base64' => trim(base64_encode(trim($privKey))),
            'private_key' => trim($privKey),
            'public_key' => trim($pubKey),
        ];
    }
}
