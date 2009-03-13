<?php

/**
 * AutoCreateWikiCentralJob -- Welcome user after first edit
 *
 * @file
 * @ingroup JobQueue
 *
 * @copyright Copyright © Krzysztof Krzyżaniak for Wikia Inc.
 * @author Krzysztof Krzyżaniak (eloy) <eloy@wikia-inc.com>
 * @date 2009-03-12
 * @version 1.0
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This is a MediaWiki extension and cannot be used standalone.\n";
	exit( 1 );
}

class AutoCreateWikiLocalJob extends Job {

	private
		$mFounder;

	/**
	 * constructor
	 *
	 * @access public
	 */
	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( "ACWLocal", $title, $params, $id );
		$this->mParams = $params;
	}

	/**
	 * main entry point
	 *
	 * @access public
	 */
    public function run() {

		global $wgUser;

		wfProfileIn( __METHOD__ );

		/**
		 * setup founder user
		 */
		if( $this->mParams[ "founder"] ) {
			$this->mFounder = User::newFromId( $this->mParams[ "founder"] );
		}
		$this->setWelcomeTalkPage();

		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * inherited "insert" function add job to current database, for this job
	 * we need to add job to newly created wiki
	 *
	 * @param Integer $city_id	wiki identifier in city_list table
	 */
	public function WFinsert( $city_id ) {

		global $wgDBname;

		/**
		 * we can take local database from city_id in params array
		 */
		if( $city_id ) {
			$database = Wikifactory::IdtoDB( $city_id );
			if( $database ) {
				$fields = $this->insertFields();

				$dbw = wfGetDB( DB_MASTER );
				$dbw->selectDB( $database );

				if ( $this->removeDuplicates ) {
					$res = $dbw->select( 'job', array( '1' ), $fields, __METHOD__ );
					if ( $dbw->numRows( $res ) ) {
						return;
					}
				}
				$fields['job_id'] = $dbw->nextSequenceValue( 'job_job_id_seq' );
				$dbw->insert( 'job', $fields, __METHOD__ );

				/**
				 * we need to commit before switching databases
				 */
				$dbw->commit();
				$dbw->selectDB( $wgDBname );
			}
		}
	}

	/**
	 * setWelcomeTalkPage
	 *
	 * @author eloy@wikia
	 * @access private
	 *
	 * @return boolean status
	 */
	private function setWelcomeTalkPage() {
		global $wgUser;

		Wikia::log( __METHOD__, "talk", "Setting welcome talk page on new wiki..." );

		$talkPage = $this->mFounder->getTalkPage();
		$wikiaName = WikiFactory::getVarValueByName( "wgSitename", $this->mParams[ "city_id"] );
		$wikiaLang = WikiFactory::getVarValueByName( "wgLanguageCode", $this->mParams[ "city_id"] );

		/**
		 * set apropriate staff member
		 */
		$wgUser = self::getStaffUserByLang( $wikiaLang );
		$wgUser = ( $wgUser instanceof User ) ? $wgUser : User::newFromId( "Angela" );

		$talkParams = array(
			$this->mFounder->getName(),
			$wgUser->getName(),
			$wgUser->getRealName(),
			$wikiaName
		);

		$talkBody = false;
		if(! empty( $wikiaLang ) ) {
			/**
			 * custom lang translation
			 */
			$talkBody = wfMsgExt( "autocreatewiki-welcometalk", array( 'language' => $wikiaLang ), $talkParams );
		}

		if( ! $talkBody ) {
			/**
			 * wfMsgExt should always return message, but just in case...
			 */
			$talkBody = wfMsg( "autocreatewiki-welcometalk", $talkParams );
		}

		/**
		 * and now create talk article
		 */
		$talkArticle = new Article( $talkPage, 0 );
		if( !$talkArticle->exists() ) {
			$talkArticle->doEdit( $talkBody,  wfMsg( "autocreatewiki-welcometalk-log" ), EDIT_FORCE_BOT );
		}

		return true;
	}

	/**
	 * move main page to SEO-friendly name
	 */
	private function moveMainPage() {
		global $wgSitename;

		$mainPage = wfMsgForContent('Mainpage');
		$targetPage = $wgSitename;

		$sourceTitle = Title::newFromText( $source );
		if( !$sourceTitle ) {
		    print "Invalid page title: $source";
		    return;
		}

		$mainArticle = new Article( $sourceTitle, 0 );
		if( !$mainPageArticle->exists() ) {
		    print "Article $source not exists.";
			return;
		}

	/**
	 * move main page to SEO-friendly name
	 */
	private function protectKeyPages() {

	}
}
