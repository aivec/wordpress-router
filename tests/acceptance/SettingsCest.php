<?php

class SettingsCest
{
    public function _before(AcceptanceTester $I) {
        $I->loginAsAdmin();
        $I->amOnPage('/wp-admin/options-general.php?page=avcwpr_settings_page_test');
    }

    public function settingsPageIsDisplayed(AcceptanceTester $I) {
        $I->canSee('Public key path');
    }

    public function canSubmitGenKeyPairForm(AcceptanceTester $I) {
        $I->click('#genkeypair');
        $I->canSee('Public key path');
    }

    public function canGenerateKeyPair(AcceptanceTester $I) {
        $I->fillField('#avcwpr_jwt_unique_id', 'deploy');
        $I->fillField('#avcwpr_public_key_path', '/var/www/html/test-public-key.pem');
        $I->click('#genkeypair');

        $I->amOnPage('/wp-admin/options-general.php?page=avcwpr_settings_page_test');
        $I->canSeeElement('.avcwpr_public_key');
        $I->dontSee('Could not get file contents');
    }
}
