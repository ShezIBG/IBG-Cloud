import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class Category extends Entity {

	static type = EntityTypes.Category;
	static groupName = 'Categories';

	get parent_category_id() { return this.data.parent_category_id; }
	set parent_category_id(value) {
		this.data.parent_category_id = value;
		this.refresh();
	}

	getTypeDescription() { return 'Category'; }
	getIconClass() { return 'md md-info-outline'; }
	getParent() { return this.data.parent_category_id ? this.entityManager.get<Category>(EntityTypes.Category, this.data.parent_category_id) : null; }
	getSort() { return [this.data.description]; }
	getTags() { return []; }

	canDelete() {
		return this.data.deletable && super.canDelete() && this.getUsedCount() === 0;
	}

	getUsedCount() {
		return this.assigned.length;
	}

}
