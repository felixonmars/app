/*
 * @define sections
 * module used to handle sections on wikiamobile
 *
 * @author Jakub Olek
 * @author Federico "Lox" Lucignano <federico(at)wikia-inc.com>
 */

define('sections', ['jquery'], function($){
	'use strict';

	var d = document,
		sections = $('h2[id],h3[id],h4[id]', document.getElementById('wkPage')).toArray(),
		l = sections.length,
		lastSection,
		timeout;

	function scrollTo(header){
		header[0].scrollIntoView();
	}

	function current(){
		var top = window.scrollY,
			i = 0;

		for(;i < l;i++) {
			if(sections[i].offsetTop - 5 > top) {
				break;
			}
		}

		return $(sections[i-1]);
	}

	lastSection = current();

	function onScroll(){
		var currentSection = current();

		if(currentSection && !currentSection.is(lastSection)) {
			$(d).trigger('section:changed', {
				section: currentSection,
				id: currentSection.length ? currentSection[0].id : undefined
			});

			lastSection = currentSection;
		}

		timeout = null;
	}

	window.addEventListener('scroll', function(){
		if(!timeout) {
			timeout = setTimeout(onScroll, 100);
		}
	});

	return {
		list: sections,
		scrollTo: scrollTo,
		current: current
	};
});
