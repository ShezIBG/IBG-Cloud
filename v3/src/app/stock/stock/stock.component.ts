import { ModuleComponent } from './../../shared/module/module.component';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock',
	template: `<router-outlet></router-outlet>`
})
export class StockComponent extends ModuleComponent implements OnInit, OnDestroy {

	private timer: any;

	constructor(
		public app: AppService,
		private api: ApiService
	) {
		super(app);
	}

	ngOnInit() {
		this.api.stock.getNavigation(response => {
			this.app.sidebar.setMenu(response.data);
		});

		this.scheduleKeepAlive();
	}

	ngOnDestroy() {
		clearTimeout(this.timer);
	}

	// Send ping request every 9 minutes
	scheduleKeepAlive() { this.timer = setTimeout(() => this.keepAlive(), 9 * 60 * 1000); }
	keepAlive() { this.api.general.ping(() => this.scheduleKeepAlive(), () => this.scheduleKeepAlive()); }

}
