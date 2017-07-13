<?php

namespace Wikia\CommunityHeader;

use DesignSystemCommunityHeaderModel;
use NavigationModel;

class Navigation {
	public $discussLink;
	public $exploreItems;
	public $exploreLabel;
	public $localNavigation;

	private $model;

	public function __construct( DesignSystemCommunityHeaderModel $model, $wikiText = null ) {
		$this->model = $model;

		//$navigationModel = new NavigationModel();
		//if ( empty( $wikiText ) ) {
		//	$this->localNavigation = $navigationModel->getLocalNavigationTree( NavigationModel::WIKI_LOCAL_MESSAGE );
		//} else {
		//	$this->localNavigation = $navigationModel->getTreeFromText( $wikiText );
		//}

		$this->localNavigation = $model->getWikiLocalNavigation( $wikiText );

		$exploreMenu = $this->model->getExploreMenu();

		$this->exploreLabel = new Label(
			$exploreMenu['title']['key'], Label::TYPE_TRANSLATABLE_TEXT, $exploreMenu['image-data']['name']
		);
		$this->exploreItems = $this->getExploreItems();
		$this->discussLink = $this->getDiscussLink();
	}

	private function getExploreItems(): array {
		$exploreItems = $this->model->getExploreMenu()['items'];

		return array_map(
			function ( $item ) {
				return new Link(
					new Label( $item['title']['key'], Label::TYPE_TRANSLATABLE_TEXT ),
					$item['href'],
					$item['tracking_label']
				);
			},
			$exploreItems
		);
	}

	private function getDiscussLink() {
		$discussData = $this->model->getDiscussLinkData();
		$discussLink = null;

		if ( !empty( $discussData ) ) {
			$discussLink = new Link(
				new Label( $discussData['title']['key'], Label::TYPE_TRANSLATABLE_TEXT, $discussData['image-data']['name'] ),
				$discussData['href'],
				$discussData['tracking_label']
			);
		}

		return $discussLink;
	}
}
