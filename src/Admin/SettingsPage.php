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
     * Returns public key path given a JWT unique ID
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $jwtkeyid
     * @return string
     */
    public function getPubKeyPathForUniqueId($jwtkeyid) {
        $opts = $this->getOpts();
        $keys = !empty($opts[self::JWTKEYS_OPT][$jwtkeyid]) ? $opts[self::JWTKEYS_OPT][$jwtkeyid] : [];
        return !empty($keys['public_key']) ? (string)$keys['public_key'] : '';
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
     * Returns URL for the settings page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getPageUrl() {
        return admin_url("options-general.php?page={$this->page}");
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
                $this->getPageUrl(),
                $this->keyPairGeneratorSection()
            );
            $opts = $this->getOpts();
            foreach ($opts[self::JWTKEYS_OPT] as $uniqueid => $paths) {
                $pubkey = (string)file_get_contents($paths['public_key']);
                if (empty($pubkey)) {
                    $pubkey = __('Could not get file contents.', 'avcwpr');
                }

                ob_start();
                ?>
                <table class="form-table" role="presentation">
                    <input type="hidden" name="avcwpr_jwt_unique_id" value="<?php echo esc_attr($uniqueid); ?>" />
                    <tr>
                        <th scope="row">
                            <input
                                type="text"
                                name="avcwpr_pubkey_path"
                                id="<?php echo esc_attr($uniqueid); ?>_pubkey_path"
                                value="<?php echo esc_attr($paths['public_key']); ?>"
                                class="regular-text"
                            />
                        </th>
                        <td>
                            <?php
                            submit_button(
                                __('Update public key path.', 'avcwpr'),
                                'secondary',
                                $uniqueid . '_update',
                                false
                            );
                            ?>
                        </td>
                    </tr>
                </table>
                <p style="margin-top: 0;">
                    <?php
                    esc_html_e('Note that changing the public key path does not move the file for you.', 'avcwpr');
                    ?>
                </p>
                <?php
                $pathsection = (string)ob_get_clean();
                ob_start();
                ?>
                <div style="margin-top: 1rem;">
                    <input type="hidden" name="avcwpr_jwt_unique_id" value="<?php echo esc_attr($uniqueid); ?>" />
                    <?php
                    submit_button(
                        sprintf(__('Delete %s', 'avcwpr'), esc_html($uniqueid)),
                        'primary',
                        $uniqueid . '_delete',
                        false
                    );
                    ?>
                    <p>
                        <?php
                        esc_html_e(
                            'Note that deleting the key pair does not automatically remove the public key file.',
                            'avcwpr'
                        );
                        ?>
                    </p>
                </div>
                <?php
                $dsection = (string)ob_get_clean();
                ?>
                <div style="margin-bottom: 2rem;">
                    <h1><?php echo esc_html($uniqueid); ?></h1>
                    <hr>
                    <!-- <div style="margin-bottom: 1rem;"></div> -->
                    <?php
                    echo $this->router->createPostForm(
                        '/avcwpr/updatePubKeyPath',
                        $this->getPageUrl(),
                        $pathsection
                    );
                    ?>
                    <textarea
                        style="height: 300px; max-width: 600px; width: 100%;"
                        class="avcwpr_public_key"
                    ><?php echo trim($pubkey); ?></textarea>
                    <?php
                    echo $this->router->createPostForm(
                        '/avcwpr/deleteKeyPair',
                        $this->getPageUrl(),
                        $dsection
                    );
                    ?>
                </div>
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
        </table>
        <?php
        submit_button(__('Generate RSA key pair for JWT', 'avcwpr'), 'primary', 'genkeypair');
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
        if (empty((string)$_POST['avcwpr_public_key_path'])) {
            return;
        }

        $jwtkey = sanitize_key((string)$_POST['avcwpr_jwt_unique_id']);
        $pubpath = sanitize_text_field((string)$_POST['avcwpr_public_key_path']);

        // create key files
        $res = JWT::generateRSAKeyPair();

        file_put_contents($pubpath, $res['public_key']);

        $opts = $this->getOpts();
        $opts[self::JWTKEYS_OPT][$jwtkey] = [
            'public_key' => $pubpath,
        ];
        update_option($this->optkey, $opts);

        $tmpdir = rtrim(get_temp_dir(), '/');
        $zipname = "{$jwtkey}-jwt-rsa-private-key.zip";
        $zipf = "{$tmpdir}/{$zipname}";
        $zip = new \ZipArchive();
        $zip->open($zipf, \ZipArchive::CREATE);
        $zip->addFromString("{$jwtkey}-private-key.pem", $res['private_key']);
        $zip->addFromString("{$jwtkey}-private-key-base64.txt", $res['private_key_base64']);
        $zip->close();

        $zipdata = (string)file_get_contents($zipf);
        unlink($zipf);

        mb_http_output('UTF-8');
        set_time_limit(3600);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename={$zipname}");
        echo $zipdata;
        exit;
    }

    /**
     * Updates the public key path for a JWT key pair
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function updatePubKeyPath() {
        if (empty((string)$_POST['avcwpr_pubkey_path'])) {
            return;
        }
        if (empty((string)$_POST['avcwpr_jwt_unique_id'])) {
            return;
        }

        $jwtkey = sanitize_key((string)$_POST['avcwpr_jwt_unique_id']);
        $pubpath = sanitize_text_field((string)$_POST['avcwpr_pubkey_path']);

        $opts = $this->getOpts();
        if (!isset($opts[self::JWTKEYS_OPT][$jwtkey])) {
            return;
        }

        $opts[self::JWTKEYS_OPT][$jwtkey]['public_key'] = $pubpath;
        update_option($this->optkey, $opts);
    }

    /**
     * Deletes a JWT key pair
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function deleteKeyPair() {
        if (empty((string)$_POST['avcwpr_jwt_unique_id'])) {
            return;
        }

        $jwtkey = sanitize_key((string)$_POST['avcwpr_jwt_unique_id']);

        $opts = $this->getOpts();
        if (!isset($opts[self::JWTKEYS_OPT][$jwtkey])) {
            return;
        }

        unset($opts[self::JWTKEYS_OPT][$jwtkey]);
        update_option($this->optkey, $opts);
    }
}
