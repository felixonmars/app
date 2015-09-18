require([
	'wikia.window', 'wikia.document', 'wikia.tracker'
], function(win, doc, tracker) {
	var slider,
		imageNumber,
		imageWidth = 270,
		currentPostion = 0,
		currentImage = 0;

	function init() {
		slider = doc.getElementsByClassName('image_slider')[0];

		if (!slider) {
			return;
		}

		imageNumber = slider.children.length;

		slider.style.width = parseInt(imageWidth * imageNumber) + 'px';

		doc.getElementsByClassName('prev')[0].onclick = function(){ onClickPrev(); };
		doc.getElementsByClassName('next')[0].onclick = function(){ onClickNext(); };

		generatePager(imageNumber);
	}

	function animate(opts){
		var start = new Date;
		var id = setInterval(function(){
			var timePassed = new Date - start,
				progress = timePassed / opts.duration;
			if (progress > 1) {
				progress = 1;
			}
			var delta = opts.delta(progress);
			opts.step(delta);
			if (progress == 1) {
				clearInterval(id);
				opts.callback();
			}
		}, opts.delay || 100);
	}

	function slideTo(imageToGo) {
		var numOfImageToGo = imageToGo - currentImage;
		currentPostion = -1 * currentImage * imageWidth;

		var opts = {
			duration: 500,
			delta: function(p){return p;},
			step: function(delta) {
				slider.style.left = parseInt(currentPostion - delta * imageWidth * numOfImageToGo) + 'px';
			},
			callback:function(){
				currentImage = imageToGo;
			}
		};

		animate(opts);
	}

	function onClickPrev() {
		var targetIndex = currentImage == 0 ? imageNumber - 1 : currentImage - 1;

		slideTo(targetIndex);
	}

	function onClickNext() {
		var targetIndex = currentImage == imageNumber - 1 ? 0 : currentImage + 1;

		slideTo(targetIndex);
	}

	function generatePager(imageNumber) {
		var i, li, pagerDiv = doc.getElementsByClassName('pi-pager')[0];

		for (i = 0; i < imageNumber; i++){
			li = doc.createElement('li');
			pagerDiv.appendChild(li);

			li.onclick = function(i) {
				return function() {
					slideTo(i);
				}
			} (i);
		}
	}

	window.onload = init;
});
