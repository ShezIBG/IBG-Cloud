import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-tag-groups',
	templateUrl: './stock-product-tag-groups.component.html'
})
export class StockProductTagGroupsComponent implements OnInit, OnDestroy {

	list: any;
	count = { list: 0 };
	search = '';

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.products.listTagGroups(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);

			const index = {};
			this.list.forEach(group => {
				index[group.id] = group;
				group.tags = [];
				group.tag_names = '';
			});
			response.data.tags.forEach(tag => {
				const group = index[tag.group_id];
				if (group) {
					group.tags.push(tag);
					group.tag_names += tag.name + ' ';
				}
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
