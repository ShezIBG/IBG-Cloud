import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-package-details',
	templateUrl: './isp-package-details.component.html'
})
export class IspPackageDetailsComponent implements OnInit, OnDestroy {

	details;
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const id = params['package'] || 'new';
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};

				if (!this.details.upstream_profile) this.details.upstream_profile = {};
				if (!this.details.downstream_profile) this.details.downstream_profile = {};
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (id === 'new') {
				this.app.notifications.showDanger('Adding packages it not yet supported.');
			} else {
				this.api.isp.getPackage(id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.isp.savePackage(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Package updated.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
