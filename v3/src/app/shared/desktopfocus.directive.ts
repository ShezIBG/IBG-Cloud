import { Directive, ElementRef, AfterViewInit } from '@angular/core';

import { Util } from './util';

@Directive({
	selector: '[appDesktopFocus]'
})
export class DesktopfocusDirective implements AfterViewInit {

	constructor(private el: ElementRef) { }

	ngAfterViewInit() {
		// Don't auto-focus elements on mobile or tablet
		if (Util.isMobileOrTablet) return;

		if (this.el.nativeElement['focus']) this.el.nativeElement['focus']();
	}

}
