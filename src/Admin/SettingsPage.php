<?php

namespace Aivec\WordPress\Routing\Admin;

use Aivec\WordPress\Routing\I18n;
use Aivec\WordPress\Routing\Middleware\JWT;

/**
 * Methods for creating a settings page for JWT settings
 */
class SettingsPage
{
    const PAGE_PREFIX = 'avcwpr_settings_page_';
    const JWTKEYS_OPT = 'jwtkeys';

    /**
     * Page unique key
     *
     * @var string
     */
    public $key;

    /**
     * Page name prefix
     *
     * @var string
     */
    public $name;

    /**
     * Option key
     *
     * @var string
     */
    public $optkey;

    /**
     * Settings page slug
     *
     * @var string
     */
    private $page;

    /**
     * Router instance
     *
     * @var ReqKeyRoutes
     */
    private $router;

    /**
     * Constructs member vars
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $key
     * @param string $name
     * @return void
     */
    public function __construct($key, $name) {
        $this->key = $key;
        $this->name = $name;
        $this->optkey = 'avcwpr_' . $key;
        $this->page = self::PAGE_PREFIX . $key;

        I18n::loadTranslations();
        add_action('init', function () {
            $this->router = new ReqKeyRoutes($this);
            $this->router->dispatcher->listen();
        });
    }

    /**
     * Returns options
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getOpts() {
        return get_option($this->optkey, [self::JWTKEYS_OPT => []]);
    }

    /**
     * Creates settings page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function createSettingsPage() {
        add_action('admin_menu', [$this, 'registerSettingsPage']);
    }

    /**
     * Adds settings page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function registerSettingsPage() {
        add_options_page(
            $this->name . __('Router Settings', 'avcwpr'),
            $this->name . __('Router Settings', 'avcwpr'),
            'manage_options',
            $this->page,
            [$this, 'addSettingsPage']
        );
    }

    /**
     * Adds `<name> Router Settings` page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function addSettingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo $this->name . __('Router Settings', 'avcwpr'); ?></h1>
            <?php
            echo $this->router->createPostForm(
                '/avcwpr/generateRSAKeyPair',
                admin_url("admin.php?page={$this->page}"),
                $this->keyPairGeneratorSection()
            );
            $opts = $this->getOpts();
            foreach ($opts[self::JWTKEYS_OPT] as $uniqueid => $paths) {
                $privkey = (string)file_get_contents($paths['private_key']);
                $pubkey = (string)file_get_contents($paths['public_key']);
                if (empty($privkey)) {
                    $privkey = __('Could not get file contents.', 'avcwpr');
                }
                if (empty($pubkey)) {
                    $pubkey = __('Could not get file contents.', 'avcwpr');
                }
                ?>
                <h4><?php echo esc_html($uniqueid); ?></h4>
                <hr>
                <h5><?php esc_html_e('Private key (base64)', 'avcwpr'); ?></h5>
                <textarea class="avcwpr_private_key_base64"><?php echo base64_encode($privkey); ?></textarea>
                <h5><?php esc_html_e('Private key', 'avcwpr'); ?></h5>
                <textarea class="avcwpr_private_key"><?php echo $privkey; ?></textarea>
                <h5><?php esc_html_e('Public key', 'avcwpr'); ?></h5>
                <textarea class="avcwpr_public_key"><?php echo $pubkey; ?></textarea>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Adds RSA key generator fields
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function keyPairGeneratorSection() {
        ob_start();
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="avcwpr_jwt_unique_id">
                        <?php esc_html_e('Unique identifier', 'avcwpr'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        name="avcwpr_jwt_unique_id"
                        id="avcwpr_jwt_unique_id"
                        value=""
                        class="regular-text"
                    />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="avcwpr_private_key_path">
                        <?php esc_html_e('Private key path', 'avcwpr'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        name="avcwpr_private_key_path"
                        id="avcwpr_private_key_path"
                        value=""
                        class="regular-text"
                    />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="avcwpr_public_key_path">
                        <?php esc_html_e('Public key path', 'avcwpr'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        name="avcwpr_public_key_path"
                        id="avcwpr_public_key_path"
                        value=""
                        class="regular-text"
                    />
                </td>
            </tr>
            <tr>
                <td><?php submit_button(__('Generate RSA key pair for JWT', 'avcwpr'), 'primary', 'genkeypair'); ?></td>
            </tr>
        </table>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Handles RSA key pair generator route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function generateRSAKeyPair() {
        if (empty((string)$_POST['avcwpr_jwt_unique_id'])) {
            return;
        }
        if (empty((string)$_POST['avcwpr_private_key_path'])) {
            return;
        }
        if (empty((string)$_POST['avcwpr_public_key_path'])) {
            return;
        }

        $jwtkey = sanitize_key((string)$_POST['avcwpr_jwt_unique_id']);
        $privpath = sanitize_text_field((string)$_POST['avcwpr_private_key_path']);
        $pubpath = sanitize_text_field((string)$_POST['avcwpr_public_key_path']);

        // create key files
        JWT::generateRSAKeyPair($privpath, $pubpath);

        $opts = $this->getOpts();
        $opts[self::JWTKEYS_OPT][$jwtkey] = [
            'private_key' => $privpath,
            'public_key' => $pubpath,
        ];
        update_option($this->optkey, $opts);
    }
}
