require(['jquery', 'wikia.window', 'wikia.tracker'], function($, window, tracker) {	
	'use strict';

	var WIDTH_SAMPLING_RATIO = 1;
		
	if (Math.random() * 100 < WIDTH_SAMPLING_RATIO) {
	
		var $article = $('.WikiaArticle'),
			articleLength = $article.height(),
			label = '',
			LENGTH_BORDER = 800,
			SCALE = 100,
			track,
			windowWidth = $(window).width(),
			widthCategory = Math.floor(windowWidth / SCALE);

		track = Wikia.Tracker.buildTrackingFunction({
			action: Wikia.Tracker.ACTIONS.IMPRESSION,
			category: 'articleContentLengthTest',
			trackingMethod: 'ga'
		});

		if (articleLength > LENGTH_BORDER) {
			label = 'long-';
		} else {
			label = 'short-';
		};
		track({
			label: label + widthCategory
		})
	}	
});
