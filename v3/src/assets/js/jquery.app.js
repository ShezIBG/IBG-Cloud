/**
* Theme: Minton Admin Template
* Author: Coderthemes
* Module/App: Main Js
*/

//main app module
(function($) {
	"use strict";

	var App = function () {
		this.VERSION = "2.1.0",
			this.AUTHOR = "Coderthemes",
			this.SUPPORT = "coderthemes@gmail.com",
			this.pageScrollElement = "html, body",
			this.$body = $("body")
	};

	//on doc load
	App.prototype.onDocReady = function (e) {
		FastClick.attach(document.body);

		//RUN RESIZE ITEMS
		$(window).resize(debounce(resizeitems, 100));
		$("body").trigger("resize");

	},
		//initilizing
		App.prototype.init = function () {
			var $this = this;
			//document load initialization
			$(document).ready($this.onDocReady);
		},

		$.App = new App, $.App.Constructor = App

} (window.jQuery),

//initializing main application module
function ($) {
	"use strict";
	$.App.init();
})(window.jQuery);

/* ------------ some utility functions ----------------------- */

var debounce = function (func, wait, immediate) {
	var timeout, result;
	return function () {
		var context = this, args = arguments;
		var later = function () {
			timeout = null;
			if (!immediate) result = func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) result = func.apply(context, args);
		return result;
	};
}

function resizeitems() {
	if ($.isArray(resizefunc)) {
		for (i = 0; i < resizefunc.length; i++) {
			window[resizefunc[i]]();
		}
	}
}
