<?php
/**
 * WikiaMobile Ads Service
 * 
 * @author Jakub Olek <bukaj.kelo(at)gmail.com>
 */
class WikiaMobileAdService extends WikiaService {

	static public function shouldLoadAssets() {
		// Append ad code for anonymous users
		// They'll get the ad eventually
		return F::app()->wg->user->isAnon();
	}

	static public function shouldShowAds() {
		// Show ads only for anon users on all but special pages
		// TODO: unify with desktop logic, like this:
		// AdEngine2Controller::getAdLevelForPage() === AdEngine2Controller::LEVEL_ALL
		return self::shouldLoadAssets() && !F::app()->wg->Title->isSpecialPage();
	}

	public function index() {
		if (!self::shouldShowAds()) {
			$this->skipRendering();
		}
	}
}
