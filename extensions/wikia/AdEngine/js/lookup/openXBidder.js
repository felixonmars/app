/*global define*/
/*jshint camelcase:false*/
/*jshint maxdepth:5*/
define('ext.wikia.adEngine.lookup.openXBidder', [
	'ext.wikia.adEngine.adTracker',
	'wikia.document',
	'wikia.log',
	'wikia.window'
], function (adTracker, doc, log, win) {
	'use strict';

	var logGroup = 'ext.wikia.adEngine.lookup.openXBidder',
		oxResponse = false,
		oxTiming,
		bestPricePointForSize = {
			'1x6': null,
			'3x2': null,
			'3x5': null,
			'3x6': null,
			'7x9': null
		},
		called = false,
		priceTimeout = 't',
		slots = {
			HOME_TOP_LEADERBOARD: '728x90',
			HOME_TOP_RIGHT_BOXAD: '300x250',
			INCONTENT_BOXAD_1: '300x250',
			LEFT_SKYSCRAPER_2: '160x600',
			LEFT_SKYSCRAPER_3: '160x600',
			PREFOOTER_LEFT_BOXAD: '300x250',
			PREFOOTER_RIGHT_BOXAD: '300x250',
			TOP_LEADERBOARD: '728x90',
			TOP_RIGHT_BOXAD: '300x250',
			MOBILE_IN_CONTENT: '300x250',
			MOBILE_PREFOOTER: '300x250',
			MOBILE_TOP_LEADERBOARD: '320x50'
		},
		sizeMapping = {
			'728x90': '7x9',
			'300x250': '3x2',
			'160x600': '1x6',
			'320x50': '3x5'
		};

	function getAds() {
		var ads = [],
			size,
			slotName,
			slotPath,
			src = 'gpt';

		for (slotName in slots) {
			if (slots.hasOwnProperty(slotName)) {
				size = slots[slotName];
				// @TODO - ADEN-2447 - Remove hardcoded wiki details
				slotPath = [
					'/5441', 'wka.life', '_prowrestling', 'article', src, slotName
				].join('/');
				ads.push([
					slotPath,
					[size],
					slotName
				]);
			}
		}

		return ads;
	}

	function trackState(trackEnd) {
		log(['trackState', oxResponse], 'debug', logGroup);

		var eventName,
			oxSize,
			data = {};

		if (oxResponse) {
			eventName = 'lookupSuccess';
			for (oxSize in bestPricePointForSize) {
				if (bestPricePointForSize.hasOwnProperty(oxSize) && bestPricePointForSize[oxSize] !== null) {
					data['ox' + oxSize] = 'p' + bestPricePointForSize[oxSize];
				}
			}
		} else {
			eventName = 'lookupError';
		}

		if (trackEnd) {
			eventName = 'lookupEnd';
		}

		adTracker.track(eventName + '/ox', data || '(unknown)', 0);
	}

	function setPrice(size, price) {
		var mappedSize = sizeMapping[size];

		if (mappedSize && (bestPricePointForSize[mappedSize] === null || bestPricePointForSize[mappedSize] < price)) {
			bestPricePointForSize[mappedSize] = price;
		}
	}

	function onResponse() {
		oxTiming.measureDiff({}, 'end').track();
		log('OpenX bidder done', 'info', logGroup);
		var prices = win.OX.dfp_bidder.getPriceMap(),
			slotName;

		for (slotName in prices) {
			if (prices.hasOwnProperty(slotName) && prices[slotName].price !== priceTimeout) {
				setPrice(prices[slotName].size, prices[slotName].price);
			}
		}
		oxResponse = true;
		log(['OpenX bidder best prices', bestPricePointForSize], 'info', logGroup);

		trackState(true);
	}

	function call() {
		log('call', 'debug', logGroup);
		var openx = doc.createElement('script'),
			node = doc.getElementsByTagName('script')[0];

		oxTiming = adTracker.measureTime('ox_bidder', {}, 'start');
		oxTiming.track();
		win.OX_dfp_ads = getAds();

		win.OX_dfp_options = {
			callback: onResponse
		};

		openx.async = true;
		openx.type = 'text/javascript';
		openx.src = '//ox-d.wikia.servedbyopenx.com/w/1.0/jstag?nc=5441-Wikia';

		node.parentNode.insertBefore(openx, node);

		called = true;
	}

	function wasCalled() {
		log(['wasCalled', called], 'debug', logGroup);
		return called;
	}

	function getSlotParams(slotName) {
		log(['getSlotParams', slotName], 'debug', logGroup);
		var oxSlots = [],
			slotSize,
			mappedSize;

		if (oxResponse && slots[slotName]) {
			slotSize = slots[slotName];
			mappedSize = sizeMapping[slotSize];
			if (bestPricePointForSize[mappedSize] !== null) {
				oxSlots.push('ox' + mappedSize + 'p' + bestPricePointForSize[mappedSize]);
			}

			log(['getSlotParams - oxSlots: ', oxSlots], 'debug', logGroup);
		} else {
			log(['getSlotParams - no oxSlots since ads has been already displayed', slotName], 'debug', logGroup);
		}

		if (oxSlots.length) {
			return {
				oxslots: oxSlots
			};
		}

		return {};
	}

	return {
		call: call,
		getSlotParams: getSlotParams,
		trackState: trackState,
		wasCalled: wasCalled
	};
});
