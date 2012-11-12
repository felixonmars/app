<?php
class RelatedForumDiscussionController extends WikiaController {
	
	public function init() {
		
	}
	
	public function index() {
		// mock data
		$messages = array(
			array(
				'metaTitle' => 'Placeholder main title',
				'threadUrl' => '#',
				'totalReplies' => $this->wf->Msg('forum-related-discussion-total-replies', 29),
				'replies' => array(
					array(
						'userName' => 'SomeDude',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body goes here',
						'timeStamp' => '3 hour ago',
					),
					array(
						'userName' => 'SomeDude2',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body 2 goes here',
						'timeStamp' => '1 hour ago',
					),
				),
			),
			array(
				'metaTitle' => 'Placeholder main title',
				'threadUrl' => '#',
				'totalReplies' => $this->wf->Msg('forum-related-discussion-total-replies', 14),
				'replies' => array(
					array(
						'userName' => 'SomeDude',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body goes here',
						'timeStamp' => '3 hour ago',
					),
					array(
						'userName' => 'SomeDude2',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body 2 goes here',
						'timeStamp' => '1 hour ago',
					),
				),
			),
		);
		
		// resources
		// load assets related to this if there is a discussions section
		$this->response->addAsset( 'extensions/wikia/Forum/css/RelatedForumDiscussion.scss' );
		
		$messages = array();
		
		// don't render anything if there are no discussions for this article
		if(empty($messages)) {
			$this->forward( 'RelatedForumDiscussion', 'zeroState', false );
		} else {
			$this->messages = $messages;
			$this->forward( 'RelatedForumDiscussion', 'relatedForumDiscussion', false );
		}
	}
	
	private function setupCommon($title) {
		// set template rendering to mustache
		//$this->response->setTemplateEngine(WikiaResponse::TEMPLATE_ENGINE_MUSTACHE);
		
		// common data
		$this->sectionHeading = $this->wf->Msg('forum-related-discussion-heading', $title->getText());
		$this->newPostButton = $this->wf->Msg('forum-related-discussion-new-post-button');
		$this->newPostUrl = '#';
		$this->newPostTooltip = $this->wf->Msg('forum-related-discussion-new-post-tooltip', $title->getText());
		$this->blankImgUrl = $this->wf->BlankImgUrl();
	}
	
	public function relatedForumDiscussion() {
		$title = $this->getVal('title', $this->wg->Title);
	
		if(empty($this->messages)) {
			$this->messages = $this->getVal('messages');
		}
		
		$this->setupCommon($title);
	
		// set template data
		$this->seeMoreUrl = "#";
		$this->seeMoreText = "See more";
	}
	
	public function zeroState() {
		$this->setupCommon($this->wg->Title);
	
		$this->creative = $this->wf->MsgExt('forum-related-discussion-zero-state-creative', 'parseinline');
	}
	
	public function checkData() {
		$articleTitle = $this->getVal('articleTitle');
		$title = Title::newFromText($articleTitle);
		if(empty($articleTitle) || empty($title)) {
			$this->replace = false;
			return;
		}
	
		$replace = true;
		$this->replace = $replace;

		// mock data
		$messages = array(
			array(
				'metaTitle' => 'Placeholder main title',
				'threadUrl' => '#',
				'totalReplies' => $this->wf->Msg('forum-related-discussion-total-replies', 29),
				'replies' => array(
					array(
						'userName' => 'SomeDude',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body goes here',
						'timeStamp' => '3 hour ago',
					),
					array(
						'userName' => 'SomeDude2',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body 2 goes here',
						'timeStamp' => '1 hour ago',
					),
				),
			),
			array(
				'metaTitle' => 'Placeholder main title',
				'threadUrl' => '#',
				'totalReplies' => $this->wf->Msg('forum-related-discussion-total-replies', 14),
				'replies' => array(
					array(
						'userName' => 'SomeDude',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body goes here',
						'timeStamp' => '3 hour ago',
					),
					array(
						'userName' => 'SomeDude2',
						'userAvatarUrl' => $this->wf->BlankImgUrl(),
						'userUrl' => '#',
						'messageBody' => 'Message body 2 goes here',
						'timeStamp' => '1 hour ago',
					),
				),
			),
		);
		
		$this->html = $this->app->renderView( "RelatedForumDiscussion", "index", array('messages' => $messages, 'title' => $title) );

	}
	
}