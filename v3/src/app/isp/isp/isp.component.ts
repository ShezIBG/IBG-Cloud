import { IspService } from './../isp.service';
import { Router, ActivationEnd } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModuleComponent } from './../../shared/module/module.component';
import { Component, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp',
	template: `<router-outlet></router-outlet>`
})
export class IspComponent extends ModuleComponent implements OnDestroy {

	private subs: any[] = [];

	constructor(
		public app: AppService,
		private api: ApiService,
		private router: Router,
		private isp: IspService
	) {
		super(app);

		this.subs.push(isp.onIspChanged.subscribe(id => {
			this.api.isp.getNavigation(id, response => {
				this.app.sidebar.setMenuData(response.data);
			});
		}));

		this.subs.push(this.router.events.subscribe(event => {
			if (event instanceof ActivationEnd) {
				if (event.snapshot.params.isp) {
					this.isp.id = event.snapshot.params.isp;
				}
			}
		}));
	}

	ngOnDestroy() {
		this.subs.forEach(sub => sub.unsubscribe());
	}

}
