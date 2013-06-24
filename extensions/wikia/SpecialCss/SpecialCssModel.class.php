<?php
class SpecialCssModel extends WikiaModel {
	/**
	 * @desc The article page name of CSS file of which content we display in the editor
	 */
	const CSS_FILE_NAME = 'Wikia.css';
	
	/**
	 * @desc User avatar size
	 */
	const USER_AVATAR_SIZE = 25;
	
	/**
	 * @desc The category of blogposts we pull data from
	 */
	const UPDATES_CATEGORY = 'CSS_Updates';
	
	/**
	 * @desc The section number we pull content from
	 */
	const UPDATE_SECTION_IN_BLOGPOST = 2;
	
	/**
	 * @desc Regex pattern used to extract h3 tags
	 * 
	 * @see SpecialCssModel::removeHeadline() 
	 * @see SpecialCssModel::addAnchorToPostUrl()
	 */
	const MEDIAWIKI_H3_PATTERN = '/===[^=]+===\s*/';
	
	/**
	 * @desc Limit of characters per one post snippet
	 */
	const SNIPPET_CHAR_LIMIT = 150;
	
	/**
	 * @desc Memcache key for CSS Updates
	 */
	const MEMC_KEY = 'css-chrome-updates';
	
	/**
	 * @var array List of skins for which we would like to use SpecialCss for editing css file
	 */
	public static $supportedSkins = array( 'oasis' );

	/**
	 * @desc Retruns css file name
	 * 
	 * @return string
	 */
	public function getCssFileName() {
		return self::CSS_FILE_NAME;
	}

	/**
	 * @desc Returns Wikia.css article's content
	 * 
	 * @return Return|string
	 */
	public function getCssFileContent() {
		$cssArticle = $this->getCssFileArticle( $this->getCssFileArticleId() );
		return ($cssArticle instanceof Article) ? $cssArticle->getContent() : '';
	}

	/**
	 * @desc Returns Title instance for Wikia.css article or null
	 * 
	 * @return null|Title
	 */
	public function getCssFileTitle() {
		return Title::newFromText( $this->getCssFileName(), NS_MEDIAWIKI);
	}

	/**
	 * @desc Returns article id of Wikia.css or null
	 * 
	 * @return int|null
	 */
	public function getCssFileArticleId() {
		wfProfileIn(__METHOD__);
		
		$title = $this->getCssFileTitle();
		$articleId = null;
		
		if( $title instanceof Title ) {
			$articleId = $this->getCssFileTitle()->getArticleId();
		}

		wfProfileOut(__METHOD__);
		return $articleId;
	}

	/**
	 * @desc Returns an article instance from given article id
	 * 
	 * @param $articleId
	 * @return Article|null
	 */
	public function getCssFileArticle($articleId) {
		return Article::newFromID($articleId);
	}

	/**
	 * @desc Compares passed article id with Wikia.css article id
	 * 
	 * @param $articleId
	 * @return bool
	 */
	public function isWikiaCssArticle($articleId) {
		return ($articleId == $this->getCssFileArticleId() );
	}

	/**
	 * @desc Returns url for Special:CSS page
	 *
	 * @param bool $full
	 * @param array|null $params optional associative array with query parameters
	 * @return string
	 */
	public function getSpecialCssUrl($full = false, $params = null) {
		wfProfileIn(__METHOD__);
		
		$title = $this->getSpecialCssTitle();
		if( !$full ) {
			$url = $title->getLocalURL( $params );
		} else {
			$url = $title->getFullUrl( $params );
		}

		wfProfileOut(__METHOD__);
		return $url;
	}

	/**
	 * @desc Returns Title instance for Special:CSS page
	 * 
	 * @return Title
	 */
	public function getSpecialCssTitle() {
		return SpecialPage::getTitleFor('CSS');
	}

	/**
	 * @desc Saving CSS content
	 *
	 * @param string $content
	 * @param string $summary
	 * @param bool $isMinor
	 * @param User $user
	 * @return bool if saving was successful
	 */
	public function saveCssFileContent($content, $summary, $isMinor, $user) {
		$cssTitle = $this->getCssFileTitle();
		$flags = 0;
		if ( $cssTitle instanceof Title) {
			$aid = $cssTitle->getArticleID( Title::GAID_FOR_UPDATE );
			$flags |= ( $aid == 0 ) ? EDIT_NEW : EDIT_UPDATE;
			if ( $isMinor ) {
				$flags |= EDIT_MINOR;
			}
			$article = new Article($cssTitle);
			$status = $article->doEdit($content, $summary, $flags, false, $user);
			return $status;
		}
		return Status::newFatal('special-css-saving-internal-error');
	}

