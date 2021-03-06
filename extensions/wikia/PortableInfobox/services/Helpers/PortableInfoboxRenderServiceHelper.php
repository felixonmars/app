<?php

namespace Wikia\PortableInfobox\Helpers;

use \Wikia\Logger\WikiaLogger;

class PortableInfoboxRenderServiceHelper {
	const LOGGER_LABEL = 'portable-infobox-render-not-supported-type';
	//todo: https://wikia-inc.atlassian.net/browse/DAT-3075
	//todo: figure out what to do when user changes default infobox width via custom theming
	const DESKTOP_THUMBNAIL_WIDTH = 270;
	const MOBILE_THUMBNAIL_WIDTH = 360;
	const MINIMAL_HERO_IMG_WIDTH = 300;
	const MAX_DESKTOP_THUMBNAIL_HEIGHT = 500;

	function __construct() {}

	/**
	 * creates special data structure for horizontal group from group data
	 *
	 * @param array $groupData
	 * @return array
	 */
	public function createHorizontalGroupData( $groupData ) {
		$horizontalGroupData = [
			'labels' => [],
			'values' => [],
			'renderLabels' => false
		];

		foreach ( $groupData as $item ) {
			$data = $item[ 'data' ];

			if ( $item[ 'type' ] === 'data' ) {
				array_push( $horizontalGroupData[ 'labels' ], $data[ 'label' ] );
				array_push( $horizontalGroupData[ 'values' ], $data[ 'value' ] );

				if ( !empty( $data[ 'label' ] ) ) {
					$horizontalGroupData[ 'renderLabels' ] = true;
				}
			} else if ( $item[ 'type' ] === 'header' ) {
				$horizontalGroupData[ 'header' ] = $data[ 'value' ];
			}
		}

		return $horizontalGroupData;
	}

	/**
	 * checks if infobox item is the title or title inside the hero module
	 * and if so, removes from it all HTML tags.
	 *
	 * @param string $type type of infobox item
	 * @param array $data infobox item data
	 * @return array infobox $data with sanitized title param if needed
	 */
	public function sanitizeInfoboxTitle( $type, $data ) {
		if ( $type === 'title' && !empty( $data[ 'value' ] ) ) {
			$data[ 'value' ] = trim( strip_tags( $data[ 'value' ] ) );

			return $data;
		}
		if ( $type === 'hero-mobile' && !empty( $data[ 'title' ][ 'value' ] ) ) {
			$data[ 'title' ][ 'value' ] = trim( strip_tags( $data[ 'title' ][ 'value' ] ) );

			return $data;
		}

		return $data;
	}

	/**
	 * extends image data
	 *
	 * @param array $data
	 *
	 * @return bool|array
	 */
	public function extendImageData( $data ) {
		$thumbnail = $this->getThumbnail( $data[ 'name' ] );

		if ( !$thumbnail ) {
			return false;
		}

		$data[ 'height' ] = $thumbnail->getHeight();
		$data[ 'width' ] = $thumbnail->getWidth();
		$data[ 'thumbnail' ] = $thumbnail->getUrl();
		$data[ 'key' ] = urlencode( $data[ 'key' ] );
		$data[ 'media-type' ] = $data[ 'isVideo' ] ? 'video' : 'image';

		return $data;
	}

	/**
	 * checks if infobox data item is valid hero component data.
	 * If image is smaller than MINIMAL_HERO_IMG_WIDTH const, doesn't render the hero module.
	 *
	 * @param array $item - infobox data item
	 * @param array $heroData - hero component data
	 *
	 * @return bool
	 */
	public function isValidHeroDataItem( $item, $heroData ) {
		$type = $item[ 'type' ];

		if ( $type === 'title' && !array_key_exists( 'title', $heroData ) ) {
			return true;
		}

		if ( $type === 'image' && !array_key_exists( 'image', $heroData ) ) {
			$imageWidth = $this->getFileWidth( $item[ 'data' ][ 'name' ] );

			if ( $imageWidth >= self::MINIMAL_HERO_IMG_WIDTH ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * required for testing mobile template rendering
	 * @return bool
	 */
	public function isWikiaMobile() {
		return \F::app()->checkSkin( 'wikiamobile' );
	}

	/**
	 * check if item type is supported and logs unsupported types
	 *
	 * @param string $type - template type
	 * @param array $templates - array of supported templates
	 *
	 * @return bool
	 */
	public function isTypeSupportedInTemplates( $type, $templates ) {
		$isValid = true;

		if ( !isset( $templates[ $type ] ) ) {
			WikiaLogger::instance()->info( self::LOGGER_LABEL, [
				'type' => $type
			] );

			$isValid = false;
		}

		return $isValid;
	}

	/**
	 * return real width of the image.
	 * @param Title $title
	 * @return int number
	 */
	private function getFileWidth( $title ) {
		$file = \WikiaFileHelper::getFileFromTitle( $title );

		if ( $file ) {
			return $file->getWidth();
		}
	}

	/**
	 * @desc create a thumb of the image from file title.
	 * @param Title $title
	 * @return bool|MediaTransformOutput
	 */
	private function getThumbnail( $title ) {
		$file = \WikiaFileHelper::getFileFromTitle( $title );

		if ( $file ) {
			$size = $this->getAdjustedImageSize( $file );
			$thumb = $file->transform( $size );

			if ( !is_null( $thumb ) && !$thumb->isError() ) {
				return $thumb;
			}
		}

		return false;
	}

	/**
	 * @desc get image size according to the width and height limitations:
	 * Height on desktop cannot be bigger than 500px
	 * Width have to be adjusted to const for mobile or desktop infobox
	 * @param $image
	 * @return array width and height
	 */
	public function getAdjustedImageSize( $image ) {
		if ( $this->isWikiaMobile() ) {
			$width = self::MOBILE_THUMBNAIL_WIDTH;
			$height = null;
		} else {
			$height = min( self::MAX_DESKTOP_THUMBNAIL_HEIGHT, $image->getHeight() );
			$width = self::DESKTOP_THUMBNAIL_WIDTH;
		}

		return [ 'height' => $height, 'width' => $width ];
	}
}
