<?php

namespace Wikia\ContentReview;

use Wikia\ContentReview\Models\CurrentRevisionModel;
use Wikia\ContentReview\Models\ReviewModel;

class Helper extends \ContextSource {

	const CONTENT_REVIEW_TOOLBAR_TEMPLATE_PATH = 'extensions/wikia/ContentReview/templates/ContentReviewToolbar.mustache';
	const CONTENT_REVIEW_PARAM = 'contentreview';
	const CONTENT_REVIEW_MEMC_VER = '1.0';
	const CONTENT_REVIEW_REVIEWED_KEY = 'reviewed-js-pages';
	const CONTENT_REVIEW_CURRENT_KEY = 'current-js-pages';
	const JS_FILE_EXTENSION = '.js';


	/**
	 * Returns data about all approved revisions (of JS pages) for current wiki
	 *
	 * @return bool|array
	 */
	public function getReviewedJsPages() {
		global $wgCityId;

		$currentRevisionModel = new Models\CurrentRevisionModel();
		$revisions = $currentRevisionModel->getLatestReviewedRevisionForWiki( $wgCityId );

		return $revisions;
	}

	/**
	 * Return data about all JS pages on current wiki
	 *
	 * @return bool|mixed
	 */
	public function getJsPages() {
		$db = wfGetDB( DB_SLAVE );

		$jsPages = ( new \WikiaSQL() )
			->SELECT( 'page_id', 'page_title', 'page_touched', 'page_latest' )
			->FROM( 'page' )
			->WHERE( 'page_namespace' )->EQUAL_TO( NS_MEDIAWIKI )
			->AND_( 'LOWER (page_title)' )->LIKE( '%' . self::JS_FILE_EXTENSION )
			->runLoop( $db, function ( &$jsPages, $row ) {
				$jsPages[$row->page_id] = get_object_vars( $row );
				$jsPages[$row->page_id]['ts'] = wfTimestamp( TS_UNIX, $row->page_touched );
			} );

		return $jsPages;

	}

	/**
	 * Returns timestamp of last approved revision
	 *
	 * @return int
	 */
	public function getReviewedJsPagesTimestamp() {
		$timestamp = \WikiaDataAccess::cache(
			$this->getMemcKey( self::CONTENT_REVIEW_REVIEWED_KEY ),
			\WikiaResponse::CACHE_STANDARD, // 60 * 60 * 24
			function() {
				$pages = $this->getReviewedJsPages();

				return $this->getMaxTimestamp( $pages );
			}
		);

		return $timestamp;
	}

	/**
	 * Returns timestamp of last edited JS page
	 *
	 * @return int
	 */
	public function getJsPagesTimestamp() {
		$timestamp = \WikiaDataAccess::cache(
			$this->getMemcKey( self::CONTENT_REVIEW_CURRENT_KEY ),
			\WikiaResponse::CACHE_STANDARD, // 60 * 60 * 24
			function() {
				$pages = $this->getJsPages();

				return $this->getMaxTimestamp( $pages );
			}
		);

		return $timestamp;
	}

	/**
	 * Returns max timestamp
	 *
	 * @param $pages
	 * @return int
	 */
	public function getMaxTimestamp( $pages ) {
		$maxTimestamp = 0;

		foreach ( $pages as $page ) {
			$maxTimestamp = max( $maxTimestamp, $page['ts'] );
		}

		if ( empty( $maxTimestamp ) ) {
			return 0;
		}

		return $maxTimestamp;
	}

	/**
	 * Returns approved revision id for given page.
	 * If there is no reviewed revision it returns 0.
	 *
	 * @param int $pageId
	 * @param int $wikiId
	 * @return int
	 */
	public function getReviewedRevisionId( $pageId, $wikiId = 0 ) {
		global $wgCityId;

		if ( empty( $wikiId ) ) {
			$wikiId = $wgCityId;
		}

		$currentRevisionModel = new Models\CurrentRevisionModel();
		$revision = $currentRevisionModel->getLatestReviewedRevision( $wikiId, $pageId );

		if ( is_null( $revision['revision_id'] ) ) {
			return 0;
		}

		return $revision['revision_id'];
	}

