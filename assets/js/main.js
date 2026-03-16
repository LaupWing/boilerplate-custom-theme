/**
 * Main theme JavaScript.
 *
 * @package Boilerplate
 */

(function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Language Switcher Popover
	// -------------------------------------------------------------------------
	const btn = document.getElementById('bp-lang-btn');
	const popover = document.getElementById('bp-lang-popover');
	const chevron = document.getElementById('bp-lang-chevron');

	if (!btn || !popover) return;

	let isOpen = false;

	function open() {
		isOpen = true;
		popover.classList.remove('opacity-0', 'invisible', 'scale-95');
		popover.classList.add('opacity-100', 'visible', 'scale-100');
		btn.setAttribute('aria-expanded', 'true');
		chevron.classList.add('rotate-180');
	}

	function close() {
		isOpen = false;
		popover.classList.add('opacity-0', 'invisible', 'scale-95');
		popover.classList.remove('opacity-100', 'visible', 'scale-100');
		btn.setAttribute('aria-expanded', 'false');
		chevron.classList.remove('rotate-180');
	}

	function toggle() {
		isOpen ? close() : open();
	}

	// Toggle on button click
	btn.addEventListener('click', (e) => {
		e.stopPropagation();
		toggle();
	});

	// Close on outside click
	document.addEventListener('click', (e) => {
		if (isOpen && !popover.contains(e.target) && !btn.contains(e.target)) {
			close();
		}
	});

	// Close on Escape
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && isOpen) {
			close();
			btn.focus();
		}
	});
})();
