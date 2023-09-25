import { AppService } from './../../app.service';
import { Component, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-module',
	template: '<router-outlet></router-outlet>'
})
export class ModuleComponent implements OnDestroy {

	constructor(
		public app: AppService
	) { }

	ngOnDestroy() {
		this.app.header.clearAll();
		this.app.sidebar.setMenu([]);
	}

}