	/**
	 * @desc Returns an array of 20 last CSS updates
	 *
	 * @param array $postsParams parameters for api to fetch blog data
	 * @param array $revisionsParams parameters for api to fetch revisions data including user info
	 * @return array
	 */
	public function getCssUpdatesData($postsParams = [], $revisionsParams = []) {
		$cssUpdatesPosts = WikiaDataAccess::cache(
			wfSharedMemcKey(self::MEMC_KEY),
			60 * 60 * 24,
			function () use ($postsParams, $revisionsParams) {
				$cssUpdatesPosts = [];
				$cssUpdatesPostsData = $this->getCssPostsApiData($postsParams);

				if ( !empty( $cssUpdatesPostsData ) ) {
					$ids = $this->getBlogsIds( $cssUpdatesPostsData );
					$cssRevisionsData = $this->getCssRevisionsApiData( $ids, $revisionsParams );

					foreach ( $cssUpdatesPostsData as $postData ) {
						$cssUpdatesPosts[] = $this->prepareCssUpdateData($cssRevisionsData, $postData);
					}
				}
				
				return $cssUpdatesPosts;
			}
		);

		return $cssUpdatesPosts;
	}

	/**
	 * Get url to community wiki to page with all CSS updates
	 *
	 * @return string
	 */
	public function getCssUpdatesUrl() {
		$title = GlobalTitle::newFromText(
			self::UPDATES_CATEGORY,
			NS_CATEGORY,
			WikiFactory::DBtoId($this->getCommunityDbName())
		);

		return $title->getFullURL();
	}

	/**
	 * @desc Returns an array with correct elements from given api results
	 * 
	 * @param array $cssRevisionsData results from API call with request of revision's info
	 * @param array $postData results from API call with request of posts (articles) list in a category
	 * 
	 * @return array
	 */
	private function prepareCssUpdateData($cssRevisionsData, $postData) {
		$cssUpdatePost = [
			'title' => '',
			'url' => '',
			'userAvatar' => '',
			'userUrl' => '',
			'userName' => '',
			'timestamp' => '',
			'text' => '',
		];
		
		$pageId = $postData['pageid'];
		$blogTitle = GlobalTitle::newFromText( $postData['title'], NS_MAIN, WikiFactory::COMMUNITY_CENTRAL );
		$blogTitleText = $blogTitle->getText();

		$lastRevisionUser = $cssRevisionsData[$pageId]['revisions'][0]['user'];
		$blogUser = $this->getUserFromTitleText( $blogTitleText, $lastRevisionUser);
		$userPage = GlobalTitle::newFromText( $blogUser, NS_USER, WikiFactory::COMMUNITY_CENTRAL );

		if( $blogTitle instanceof GlobalTitle && $userPage instanceof GlobalTitle ) {
			$timestamp = $cssRevisionsData[$pageId]['revisions'][0]['timestamp'];
			$sectionText = $cssRevisionsData[$pageId]['revisions'][0]['*'];

			$cssUpdatePost = [
				'title' => $this->getAfterLastSlashText( $blogTitleText ),
				'url' => trim( $this->getFormattedUrl($blogTitle->getFullURL()) . $this->addAnchorToPostUrl( $sectionText ) ),
				'userAvatar' => AvatarService::renderAvatar( $blogUser, 25 ),
				'userUrl' => $userPage->getFullUrl(),
				'userName' => $blogUser,
				'timestamp' => $this->wg->Lang->date( wfTimestamp( TS_MW, $timestamp ) ),
				'text' => $this->getPostSnippet($blogTitle, $sectionText),
			];
		}
		
		return $cssUpdatePost;
	}

	/**
	 * @desc Removes wikitext H3, truncates $sectionText and parse wikitext
	 * 
	 * @param Title $blogTitle
	 * @param String $sectionText
	 * 
	 * @return String
	 */
	private function getPostSnippet($blogTitle, $sectionText) {
		$output = $this->removeHeadline( $sectionText );
		$output = $this->wg->Lang->truncate( $output, self::SNIPPET_CHAR_LIMIT, wfMessage( 'ellipsis' )->text() );
		$output = $this->getParsedText($output, $blogTitle);
		
		return $output;
	}

	/**
	 * @desc Gets first H3 tag content and makes an anchor of it if found
	 * 
	 * @param String $sectionText
	 * 
	 * @return string
	 */
	private function addAnchorToPostUrl($sectionText) {
		$anchor = '';
		$headlines = [];
		
		if( preg_match( self::MEDIAWIKI_H3_PATTERN, $sectionText, $headlines ) ) {
			$anchor = '#' . str_replace( ' ', '_', str_replace('=', '', $headlines[0]) );
		}
		
		return $anchor;
	}

	/**
	 * @desc Removes wikitext's H3 tags from given text
	 * 
	 * @param String $text
	 * @return mixed
	 */
	private function removeHeadline($text) {
		return preg_replace(self::MEDIAWIKI_H3_PATTERN, '', $text);
	}