	/**
	 * Returns wiki ids on which user is in test mode
	 *
	 * @return array|mixed
	 */
	public function getContentReviewTestModeWikis() {
		$key = \ContentReviewApiController::CONTENT_REVIEW_TEST_MODE_KEY;
		$wikiIds = $this->getRequest()->getSessionData( $key );

		if ( !empty( $wikiIds ) ) {
			$wikiIds = unserialize( $wikiIds );
		} else {
			$wikiIds = [];
		}

		return $wikiIds;
	}

	/**
	 * Enable test mode on provided wiki
	 * @param int $wikiId
	 */
	public function setContentReviewTestMode( $wikiId ) {
		$wikiIds = $this->getContentReviewTestModeWikis();

		if ( !in_array( $wikiId, $wikiIds ) ) {
			$wikiIds[] = $wikiId;
			$this->getRequest()->setSessionData(
				\ContentReviewApiController::CONTENT_REVIEW_TEST_MODE_KEY,
				serialize( $wikiIds )
			);
		}
	}

	/**
	 * Disable test mode on provided wiki
	 * @param int $wikiId
	 */
	public function disableContentReviewTestMode( $wikiId ) {;
		$wikiIds = $this->getContentReviewTestModeWikis();
		$wikiKey = array_search( $wikiId, $wikiIds );

		if ( $wikiKey !== false ) {
			unset( $wikiIds[$wikiKey] );
			$this->getRequest()->setSessionData(
				\ContentReviewApiController::CONTENT_REVIEW_TEST_MODE_KEY,
				serialize( $wikiIds )
			);
		}
	}

	/**
	 * Checks if test mode is enabled on current or given wiki
	 *
	 * @param int $wikiId
	 * @return bool
	 */
	public function isContentReviewTestModeEnabled( $wikiId = 0 ) {
		global $wgCityId;

		if ( empty( $wikiId ) ) {
			$wikiId = $wgCityId;
		}

		$wikisIds = $this->getContentReviewTestModeWikis();
		return ( !empty( $wikisIds ) && in_array( $wikiId, $wikisIds ) );
	}

	public static function isStatusAwaiting( $status ) {
		return in_array( (int)$status, [
				ReviewModel::CONTENT_REVIEW_STATUS_UNREVIEWED,
				ReviewModel::CONTENT_REVIEW_STATUS_IN_REVIEW,
			]
		);
	}

	public static function isStatusCompleted( $status ) {
		return in_array( (int)$status, [
				ReviewModel::CONTENT_REVIEW_STATUS_APPROVED,
				ReviewModel::CONTENT_REVIEW_STATUS_REJECTED,
			]
		);
	}

	public function isDiffPageInReviewProcess( \WikiaRequest $request, ReviewModel $reviewModel, $wikiId, $pageId, $diff ) {
		/**
		 * Do not hit database if there is a URL parameter that indicates that a user
		 * came directly from Special:ContentReview.
		 */
		if ( $request->getInt( self::CONTENT_REVIEW_PARAM ) === 1 ) {
			return true;
		}

		$reviewData = $reviewModel->getReviewedContent( $wikiId, $pageId, ReviewModel::CONTENT_REVIEW_STATUS_IN_REVIEW );
		return ( !empty( $reviewData ) && (int)$reviewData['revision_id'] === $diff );
	}

	public function hasPageApprovedId( CurrentRevisionModel $model, $wikiId, $pageId, $oldid ) {
		$currentData = $model->getLatestReviewedRevision( $wikiId, $pageId );
		return ( !empty( $currentData ) && (int)$currentData['revision_id'] === $oldid );
	}

	public function shouldDisplayReviewerToolbar() {
		global $wgCityId;

		$title = $this->getTitle();

		if ( $title->inNamespace( NS_MEDIAWIKI )
			&& $title->isJsPage()
			&& $title->userCan( 'content-review' )
		) {
			$diffRevisionId = $this->getRequest()->getInt( 'diff' );
			$diffRevisionInfo = ( new ReviewModel() )->getRevisionInfo(
				$wgCityId,
				$title->getArticleID(),
				$diffRevisionId
			);

			$status = (int)$diffRevisionInfo['status'];
			return ( $status === ReviewModel::CONTENT_REVIEW_STATUS_IN_REVIEW
				/* Fallback to URL param if a master-slave replication has not finished */
				|| ( $this->getRequest()->getInt( self::CONTENT_REVIEW_PARAM ) === 1
					&& $status === ReviewModel::CONTENT_REVIEW_STATUS_UNREVIEWED
				)
			);
		}

		return false;
	}

