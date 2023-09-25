import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-locations',
	templateUrl: './stock-locations.component.html',
	styleUrls: ['./stock-locations.component.less']
})
export class StockLocationsComponent implements OnInit, OnDestroy {

	warehouse;
	list = null;
	printURL;

	newLocation = {
		rack: '',
		bay: '',
		level: '',
		delim: '/'
	};

	racks = [];
	expanded = [];

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.warehouse = params['warehouse'];
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	indexKey(rack, bay = null, level = null) {
		return '' + (rack || '') + '|' + (bay || '') + '|' + (level || '');
	}

	refresh() {
		this.api.stock.listStockLocations(this.warehouse, response => {
			this.list = response.data.list;
			this.printURL = response.data.print_url;

			// Build location tree

			const index = Mangler.index(this.list, (k, v) => this.indexKey(v.rack, v.bay, v.level));
			this.racks = [];

			// Initialise
			this.list.forEach(item => {
				item.index = this.indexKey(item.rack, item.bay, item.level);
				item.bays = [];
				item.levels = [];
				item.labels = [item.label];
			});

			const resolveRack = item => {
				const i = this.indexKey(item.rack);
				if (index[i]) {
					return index[i];
				} else {
					const rack = {
						index: i,
						rack: item.rack,
						bay: null,
						level: null,
						bays: [],
						levels: [],
						labels: []
					};

					index[i] = rack;
					this.racks.push(rack);
					return rack;
				}
			};

			const resolveBay = item => {
				const i = this.indexKey(item.rack, item.bay);
				if (index[i]) {
					return index[i];
				} else {
					const bay = {
						index: i,
						rack: item.rack,
						bay: item.bay,
						level: null,
						bays: [],
						levels: [],
						labels: []
					};

					index[i] = bay;
					resolveRack(item).bays.push(bay);
					return bay;
				}
			};

			// Set parents
			this.list.forEach(item => {
				if (item.level) {
					// Level
					if (item.bay) {
						// Connects to bay
						resolveBay(item).levels.push(item);
					} else {
						// Connects to rack
						resolveRack(item).levels.push(item);
					}
				} else if (item.bay) {
					// Bay
					resolveRack(item).bays.push(item);
				} else {
					// Rack
					this.racks.push(item);
				}
			});

			// Add labels
			const addLabel = (item, label) => {
				if (label && item.labels.indexOf(label) === -1) {
					item.labels.push(label);
				}
			};

			this.list.forEach(item => {
				if (item.level) {
					// Level
					if (item.bay) {
						// Connects to bay
						addLabel(resolveBay(item), item.label);
					}
					addLabel(resolveRack(item), item.label);
				} else if (item.bay) {
					// Bay
					addLabel(resolveRack(item), item.label);
				}
			});

			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	createLocation() {
		if (!this.newLocation.rack) {
			this.app.notifications.showDanger('Rack cannot be empty.');
			return;
		}

		this.api.stock.createStockLocation({
			warehouse_id: this.warehouse,
			rack: this.newLocation.rack,
			bay: this.newLocation.bay,
			level: this.newLocation.level,
			delim: this.newLocation.delim
		}, () => {
			this.app.notifications.showSuccess('Location created.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteLocation(item) {
		this.api.stock.deleteStockLocation(item.id, () => {
			this.app.notifications.showSuccess('Location deleted.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	goBack() {
		this.location.back();
	}

	isExpanded(item) {
		return this.expanded.indexOf(item.index) !== -1;
	}

	toggleItem(item) {
		const i = this.expanded.indexOf(item.index);
		if (i === -1) {
			this.expanded.push(item.index);
		} else {
			this.expanded.splice(i, 1);
		}
		this.expanded = this.expanded.slice();
	}

	hasLabel(item, label) {
		return item.labels.indexOf(label) !== -1;
	}

	setLabel(item, label) {
		this.api.stock.setLocationLabel(this.warehouse, item.rack || '', item.bay || '', item.level || '', label, () => {
			this.refresh();
		});
	}

	printLocation(item) {
		let url = this.printURL;
		if (item.rack) url += '&rack=' + item.rack;
		if (item.bay) url += '&rack=' + item.bay;
		if (item.level) url += '&rack=' + item.level;

		window.open(url, '_blank');
	}

}
