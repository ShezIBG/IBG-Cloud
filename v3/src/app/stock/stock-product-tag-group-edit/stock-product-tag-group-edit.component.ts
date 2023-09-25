import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-tag-group-edit',
	templateUrl: './stock-product-tag-group-edit.component.html'
})
export class StockProductTagGroupEditComponent implements OnInit, OnDestroy {

	id;
	details;
	productCount = 0;
	tags = [];
	deletedTags = [];
	highlightedTag = null;
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
			this.id = params['id'] || 'new';
			const owner = params['owner'];

			this.details = null;
			this.tags = null;
			this.deletedTags = [];

			const success = response => {

				this.details = response.data.details || {};
				this.tags = response.data.tags || [];
				this.productCount = response.data.product_count || 0;

				// Set modified/deleted flags
				this.tags.forEach(tag => {
					tag.modified = false;
				});

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Tags', route: '/stock/product-config/tag-group' },
					{ description: this.id === 'new' ? 'New Tag Group' : this.details.name }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newTagGroup(owner, success, fail);
			} else {
				this.api.products.getTagGroup(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	addTag() {
		const tag = {
			id: 'new',
			name: '',
			modified: false
		};

		this.tags.push(tag);
		this.tags = this.tags.slice();
		this.highlightedTag = tag;
	}

	deleteTag(tag) {
		const i = this.tags.indexOf(tag);
		if (i !== -1) {
			this.tags.splice(i, 1);
			this.tags = this.tags.slice();

			if (tag.id !== 'new') {
				this.deletedTags.push(tag);
				this.deletedTags = this.deletedTags.slice();
				this.highlightedTag = tag;
			}
		}
	}

	undeleteTag(tag) {
		const i = this.deletedTags.indexOf(tag);
		if (i !== -1) {
			this.deletedTags.splice(i, 1);
			this.deletedTags = this.deletedTags.slice();

			this.tags.push(tag);
			this.tags = this.tags.slice();

			this.highlightedTag = tag;
		}
	}

	goBack() {
		this.location.back();
	}

	save() {
		const data = {
			details: this.details,
			deleted: [],
			modified: [],
			added: []
		};

		this.tags.forEach(tag => {
			if (tag.id === 'new') {
				data.added.push(tag);
			} else if (tag.modified) {
				data.modified.push(tag);
			}
		});

		this.deletedTags.forEach(tag => {
			data.deleted.push(tag);
		});

		this.disabled = true;
		this.api.products.saveTagGroup(data, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Tag group created.');
			} else {
				this.app.notifications.showSuccess('Tag group updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	delete() {
		if (this.id === 'new') return;

		this.disabled = true;
		this.api.products.deleteTagGroup(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Tag group deleted.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
