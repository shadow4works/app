<?php

class InsightsController extends WikiaSpecialPageController {
	private $model;

	public function __construct() {
		parent::__construct( 'Insights', 'insights', true );
	}

	public function index() {
		wfProfileIn( __METHOD__ );
		$this->wg->Out->setPageTitle( wfMessage( 'insights' )->escaped() );
		$this->addAssets();

		$this->subpage = $this->getPar();

		if ( !empty( $this->subpage ) ) {
			if ( InsightsHelper::isInsightPage( $this->subpage ) ) {
				$this->renderSubpage();
			} else {
				$this->response->redirect( $this->getSpecialInsightsUrl() );
			}
		}

		wfProfileOut( __METHOD__ );
	}

	private function renderSubpage() {
		$this->model = $this->getInsightModel( $this->subpage );
		if ( $this->model instanceof InsightsModel ) {
			$this->content = $this->model->getContent();
			$this->data = $this->model->getData();
			$this->overrideTemplate($this->model->getTemplate());
		} else {
			throw new MWException( 'An Insights subpage should implement the InsightsModel interface.' );
		}
	}

	/**
	 * Setup method for Insights_loopNotification.mustache template
	 */
	public function loopNotification() {
		$this->response->setTemplateEngine( WikiaResponse::TEMPLATE_ENGINE_MUSTACHE );

		$subpage = $this->request->getVal( 'insight', null );

		if ( !empty( $subpage ) && InsightsHelper::isInsightPage( $subpage ) ) {
			$model = $this->getInsightModel( $subpage );
			if ( $model instanceof InsightsModel ) {
				$next = $model->getNext();

				$this->response->setVal( 'notificationMessage', wfMessage( 'insights-notification-message' )->escaped() );
				$this->response->setVal( 'insightsPageButton', wfMessage( 'insights-notification-list-button' )->escaped() );
				$this->response->setVal( 'nextArticleButton', wfMessage( 'insights-notification-next-item-button' )->escaped() );

				$this->response->setVal( 'insightsPageLink', $this->getSpecialInsightsUrl() );
				$this->response->setVal( 'nextArticleTitle', $next['title'] );
				$this->response->setVal( 'nextArticleLink', $next['link'] . '?action=edit&insights=' . $subpage );
			}
		}
	}

	/**
	 * Returns specific data provider
	 * If it doesn't exists redirect to Special:Insights main page
	 *
	 * @param $subpage Insights subpage name
	 * @return mixed
	 */
	public function getInsightModel( $subpage ) {
		if ( !empty( $subpage ) && InsightsHelper::isInsightPage( $subpage ) ) {
			$modelName = InsightsHelper::$insightsPages[$subpage];
			if ( class_exists( $modelName ) ) {
				return new $modelName();
			}
		}

		return null;
	}

	private function addAssets() {
		$this->response->addAsset( '/extensions/wikia/Insights/styles/insights.scss' );
	}

	/**
	 * Get Special:Insights full url
	 *
	 * @return string
	 */
	private function getSpecialInsightsUrl() {
		return $this->specialPage->getTitle()->getFullURL();
	}
}
