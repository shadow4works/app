<?php

/**
 * Send email with report about monitored phrases
 *
 * @group cronjobs
 * @see phrase-alerts.yaml
 *
 * This script should be run for wiki_id 177 where $wgDiscussionAlertsQueries WikiFactory variable is defined
 */

require_once __DIR__ . '/../../Maintenance.php';

class PhraseAlerts extends Maintenance {

	public function execute() {
		global $wgDiscussionAlertsQueries;

		$logger = \Wikia\Logger\WikiaLogger::instance();
		$isDryRun = $this->hasOption('dry-run');

		$logger->info( 'Init' );

		$db = 'specials';
		$perpage = 1000;
		$date = gmDate( "Y-m-d\TH:i:s\Z", strtotime( '-30 days' ) );

		$actualQuery = '(title_en:"PATTERN" OR nolang_en:"PATTERN") AND touched:[' . $date . ' TO *]';

		$searchUrl = "http://prod.search-master.service.sjc.consul:8983/solr/main/select/?wt=php&fl=ns,wid,pageid,url,host,titleStrict&q=" . urlencode( $actualQuery ) . "&rows=$perpage&start=";

		$queries = $wgDiscussionAlertsQueries;
		$found = array();
		$body = 'This message contains pages containing phrases which are marked as to be tracked (in $wgDiscussionAlertsQueries).' . "\n\nThis listing contains pages edited no earlier than $date (30 days ago) and not previously reported.\n\nPlease ensure the end line is visible before closing this ticket. If not, request a full copy from Community Engineering.";

		$logger->info( 'Connecting to DB' );

		$dbr = wfGetDB( DB_SLAVE, array(), $db );
		$dbw = wfGetDB( DB_MASTER, array(), $db );

		$threadNamespaces = [ NS_USER_WALL_MESSAGE, NS_WIKIA_FORUM_BOARD_THREAD ];

		// Step 1: process queries and fetch data from Solr
		foreach ( $queries as $query ) {

			$logger->info( "Processing query: $query" );

			// initializing variables
			$i = 1;
			$found[ $query ] = array();

			// prepare solr URL
			$finalUrl = str_replace( 'PATTERN', urlencode( $query ), $searchUrl );

			$body .= "\n\n== $query ==\n\n";

			$threadReported = [];
			$numFound = 0;

			while ( $i + $perpage < $numFound || $i == 1 ) {
				$logger->info(  "Fetching result" );
				$result_raw = file_get_contents( $finalUrl . $i );
				$result = null;

				if ( empty( $result_raw ) ) {
					$logger->error( "Something went horribly wrong. No response from Solr." );
					die();
				} else {
					$logger->info(  "Processing result" );
					eval( "\$result = " . $result_raw . ";" );
				}

				$numFound = $result['response']['numFound'];
				$pages = $result['response']['docs'];

				foreach ( $pages as $pageData ) {
					$pageUrl = $pageReport = $pageData['url'];

					if ( in_array( $pageData['ns'], $threadNamespaces ) ) {
						$wikiUrl = "http://{$pageData['host']}/wiki/";
						$strippedPageTitle = str_replace( $wikiUrl, '', $pageData['url'] );
						if ( substr_count( $strippedPageTitle, '@comment' ) === 2 ) {
							$parts = explode( '/@', $strippedPageTitle );
							list( $namespaceText, $parentPageTitle ) = explode( ':', $parts[0] . '/@' . $parts[1] );
							$title = GlobalTitle::newFromText( $parentPageTitle, $pageData['ns'], $pageData['wid'] );
							$parentId = $title->getArticleID();
							$pageUrl = "{$wikiUrl}{$namespaceText}:{$parentPageTitle}";
							$pageReport = "{$wikiUrl}Thread:$parentId (Thread title: {$pageData['titleStrict']}; Comment triggered: {$wikiUrl}Thread:{$pageData['pageid']})";
						} else {
							$pageReport = "http://{$pageData['host']}/wiki/Thread:{$pageData['pageid']} (Thread title: {$pageData['titleStrict']})";
						}

						if ( isset( $threadReported[ $pageUrl ] ) ) {
							continue;
						}

						$threadReported[ $pageUrl ] = true;
					}

					$res = $dbr->selectField(
						'discussion_reporting',
						'dr_title',
						array(
							'dr_title' => $pageUrl,
							'dr_query' => $query,
						)
					);

					if ( empty( $res ) ) {

						$found[ $query ][] = $pageData['url'];
						$body .= $pageReport . "\n";

						if ( !$isDryRun ) {
							$dbw->insert(
								'discussion_reporting',
								array(
									'dr_query' => $query,
									'dr_title' => $pageUrl,
								)
							);
						}
					}
				}

				$i = $i + $perpage;
			}

			// add a message saying no results were found
			if ( empty( $found[ $query ] ) ) {
				$body .= "No matches found for this query.\n\n";
			}
		}

		$body .= "\nThis is the end of the list. If you can't read this line, please tell TOR. Oh... wait...\n";

		$logger->info( 'Sending emails' );

		if ( $isDryRun ) {
			$logger->info( 'Email to be sent', [
				'body' => $body
			] );
		} else {
			$this->doMail( 'tor@wikia-inc.com', $body );
			$this->doMail( 'community@wikia.com', $body );
		}

		$logger->info( 'Finished' );
	}

	function doMail( $address, $body ) {
		$recipient = new MailAddress( $address );

		try {
			$status = UserMailer::send(
				$recipient,
				$recipient,
				'Discussions alert',
				$body
			);

			if ( !$status->isGood() ) {
				throw new Exception( 'Status returned from UserMailer::send is not good' );
			}
		} catch ( Exception $e ) {
			\Wikia\Logger\WikiaLogger::instance()->error( $e->getMessage(), [
				'exception' => $e
			] );
		}
	}
}

$maintClass = 'PhraseAlerts';
require_once RUN_MAINTENANCE_IF_MAIN;
