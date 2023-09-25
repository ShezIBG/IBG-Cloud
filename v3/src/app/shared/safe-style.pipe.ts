import { DomSanitizer } from '@angular/platform-browser';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'safeStyle'
})
export class SafeStylePipe implements PipeTransform {

	constructor(private sanitizer: DomSanitizer) { }

	transform(style) {
		return this.sanitizer.bypassSecurityTrustStyle(style);
	}

}
