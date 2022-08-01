<?php

class SettingsCest
{
    public $uniqueId = 'deploy';

    public function _before(AcceptanceTester $I) {
        $I->loginAsAdmin();
        $I->amOnPage('/wp-admin/options-general.php?page=avcwpr_settings_page_test');
    }

    public function settingsPageIsDisplayed(AcceptanceTester $I) {
        $I->canSee('Public key path');
    }

    public function canGenerateKeyPair(AcceptanceTester $I) {
        $I->fillField('#avcwpr_jwt_unique_id', $this->uniqueId);
        $I->fillField('#avcwpr_public_key_path', '/var/www/html/test-public-key.pem');
        $I->click('#genkeypair');

        $I->amOnPage('/wp-admin/options-general.php?page=avcwpr_settings_page_test');
        $I->canSeeElement('.avcwpr_public_key');
        $I->dontSee('Could not get file contents');
    }

    public function canUpdatePublicKeyPath(AcceptanceTester $I) {
        $I->fillField("#{$this->uniqueId}_pubkey_path", '/new/path/test-public-key.pem');
        $I->click("#{$this->uniqueId}_update");
        $I->seeInField("#{$this->uniqueId}_pubkey_path", '/new/path/test-public-key.pem');
        $I->see('Could not get file contents');
    }

    public function canDeleteKeyPair(AcceptanceTester $I) {
        $I->click("#{$this->uniqueId}_delete");
        $I->dontSeeElement("#{$this->uniqueId}_update");
        $I->dontSeeElement("#{$this->uniqueId}_delete");
        $I->dontSee($this->uniqueId);
    }
}
