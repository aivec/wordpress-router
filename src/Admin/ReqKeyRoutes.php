<?php

namespace Aivec\WordPress\Routing\Admin;

use Aivec\WordPress\Routing\RequestKeyRouter;
use Aivec\WordPress\Routing\WordPressRouteCollector;

/**
 * Declares all routes
 */
class ReqKeyRoutes extends RequestKeyRouter
{
    /**
     * SettingsPage instance
     *
     * @var SettingsPage
     */
    private $sp;

    /**
     * Instantiates `RequestKeyRouter`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param SettingsPage $sp
     */
    public function __construct(SettingsPage $sp) {
        parent::__construct("/{$sp->key}", "{$sp->key}_nonce_key", "{$sp->key}_nonce_name");
        $this->sp = $sp;
    }

    /**
     * Declares routes
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param WordPressRouteCollector $r
     * @return void
     */
    public function declareRoutes(WordPressRouteCollector $r) {
        $r->addAdministratorRoute('POST', '/avcwpr/generateRSAKeyPair', [$this->sp, 'generateRSAKeyPair']);
    }
}
