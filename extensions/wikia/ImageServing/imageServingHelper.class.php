<?php
/*
 * Author: Tomek Odrobny
 * Class to serving list of top 5 images for article use for indexing and keep index up to date
 */

class ImageServingHelper{
	const IMAGES_PER_ARTICLE = 230;

	static $hookOnOff = false; // parser hook are off

	/**
	 * @param LinksUpdate $linksUpdate
	 * @return bool return true to continue hooks flow
	 */
	public static function onLinksUpdateComplete( $linksUpdate ) {
		wfProfileIn(__METHOD__);
		$images = $linksUpdate->getImages();
		$articleId = $linksUpdate->getTitle()->getArticleID();

		if(count($images) === 1) {
			$images = array_keys($images);
			self::buildIndex( $articleId, $images);
			wfProfileOut(__METHOD__);
			return true;
		}

		$article = new Article($linksUpdate->getTitle());
		self::buildAndGetIndex( $article );
		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * Replace images with some easy to parse tag
	 *
	 * @param $skin
	 * @param $title
	 * @param File|LocalFile $file
	 * @param $frameParams
	 * @param $handlerParams
	 * @param $time
	 * @param $res
	 * @return bool return false to break hooks flow
	 */
	public static function onImageBeforeProduceHTML( $skin, $title, $file, $frameParams, $handlerParams, $time, &$res ) {
		if (!self::$hookOnOff) {
			return true;
		}
		wfProfileIn(__METHOD__);

		if( $file instanceof File ||  $file instanceof LocalFile ) {
			/* @var File $file */
			$res = " <image mw='".$file->getTitle()->getPartialURL()."' /> ";
		}

		wfProfileOut(__METHOD__);
		return false;
	}

	/**
	 * Replace images from image gallery with some easy to parse tag :
	 *
	 * @param Parser $parser
	 * @param WikiaPhotoGallery $ig
	 * @return bool return true to continue hooks flow
	 */
	public static function onBeforeParserrenderImageGallery( $parser, &$ig) {
		global $wgEnableWikiaPhotoGalleryExt;

		if ((!self::$hookOnOff) || empty($wgEnableWikiaPhotoGalleryExt)) {
			return true;
		}

		wfProfileIn(__METHOD__);

		$ig->parse();
		$data = $ig->getData();

		$ig = new fakeIGimageServing( $data['images'] );
		wfProfileOut(__METHOD__);
		return false;
	}

	private static function hookSwitch($onOff = true) {
		self::$hookOnOff = $onOff;
	}

	public static function isParsing() {
		return self::$hookOnOff;
	}

	/**
	 * Helper function to help build list on images in parserHook
	 *
	 * TODO: remove? not called from anywhere
	 *
	 * @param File[] $files
	 * @return string
	 **/
	public static function buildImages($files) {
		$res = '';
		foreach($files as $file) {
			if( $file instanceof File ||  $file instanceof LocalFile ) {
				/* @var File $file */
				$res .= " <image mw='".$file->getTitle()->getPartialURL()."' /> ";
			}
		}
		return $res;
	}

	/**
	 * buildIndex - save image index in db
	 *
	 * @param int $width
	 * @param array|string $images
	 * @param $ignoreEmpty boolean
	 */
	public static function buildIndex( $articleId, $images, $ignoreEmpty = false ) {
		wfProfileIn(__METHOD__);

		$app = F::app();
		$db = $app->wf->GetDB(DB_MASTER, array());

		// BugId:95164: limit the number of images to be stored serialized in DB
		// PHP has an internal limit of 65535 bytes than can be unserialized
		if (count($images) > self::IMAGES_PER_ARTICLE) {
			$images = array_slice($images, 0, self::IMAGES_PER_ARTICLE);
		}

		array_walk( $images, create_function( '&$n', '$n = urldecode( $n );' ) );

		if( count($images) < 1 ) {
			if( $ignoreEmpty) {
				wfProfileOut(__METHOD__);
				return;
			}
			$db->delete( 'page_wikia_props',
				array(
					'page_id' =>  $articleId,
					'propname' => "0"),
				__METHOD__
			);
			wfProfileOut(__METHOD__);
			return;
		}

		$db->replace('page_wikia_props','',
			array(
				'page_id' =>  $articleId,
				'propname' => "0",
				'props' => serialize($images)
			),
			__METHOD__
		);

		$db->commit();
		wfProfileOut(__METHOD__);
		return;
	}

	/**
	 * @param Article $article
	 * @param bool $ignoreEmpty
	 * @return mixed
	 */
	public static function buildAndGetIndex($article, $ignoreEmpty = false ) {
		if(!($article instanceof Article)) {
			return;
		}
		wfProfileIn(__METHOD__);

		$article->getRawText();
		$title = $article->getTitle();
		$content = $article->getContent();

		self::hookSwitch();
		$editInfo = $article->prepareTextForEdit( $content, $title->getLatestRevID() );
		self::hookSwitch(false);

		$out = array();
		preg_match_all("/(?<=(image mw=')).*(?=')/U", $editInfo->output->getText(), $out );

		self::buildIndex($article->getID(), $out[0], $ignoreEmpty);
		wfProfileOut(__METHOD__);
	}

}

/* fake class for replace image gallery in hook*/
class fakeIGimageServing extends ImageGallery {
	private $in;

	function __construct($in) {
		$this->in = $in;
	}

	function toHTML() {
		$res = "";
		foreach ( $this->in as $imageData ) {
			$file =  $this->getImage($imageData['name']);

			if($file) {
				$res .= " <image mw='".$file->getTitle()->getDBkey()."' /> ";
			}
		}
		return $res;
	}

	private function getImage($nt) {
		wfProfileIn(__METHOD__);

		# Give extensions a chance to select the file revision for us
		$time = $descQuery = false;
		wfRunHooks( 'BeforeGalleryFindFile', array( &$this, &$nt, &$time, &$descQuery ) );

		# Render image thumbnail
		$img = wfFindFile( $nt, $time );
		wfProfileOut(__METHOD__);
		return $img;
	}
}
