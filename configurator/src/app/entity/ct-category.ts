import { EntityTypes } from './entity-types';
import { Category } from './category';
import { CT } from './ct';
import { Entity } from './entity';

export class CTCategory extends Entity {

	static type = EntityTypes.CTCategory;
	static groupName = 'Category Assignments';

	getTypeDescription() { return 'Category Assignment'; }
	getIconClass() { return 'md md-info-outline'; }
	getDescription() {
		const category = this.getAssignedTo()[0];
		return category ? category.getDescription() : '';
	}
	getParent() { return this.entityManager.get<CT>(EntityTypes.CT, this.data.ct_id); }
	getSort() { return [this.getDescription()]; }
	getTags() { return []; }

	getAssignedTo() {
		const category = this.entityManager.get<Category>(EntityTypes.Category, this.data.category_id);
		return category ? [category] : [];
	}

	canDelete() {
		// CT category assignments can always be deleted
		return true;
	}

}
