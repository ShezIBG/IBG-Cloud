import { ModuleComponent } from './../../shared/module/module.component';
import { AppService } from './../../app.service';
import { ActivatedRoute, Router, NavigationEnd } from '@angular/router';
import { SalesService } from './../sales.service';
import { ApiService } from './../../api.service';
import { Component, OnDestroy, OnInit } from '@angular/core';

@Component({
	selector: 'app-sales',
	template: `<router-outlet></router-outlet>`
})
export class SalesComponent extends ModuleComponent implements OnInit, OnDestroy {

	private sub: any;
	private child: any;
	private timer: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		public sales: SalesService,
		private route: ActivatedRoute,
		private router: Router
	) {
		super(app);

		this.router.events.subscribe(e => {
			if (e instanceof NavigationEnd && this.child !== this.route.firstChild) {
				// Watch child route changes
				if (this.sub) {
					this.sub.unsubscribe();
					this.sub = null;
				}
				this.child = this.route.firstChild;
				this.sub = this.route.firstChild.params.subscribe(params => {
					const projectId = params['projectId'] || 0;

					if (projectId !== this.sales.projectId) {
						this.sales.projectId = projectId;

						if (projectId) {
							this.api.sales.getProject(projectId, response => {
								this.app.sidebar.setMenu([]);
								this.sales.setProjectHeader(projectId, response.data.details.description, response.data.details.project_no, response.data.pricing);
							});
						} else {
							this.api.sales.getNavigation(response => {
								this.app.sidebar.setMenuData(response.data);
							});
						}
					}

					// Automatic, module level header
					if (projectId) this.sales.showProjectHeader();
				});
			}
		});
	}

	ngOnInit() {
		this.scheduleKeepAlive();
	}

	ngOnDestroy() {
		super.ngOnDestroy();

		if (this.sub) {
			this.sub.unsubscribe();
			this.sub = null;
		}

		clearTimeout(this.timer);
	}

	// Send ping request every 9 minutes
	scheduleKeepAlive() { this.timer = setTimeout(() => this.keepAlive(), 9 * 60 * 1000); }
	keepAlive() { this.api.general.ping(() => this.scheduleKeepAlive(), () => this.scheduleKeepAlive()); }

}
