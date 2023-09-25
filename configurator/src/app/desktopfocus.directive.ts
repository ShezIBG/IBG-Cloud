import { Util } from './util';
import { Directive, ElementRef, AfterViewInit } from '@angular/core';

declare var $: any;

@Directive({
	selector: '[desktopfocus]'
})
export class DesktopfocusDirective implements AfterViewInit {

	constructor(private el: ElementRef) { }

	ngAfterViewInit() {
		// Don't auto-focus elements on mobile or tablet
		if (Util.isMobileOrTablet) return;

		if (this.el.nativeElement['focus']) this.el.nativeElement['focus']();
	}

}