	/**
	 * @desc Parse given wiki text and returns the HTML output
	 * 
	 * @param String $text
	 * @param Title $title
	 * 
	 * @return mixed
	 */
	private function getParsedText( $text, $title ) {
		$output = $this->wg->Parser->parse( $text, $title, new ParserOptions() ); /** @var ParserOutput $output */
		return $output->getText();
	}

	/**
	 * @desc Removes from given string first slash and the string before it
	 * 
	 * @param String $titleText
	 *
	 * @return string
	 */
	private function getAfterLastSlashText($titleText) {
		$result = $titleText;
		$slashPosition = mb_strrpos($titleText, '/');
		
		if( $slashPosition !== false ) {
			$slashPosition++;
			$result = mb_strcut( $titleText, $slashPosition );
		}
		
		return trim( $result, '/' );
	}

	/**
	 * @desc Gets username from title's text
	 * 
	 * @param String $titleText Title::getText() result moslty
	 * @param String $fallbackUser 
	 * 
	 * @return string
	 */
	private function getUserFromTitleText($titleText, $fallbackUser) {
		$userName = str_replace( $this->getAfterLastSlashText($titleText), '', $titleText );
		$userName = trim( $userName, '/' );
		$userName = $this->getAfterLastSlashText($userName);
		$userArray = explode(':', $userName);
		
		if( count($userArray) > 1 ) {
			$userName = $userArray[1];
		} 
		
		if( empty($userName) ) {
			$userName = $fallbackUser;
		}
		
		return $userName;
	}

	/**
	 * @desc Only for devboxes - change url to community central
	 */
	private function getFormattedUrl($url) {
		global $wgDevelEnvironment;
		if ( $wgDevelEnvironment ) {
			$url = str_replace('http://wikia.', 'http://community.', $url);
		}
		return $url;
	}

	/**
	 * @desc Returns information about 20 last CSS updates (page id, namespace and post title)
	 *
	 * @param array $params: action, list, cmtitle, cmlimit, cmsort, cmdir which are send to MW API: http://en.wikipedia.org/w/api.php
	 * @return array
	 */
	private function getCssPostsApiData($params) {
		$defaultParams = $this->getDefaultBlogParams();
		$params = array_merge($defaultParams, $params);
		$blogs = $this->getApiData($params);

		return isset( $blogs['query']['categorymembers'] ) ? $blogs['query']['categorymembers'] : [];
	}

	/**
	 * @desc Returns information about 20 last revisions of CSS updates (user name, timestamp, post content)
	 *
	 * @param array $ids array of integers which are page ids
	 * @param array $params: action, prop, rvprop, rvsection, pageids which are send to MW API: http://en.wikipedia.org/w/api.php 
	 * 
	 * @return array
	 */
	private function getCssRevisionsApiData($ids, $params) {
		$defaultParams = $this->getDefaultRevisionParams($ids);
		$params = array_merge($defaultParams, $params);
		$revisions = $this->getApiData($params);

		return isset( $revisions['query']['pages'] ) ? $revisions['query']['pages'] : [];
	}

	/**
	 * @desc Returns data from API based on parameters
	 *
	 * @param array $params more documentation: http://en.wikipedia.org/w/api.php
	 * 
	 * @return mixed
	 */
	private function getApiData($params) {
		$dbName = $this->getCommunityDbName();
		$data = ApiService::foreignCall($dbName, $params);

		return $data;
	}

	/**
	 * Get community wiki db name from which we'll take blog posts for css update
	 *
	 * @return string
	 */
	private function getCommunityDbName() {
		global $wgDevelEnvironment;
		$dbName = 'wikia';

		if ( $wgDevelEnvironment ) {
			$dbName = 'community';
		}

		return $dbName;
	}


	/**
	 * @desc Returns array with page ids based on blog data from api
	 *
	 * @param $cssBlogsJson
	 * @return string
	 */
	private function getBlogsIds($cssBlogsJson) {
		$pageIds = [];

		foreach ( $cssBlogsJson as $blog ) {
			$pageIds[] = $blog['pageid'];
		}

		$ids = implode('|', $pageIds);

		return $ids;
	}

	/**
	 * @desc Returns default parameters for fetching blog data
	 *
	 * @return array
	 */
	private function getDefaultBlogParams() {
		return [
			'action' => 'query',
			'list' => 'categorymembers',
			'cmtitle' => 'Category:' . self::UPDATES_CATEGORY,
			'cmlimit' => '20',
			'cmsort' => 'timestamp',
			'cmdir' => 'desc'
		];
	}

	/**
	 * @desc Returns default parameters for fetching revision data
	 *
	 * @param string $ids page ids separated by '|'
	 * @return array
	 */
	private function getDefaultRevisionParams($ids = '') {
		return [
			'action' => 'query',
			'prop' => 'revisions',
			'rvprop' => 'content|user|timestamp',
			'rvsection' => self::UPDATE_SECTION_IN_BLOGPOST,
			'pageids' => $ids
		];
	}
}
