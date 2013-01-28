<?

class PhalanxPager extends ReverseChronologicalPager {
	public function __construct() {
		parent::__construct();
		$app = F::app();

		$this->mDb = wfGetDB( DB_SLAVE, array(), $this->wg->ExternalSharedDB );
		$this->mSearchText = $this->wg->Request->getText( 'wpPhalanxCheckBlocker', null );
		$this->mSearchFilter = $this->wg->Request->getArray( 'wpPhalanxTypeFilter' );
		$this->mSearchId = $this->wg->Request->getInt( 'id' );
	}

	function getQueryInfo() {
		$query['tables'] = 'phalanx';
		$query['fields'] = '*';

		if ( $this->mSearchId ) {
			$query['conds'][] = "p_id = {$this->mSearchId}";
		} else {
			if ( !empty( $this->mSearchText ) ) {
				$query['conds'][] = '(p_text like "%' . $this->mDb->escapeLike( $this->mSearchText ) . '%")';
			}

			if ( !empty( $this->mSearchFilter ) ) {
				$typemask = 0;
				foreach ( $this->mSearchFilter as $type ) {
					$typemask |= $type;
				}

				$query['conds'][] = "p_type & $typemask <> 0";
			}
		}

		return $query;
	}

	function getIndexField() {
		return 'p_timestamp';
	}

	function getStartBody() {
		return '<ul>';
	}

	function getEndBody() {
		return '</ul>';
	}

	function formatRow( $row ) {
		// hide e-mail filters
		if ( ( $row->p_type & Phalanx::TYPE_EMAIL ) && !$this->wg->User->isAllowed( 'phalanxemailblock' ) ) {
			return '';
		}

		$author = F::build('User', array( $row->p_author_id ), 'newFromId');
		$authorName = $author->getName();
		$authorUrl = $author->getUserPage()->getFullUrl();

		$phalanxPage = F::build( 'Title', array( 'Phalanx', NS_SPECIAL ), 'newFromText' );
		$phalanxUrl = $phalanxPage->getFullUrl( array( 'id' => $row->p_id ) );

		$phalanxStatsPage = F::build( 'Title', array( 'PhalanxStats', NS_SPECIAL ), 'newFromText' );
		$statsUrl = sprintf( "%s/%s", $phalanxStatsPage->getFullUrl(), $row->p_id );

		$html  = Html::openElement( 'li', array( 'id' => 'phalanx-block-' . $row->p_id ) );
		$html .= Html::element( 'b', array(), htmlspecialchars( $row->p_text ) ); 
		$html .= sprintf( " (%s%s%s) ", 
			( $row->p_regex ? 'regex' : 'plain' ),
			( $row->p_case  ? ',case' : '' ),
			( $row->p_exact ? ',exact': '' )
		);

		/* control links */
		$html .= sprintf( " &bull; %s &bull; %s &bull; %s <br />", 
			Html::element( 'a', array( 'class' => 'unblock', 'href' => $phalanxUrl ), $this->wf->Msg('phalanx-link-unblock') ),
			Html::element( 'a', array( 'class' => 'modify', 'href' => $phalanxUrl ), $this->wf->Msg('phalanx-link-modify') ),			  
			Html::element( 'a', array( 'class' => 'stats', 'href' => $statsUrl ), $this->wf->Msg('phalanx-link-stats') )
		);
		
		/* types */
		$html .= $this->wf->Msg('phalanx-display-row-blocks', implode( ', ', Phalanx::getTypeNames( $row->p_type ) ) );

		$html .= sprintf( " &bull; %s ", $this->wf->MsgExt( 'phalanx-display-row-created', array('parseinline'), $authorName, $this->wg->Lang->timeanddate( $row->p_timestamp ) ) );

		$html .= Html::closeElement( "li" );

		return $html;
	}
}
