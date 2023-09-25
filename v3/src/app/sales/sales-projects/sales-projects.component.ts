import { Pagination } from 'app/shared/pagination';
import { SalesService } from './../sales.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-sales-projects',
	templateUrl: './sales-projects.component.html'
})
export class SalesProjectsComponent implements OnInit, OnDestroy {

	list: any = [];
	pricing = false;
	si = false;
	search = '';
	pagination = new Pagination();

	filters;
	timer;
	destroyed = false;

	private sub: any;

	get stage_cancelled() { return this.getFilter('stage', 'cancelled'); }
	set stage_cancelled(value) { this.setFilter('stage', 'cancelled', value); }

	get stage_lead() { return this.getFilter('stage', 'lead'); }
	set stage_lead(value) { this.setFilter('stage', 'lead', value); }

	get stage_survey() { return this.getFilter('stage', 'survey'); }
	set stage_survey(value) { this.setFilter('stage', 'survey', value); }

	get stage_quote() { return this.getFilter('stage', 'quote'); }
	set stage_quote(value) { this.setFilter('stage', 'quote', value); }

	get stage_build() { return this.getFilter('stage', 'build'); }
	set stage_build(value) { this.setFilter('stage', 'build', value); }

	get stage_install() { return this.getFilter('stage', 'install'); }
	set stage_install(value) { this.setFilter('stage', 'install', value); }

	get stage_complete() { return this.getFilter('stage', 'complete'); }
	set stage_complete(value) { this.setFilter('stage', 'complete', value); }

	get visibility_public() { return this.getFilter('visibility', 1); }
	set visibility_public(value) { this.setFilter('visibility', 1, value); }

	get visibility_private() { return this.getFilter('visibility', 0); }
	set visibility_private(value) { this.setFilter('visibility', 0, value); }

	constructor(
		public app: AppService,
		private api: ApiService,
		public sales: SalesService
	) { }

	ngOnInit() {
		this.destroyed = false;

		if (!this.sales.projectFilters) {
			this.sales.projectFilters = {
				stage: ['lead', 'survey', 'quote', 'build', 'install', 'complete'],
				visibility: [0, 1]
			};
		}

		this.filters = this.sales.projectFilters;

		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		clearTimeout(this.timer);
		this.destroyed = true;
		this.sub.unsubscribe();
	}

	refresh() {
		clearTimeout(this.timer);
		this.api.sales.listProjects(this.app.selectedProductOwner, this.filters, response => {
			if (this.destroyed) return;
			this.list = response.data.list || [];
			this.pricing = response.data.pricing || false;
			this.si = response.data.si;
			this.app.resolveProductOwners(response);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
		});
	}

	timedRefresh() {
		clearTimeout(this.timer);
		this.timer = setTimeout(() => this.refresh(), 500);
	}

	getFilter(prop, value) {
		if (this.filters) return this.filters[prop].indexOf(value) !== -1;
		return false;
	}

	setFilter(prop, value, state) {
		if (this.filters) {
			const i = this.filters[prop].indexOf(value);
			if (state) {
				if (i === -1) this.filters[prop].push(value);
			} else {
				if (i !== -1) {
					this.filters[prop].splice(i, 1);
					this.filters[prop] = this.filters[prop].slice();
				}
			}
		}
	}

}
