require([
	'JSMessages',
	'wikia.cookies',
	'wikia.tracker',
	'jquery',
	'wikia.querystring',
	'wikia.window'
], function(msg, cookies, tracker, $, Querystring, window){
	/**
	 * If the pageview is generated by a device that is touch capable
	 */

	var linksWrapper = $('.global-footer ul').first();

	if(Wikia.isTouchScreen() && linksWrapper.exists()){
		msg.get('Oasis-mobile-switch').then(function(){
			var mobileSwitch = $('<li><a href="#">' + msg('oasis-mobile-site') + '</a></li>');

			mobileSwitch.on('click', function(ev){
				ev.preventDefault();
				ev.stopPropagation();

				cookies.set('useskin', 'wikiamobile', {
					domain: window.wgCookieDomain,
					path: window.wgCookiePath
				});

				tracker.track({
					category: 'corporate-footer',
					action: tracker.ACTIONS.CLICK_LINK_BUTTON,
					label: 'mobile-switch',
					trackingMethod: 'analytics'
				});


				Querystring().setVal('useskin', 'wikiamobile').addCb().goTo();
			});

			linksWrapper.append(mobileSwitch);
		});
	}
});