	public function getToolbarTemplate() {
		global $wgCityId;

		return \MustacheService::getInstance()->render(
			self::CONTENT_REVIEW_TOOLBAR_TEMPLATE_PATH,
			[
				'toolbarTitle' => wfMessage( 'content-review-diff-toolbar-title' )->plain(),
				'wikiId' => $wgCityId,
				'pageId' => $this->getTitle()->getArticleID(),
				'approveStatus' => ReviewModel::CONTENT_REVIEW_STATUS_APPROVED,
				'buttonApproveText' => wfMessage( 'content-review-diff-approve' )->plain(),
				'rejectStatus' => ReviewModel::CONTENT_REVIEW_STATUS_REJECTED,
				'buttonRejectText' => wfMessage( 'content-review-diff-reject' )->plain(),
				'talkpageUrl' => $this->prepareProvideFeedbackLink( $this->getTitle() ),
				'talkpageLinkText' => wfMessage( 'content-review-diff-toolbar-talkpage' )->plain(),
				'guidelinesUrl' => wfMessage( 'content-review-diff-toolbar-guidelines-url' )->useDatabase( false )->plain(),
				'guidelinesLinkText' => wfMessage( 'content-review-diff-toolbar-guidelines' )->plain(),
			]
		);
	}

	/**
	 * Link for adding new section on script talk page. Prefilled with standard explanation of rejection.
	 * @param \Title $title Title object of JS page
	 * @return string full link to edit page
	 */
	public function prepareProvideFeedbackLink( \Title $title ) {
		$params = [
			'action' => 'edit',
			'section' => 'new',
			'useMessage' => 'content-review-rejection-explanation'
		];
		return $title->getTalkPage()->getFullURL( $params );
	}

	public function purgeReviewedJsPagesTimestamp() {
		\WikiaDataAccess::cachePurge( $this->getMemcKey( self::CONTENT_REVIEW_REVIEWED_KEY ) );
	}

	public function purgeCurrentJsPagesTimestamp() {
		\WikiaDataAccess::cachePurge( $this->getMemcKey( self::CONTENT_REVIEW_CURRENT_KEY ) );
	}

	public function getMemcKey( $params ) {
		return wfMemcKey( self::CONTENT_REVIEW_PARAM, self::CONTENT_REVIEW_MEMC_VER, $params );
	}

	protected function getRevisionById( $revId ) {
		return \Revision::newFromId( $revId );
	}

	protected function getCurrentRevisionModel() {
		return new CurrentRevisionModel();
	}

	/**
	 * Replaces $text with text from last approved revision
	 * Change is done only for JS pages.
	 * If there's no approved revision replaces with empty string
	 * @param \Title $title
	 * @param string $contentType
	 * @param string $text
	 */
	public function replaceWithLastApproved( \Title $title, $contentType, &$text ) {
		global $wgCityId, $wgJsMimeType;

		if ( $title->isJsPage()
			|| ( $title->inNamespace( NS_MEDIAWIKI ) && $contentType == $wgJsMimeType )
		) {
			$pageId = $title->getArticleID();
			$latestRevId = $title->getLatestRevID();

			$latestReviewedRevData = $this->getCurrentRevisionModel()->getLatestReviewedRevision( $wgCityId, $pageId );

			if ( $latestReviewedRevData['revision_id'] != $latestRevId
				&& !$this->isContentReviewTestModeEnabled()
			) {
				$revision = $this->getRevisionById( $latestReviewedRevData['revision_id'] );

				if ( $revision ) {
					$text = $revision->getRawText();
				} else {
					$text = '';
				}
			}
		}
	}

	/**
	 * Checks if a user can edit a JS page in the MediaWiki namespace.
	 * @param \Title $title
	 * @param \User $user
	 * @return bool
	 */
	public function userCanEditJsPage( \Title $title, \User $user ) {
		return $title->isJsPage()
			&& $title->userCan( 'edit', $user );
	}

	/**
	 * Checks if a user is a reviewer entitled to an automatic approval and if he requested it.
	 *
	 * The wpApprove request param that appears here is a value of a checkbox which is part of
	 * the EditPageLayout for reviewers. It is displayed above the Publish button and allows a reviewer
	 * to make a decision of skipping the review process.
	 *
	 * @param \User $user
	 * @return bool
	 */
	public function userCanAutomaticallyApprove( \User $user ) {
		return $user->isAllowed( 'content-review' )
			&& $user->getRequest()->getBool( 'wpApprove' );
	}
}
