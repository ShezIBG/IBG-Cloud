import { Component, Input, OnChanges } from '@angular/core';

@Component({
	selector: 'app-icon-with-badge',
	templateUrl: './icon-with-badge.component.html',
	styleUrls: ['./icon-with-badge.component.css']
})
export class IconWithBadgeComponent implements OnChanges {

	@Input() icon;
	@Input() iconSuffix;
	@Input() badge;
	@Input() badgeColor;
	@Input() caption;

	lengthClass = 'length-1';

	ngOnChanges() {
		switch (('' + this.badge).length) {
			case 1: this.lengthClass = 'length-1'; break;
			case 2: this.lengthClass = 'length-2'; break;
			case 3: this.lengthClass = 'length-3'; break;
			default: this.lengthClass = 'length-4'; break;
		}
	}

}
