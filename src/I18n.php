<?php

namespace Aivec\WordPress\Routing;

/**
 * Handles loading translations
 */
class I18n
{
    const DOMAIN = 'avcwpr';

    /**
     * Loads translations
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public static function loadTranslations() {
        $mopath = __DIR__ . '/languages/' . self::DOMAIN . '-' . get_locale() . '.mo';
        if (file_exists($mopath)) {
            load_textdomain(self::DOMAIN, $mopath);
            return;
        }
        load_textdomain(self::DOMAIN, __DIR__ . '/languages/' . self::DOMAIN . '-en.mo');
    }
}
