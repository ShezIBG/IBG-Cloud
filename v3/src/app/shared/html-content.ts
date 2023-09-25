import { Component, Input } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
	selector: 'app-html-content',
	styles: ['p { margin: 0 0 10px; border: 1px solid red !important; }'],
	template: '<div [innerHtml]="sanitizer.bypassSecurityTrustHtml(html)"></div>'
})
export class HtmlContentComponent {

	@Input() html;

	constructor(
		public sanitizer: DomSanitizer
	) {	}

}
