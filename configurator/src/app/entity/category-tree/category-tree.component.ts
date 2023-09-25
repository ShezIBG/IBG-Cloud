import { EntityTypes } from './../entity-types';
import { EntitySortPipe } from './../entity-sort.pipe';
import { Category } from './../category';
import { AppService } from './../../app.service';
import { Component, Input, OnChanges } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'category-tree',
	templateUrl: './category-tree.component.html',
	styleUrls: ['./category-tree.component.css']
})
export class CategoryTreeComponent implements OnChanges {

	@Input() selected = [];
	@Input() editMode = false;

	categories: Category[] = [];
	closedNodes = [];
	hover = null;

	editedItem = null;
	canDelete = false;
	usedBy = 0;
	availableParents = [];

	constructor(public app: AppService) {
		this.reloadCategories();
	}

	ngOnChanges() {
		this.editedItem = null;
		this.reloadCategories();
	}

	reloadCategories() {
		this.categories = this.app.entityManager.find<Category>(EntityTypes.Category, { parent_category_id: null });
	}

	isOpen(entity) {
		return this.closedNodes.indexOf(entity) === -1;
	}

	toggleNode(entity) {
		const index = this.closedNodes.indexOf(entity);
		if (index === -1) {
			this.closedNodes.push(entity);
		} else {
			this.closedNodes.splice(index, 1);
		}
	}

	isSelected(entity) {
		if (this.editMode) {
			return this.editedItem === entity;
		} else {
			return this.selected.indexOf(entity) !== -1;
		}
	}

	clickItem(entity: Category) {
		if (this.editMode) {
			this.editedItem = entity;
			this.canDelete = entity.canDelete();
			this.usedBy = entity.getUsedCount();

			// Generate list of possible parents
			const parents = Mangler(this.app.entityManager.find<Category>(EntityTypes.Category));
			parents.removeItem(entity);
			entity.getSubitems().forEach(subitem => {
				parents.removeItem(subitem);
			});
			this.availableParents = parents.items;

			entity.scrollIntoView();
		} else {
			const index = this.selected.indexOf(entity);
			if (index === -1) {
				// Select the clicked category AND its parents
				let e = entity;
				while (e) {
					if (this.selected.indexOf(e) === -1) this.selected.push(e);
					e = e.getParent();
				}
			} else {
				this.selected.splice(index, 1);
			}
			EntitySortPipe.transform(this.selected);
		}
	}

	parentChanged(entity: Category) {
		this.reloadCategories();
		entity.scrollIntoView();
	}

	unselectItem(entity: Category) {
		const index = this.selected.indexOf(entity);
		if (index !== -1) {
			this.selected.splice(index, 1);
		}
		EntitySortPipe.transform(this.selected);
	}

	addItem(entity: Category = null) {
		const newCategory = this.app.entityManager.createEntity({
			entity: 'category',
			parent_category_id: entity ? entity.data.id : null,
			description: 'New Category',
			editable: 1,
			deletable: 1,
			client_id: this.app.building.data.client_id
		}) as Category;

		this.reloadCategories();
		this.clickItem(newCategory);
	}

	deleteItem(entity: Category) {
		this.unselectItem(entity);
		entity.delete();
		this.editedItem = null;
		this.reloadCategories();
	}

}
