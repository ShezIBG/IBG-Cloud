import { Component, Input, OnInit } from '@angular/core';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-building-header',
	templateUrl: './mobile-building-header.component.html'
})
export class MobileBuildingHeaderComponent implements OnInit {

	@Input() moduleName;

	constructor(
		public mobile: MobileService
	) { }

	ngOnInit() {
	}

}
