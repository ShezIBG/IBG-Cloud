import { Directive, ElementRef, AfterViewInit } from '@angular/core';

@Directive({
	selector: '[appAutoFocus]'
})
export class AutofocusDirective implements AfterViewInit {

	constructor(private el: ElementRef) { }

	ngAfterViewInit() {
		if (this.el.nativeElement['focus']) this.el.nativeElement['focus']();
	}

}
