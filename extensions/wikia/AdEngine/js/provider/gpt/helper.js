/*global define, setTimeout, require*/
/*jshint maxlen:125, camelcase:false, maxdepth:7*/
define('ext.wikia.adEngine.provider.gpt.helper', [
	'wikia.document',
	'wikia.log',
	'wikia.window',
	'ext.wikia.adEngine.adContext',
	'ext.wikia.adEngine.adLogicPageParams',
	'ext.wikia.adEngine.provider.gpt.adDetect',
	'ext.wikia.adEngine.provider.gpt.adElement',
	'ext.wikia.adEngine.provider.gpt.googleTag',
	'ext.wikia.adEngine.slotTweaker',
	require.optional('ext.wikia.adEngine.provider.gpt.sourcePointTag'),
	require.optional('ext.wikia.adEngine.provider.gpt.sraHelper'),
	require.optional('ext.wikia.adEngine.slot.scrollHandler')
], function (
	doc,
	log,
	window,
	adContext,
	adLogicPageParams,
	adDetect,
	AdElement,
	GoogleTag,
	slotTweaker,
	SourcePointTag,
	sraHelper,
	scrollHandler
) {
	'use strict';

	var logGroup = 'ext.wikia.adEngine.provider.gpt.helper',
		context = adContext.getContext(),
		googleApi = new GoogleTag(),
		slotsToRecover = [],
		sourcePointInitialized = false;

	function isRecoveryEnabled() {
		return !!(context.opts.sourcePoint && SourcePointTag);
	}

	function isBlocking() {
		return !!(isRecoveryEnabled() && window.ads && window.ads.runtime.sp.blocking);
	}

	function isRecoverable(slotName, recoverableSlots) {
		return isRecoveryEnabled() && recoverableSlots.indexOf(slotName) !== -1;
	}

	function recoverSlots() {
		if (!isBlocking()) {
			return;
		}
		log(['Starting recovery', slotsToRecover], 'debug', logGroup);
		while (slotsToRecover.length){
			window.ads.runtime.sp.slots.push([slotsToRecover.shift()]);
		}
	}

	function loadRecovery() {
		if (sourcePointInitialized) {
			return;
		}
		log('SourcePoint recovery enabled', 'debug', logGroup);
		sourcePointInitialized = true;
		googleApi = new SourcePointTag();
		recoverSlots();
	}

	function loadSourcePoint() {
		if (isBlocking()) {
			loadRecovery();
		} else {
			doc.addEventListener('sp.blocking', function () {
				loadRecovery();
			});
		}
	}

	/**
	 * Push ad to queue and flush if it should be
	 *
	 * @param {string}   slotName           - slot name
	 * @param {Object}   slotElement        - slot div container
	 * @param {string}   slotPath           - slot path
	 * @param {Object}   slotTargeting      - slot targeting details
	 * @param {Object}   extra              - optional parameters
	 * @param {function} extra.success      - on success callback
	 * @param {function} extra.error        - on error callback
	 * @param {boolean}  extra.sraEnabled   - whether to use Single Request Architecture
	 * @param {string}   extra.forcedAdType - ad type for callbacks info
	 */
	function pushAd(slotName, slotElement, slotPath, slotTargeting, extra) {
		var count,
			element,
			recoverableSlots = extra.recoverableSlots || [],
			shouldPush = !isBlocking() || isRecoverable(slotName, recoverableSlots);

		extra = extra || {};
		slotTargeting = JSON.parse(JSON.stringify(slotTargeting)); // copy value

		if (scrollHandler) {
			count = scrollHandler.getReloadedViewCount(slotName);
			if (count !== null) {
				slotTargeting.rv = count.toString();
			}
		}

		element = new AdElement(slotName, slotPath, slotTargeting);

		function callSuccess(adInfo) {
			if (adInfo && adInfo.adType === 'collapse') {
				slotTweaker.hide(element.getSlotName(), isBlocking());
			}
			if (typeof extra.success === 'function') {
				extra.success(adInfo);
			}
		}

		function callError(adInfo) {
			slotTweaker.hide(element.getId());
			if (typeof extra.error === 'function') {
				adInfo = adInfo || {};
				adInfo.method = 'hop';
				extra.error(adInfo);
			}
		}

		function queueAd() {
			log(['queueAd', slotName, slotElement, element], 'debug', logGroup);
			slotElement.appendChild(element.getNode());

			googleApi.addSlot(element);
		}

		function onAdLoadCallback(slotElementId, gptEvent, iframe) {
			// IE doesn't allow us to inspect GPT iframe at this point.
			// Let's launch our callback in a setTimeout instead.
			setTimeout(function () {
				log(['onAdLoadCallback', slotElementId], 'info', logGroup);
				adDetect.onAdLoad(slotElementId, gptEvent, iframe, callSuccess, callError, extra.forcedAdType);
			}, 0);
		}

		function gptCallback(gptEvent) {
			log(['gptCallback', element.getId(), gptEvent], 'info', logGroup);
			element.updateDataParams(gptEvent);
			googleApi.onAdLoad(slotName, element, gptEvent, onAdLoadCallback);
		}

		if (!googleApi.isInitialized()) {
			if (isRecoveryEnabled()) {
				googleApi.init(loadSourcePoint);
			} else {
				googleApi.init();
			}
			googleApi.setPageLevelParams(adLogicPageParams.getPageLevelParams());
		}

		if (!shouldPush) {
			log(['Push blocked', slotName], 'debug', logGroup);
			return;
		}

		if (!isBlocking()) {
			slotsToRecover.push(slotName);
		}

		log(['pushAd', slotName], 'info', logGroup);
		if (!slotTargeting.flushOnly) {
			googleApi.registerCallback(element.getId(), gptCallback);
			googleApi.push(queueAd);
		}

		if (!extra.sraEnabled || sraHelper.shouldFlush(slotName)) {
			log('flushing', 'debug', logGroup);
			googleApi.flush();
		}

		if (slotTargeting.flushOnly) {
			callSuccess();
		}
	}

	adContext.addCallback(function () {
		if (googleApi.isInitialized()) {
			googleApi.setPageLevelParams(adLogicPageParams.getPageLevelParams());
		}
	});

	return {
		pushAd: pushAd
	};
});
