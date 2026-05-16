(function () {
	'use strict';

	function initCarousel(root) {
		if (root.dataset.layout !== 'carousel') {
			return;
		}

		var track = root.querySelector('.truspilot-review__track');
		var slides = Array.prototype.slice.call(root.querySelectorAll('[data-truspilot-slide]'));
		var prev = root.querySelector('[data-truspilot-prev]');
		var next = root.querySelector('[data-truspilot-next]');
		var dotsWrap = root.querySelector('[data-truspilot-dots]');
		var autoplay = root.dataset.autoplay === 'true';
		var interval = parseInt(root.dataset.interval || '5500', 10);
		var current = 0;
		var timer = null;

		if (!track || slides.length < 2) {
			return;
		}

		function goTo(index) {
			current = (index + slides.length) % slides.length;
			track.style.transform = 'translateX(' + current * -100 + '%)';

			if (dotsWrap) {
				Array.prototype.forEach.call(dotsWrap.children, function (dot, dotIndex) {
					dot.classList.toggle('is-active', dotIndex === current);
					dot.setAttribute('aria-current', dotIndex === current ? 'true' : 'false');
				});
			}
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			if (!autoplay || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				return;
			}
			stop();
			timer = window.setInterval(function () {
				goTo(current + 1);
			}, interval);
		}

		if (dotsWrap) {
			dotsWrap.innerHTML = '';
			slides.forEach(function (slide, index) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'truspilot-review__dot';
				dot.setAttribute('aria-label', 'Show review ' + (index + 1));
				dot.addEventListener('click', function () {
					goTo(index);
					start();
				});
				dotsWrap.appendChild(dot);
			});
		}

		if (prev) {
			prev.addEventListener('click', function () {
				goTo(current - 1);
				start();
			});
		}

		if (next) {
			next.addEventListener('click', function () {
				goTo(current + 1);
				start();
			});
		}

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', start);

		goTo(0);
		start();
	}

	function init() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-truspilot-review]'), initCarousel);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
