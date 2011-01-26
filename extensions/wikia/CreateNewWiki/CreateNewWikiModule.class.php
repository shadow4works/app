<?php
class CreateNewWikiModule extends Module {
	
	// global imports
	var $IP;
	var $wgUser;
	var $wgLanguageCode;
	var $wgOasisThemes;
	var $wgSitename;
	
	// form fields
	var $aCategories;
	var $aTopLanguages;
	var $aLanguages;
	
	// form field values
	var $wikiName;
	var $wikiDomain;
	var $wikiLanguage;
	var $wikiCategory;
	
	// state variables
	var $currentStep;
	var $skipWikiaPlus;

	public function executeIndex() {
		global $wgSuppressWikiHeader, $wgSuppressPageHeader, $wgSuppressFooter, $wgSuppressAds, $fbOnLoginJsOverride, $wgRequest;
		wfProfileIn( __METHOD__ );
		
		// hide some default oasis UI things
		$wgSuppressWikiHeader = true;
		$wgSuppressPageHeader = true;
		$wgSuppressFooter = false;
		$wgSuppressAds = true;
		
		// form field values
		$hubs = WikiFactoryHub::getInstance();
		$this->aCategories = $hubs->getCategories();
		$useLang = $wgRequest->getVal('uselang');
		$this->wikiLanguage = empty($this->wikiLanguage) ? $useLang: $this->wikiLanguage;
		$this->wikiLanguage = empty($useLang) ? $this->wgLanguageCode : $useLang;  // precedence: selected form field, uselang, default wiki lang
		
		$this->aTopLanguages = explode(',', wfMsg('autocreatewiki-language-top-list'));
		$this->aLanguages = wfGetFixedLanguageNames();
		asort($this->aLanguages);
		
		// facebook callback overwrite on login.  CreateNewWiki re-uses current login stuff.
		$fbOnLoginJsOverride = 'WikiBuilder.fbLoginCallback()';
		
		// fbconnected means user has gone through step 2 to login via facebook.  
		// Therefore, we need to reload some values and start at the step after signup/login
		$fbconnected = $wgRequest->getVal('fbconnected');
		if(!empty($fbconnected) && $fbconnected === '1') {
			$this->executeLoadState();
			$this->currentStep = 'DescWiki';
		} else {
			$this->currentStep = '';
		}
		
		// If not english, skip Wikia Plus signup step
		$this->skipWikiaPlus = $this->wikiLanguage != 'en';
		
		wfProfileOut( __METHOD__ );
	}
	
	/**
	 * Ajax call to validate domain.
	 * Called via moduleproxy
	 */
	public function executeCheckDomain() {
		wfProfileIn(__METHOD__);
		global $wgRequest;
		
		$name = $wgRequest->getVal('name');
		$lang = $wgRequest->getVal('lang');
		$type  = $wgRequest->getVal('type');
		
		$this->response = AutoCreateWiki::checkDomainIsCorrect($name, $lang, $type);
		
		wfProfileOut(__METHOD__);
	}
	
	/**
	 * Ajax call for validate wiki name.
	 */
	public function executeCheckWikiName() {
		wfProfileIn(__METHOD__);
		global $wgRequest;
		
		$name = $wgRequest->getVal('name');
		$lang = $wgRequest->getVal('lang');
		
		$this->response = AutoCreateWiki::checkWikiNameIsCorrect($name, $lang);
		
		wfProfileOut(__METHOD__);
	}
	
	public function executeCreateWiki() {
		wfProfileIn(__METHOD__);
		global $wgRequest;
		
		$name = $wgRequest->getVal('name');
		$domain = $wgRequest->getVal('domain');
		$lang = $wgRequest->getVal('lang');
		$category = $wgRequest->getVal('category');
		
		//$status = $this-createWiki($name, $domain, $lang, $category)
		
		wfProfileOut(__METHOD__);
	}
	
	private function createWiki() {
		wfProfileIn(__METHOD__);
		
		
		
		wfProfileOut(__METHOD__);
	}
	
	/**
	 * Saves current form values into session.
	 * Currently, only for the first step.  May need to expand this to all steps later.
	 */
	public function executeSaveState() {
		wfProfileIn(__METHOD__);
		global $wgRequest;
		
		$params = array();
		
		$params['name'] = $wgRequest->getVal('name');
		$params['domain'] = $wgRequest->getVal('domain');
		$params['lang'] = $wgRequest->getVal('lang');
		
		$_SESSION['wsCreateNewWikiParams'] = $params;

		wfProfileOut(__METHOD__);
	}
	
	/**
	 * Loads form values from session.
	 */
	public function executeLoadState() {
		wfProfileIn(__METHOD__);
		if(!empty($_SESSION['wsCreateNewWikiParams'])) {
			$params = $_SESSION['wsCreateNewWikiParams'];
			
			$this->wikiName = $params['name'];
			$this->wikiDomain = $params['domain'];
			$this->wikiLanguage = $params['lang'];
		}
		wfProfileOut(__METHOD__);
	}
	
	/**
	 * Checks if WikiPayment is enabled and handles fetching PayPal token - if disabled, displays error message
	 *
	 * @author Maciej B?aszkowski <marooned at wikia-inc.com>
	 */
	public function executeUpgradeToPlus() {
		global $wgRequest;
		wfProfileIn( __METHOD__ );
		
		$cityId = $wgRequest->getVal('cityId');
		
		// XSS security needed here

		if (method_exists('SpecialWikiPayment', 'fetchPaypalToken')) {

			$data = SpecialWikiPayment::fetchPaypalToken($cityId);
			if (empty($data['url'])) {
				$this->status = 'error';
				$this->caption = wfMsg('owb-step4-error-caption');
				$this->content = wfMsg('owb-step4-error-token-content');
			} else {
				$this->status = 'ok';
				$this->data = $data;
			}
		} else {
			$this->status = 'error';
			$this->caption = wfMsg('owb-step4-error-caption');
			$this->content = wfMsg('owb-step4-error-upgrade-content');
		}
		wfProfileOut( __METHOD__ );
	}
	
	public function executeWikiWelcomeModal() {
		wfProfileIn(__METHOD__);
		
		wfProfileOut(__METHOD__);
	}
	
	public function executeSaveSettings() {
		global $wgRequest;

		wfProfileIn( __METHOD__ );

		$data = $wgRequest->getArray( 'settings' );
		$cityId = $wgRequest->getVal( 'cityId' );
		
		$themeSettings = new ThemeSettings();
		$themeSettings->saveSettings($data, $cityId);

		wfProfileOut( __METHOD__ );
	}
	
	public function executeSaveMainPageDescription() {
		global $wgRequest;
		wfProfileIn( __METHOD__ );
		
		$cityId = $wgRequest->getVal( 'cityId' );
		$desc = $wgRequest->getVal( 'desc' );
		
		$this->mainpage = wfMsgForContent( 'mainpage' );
		
		wfProfileOut( __METHOD__ );
	}

}