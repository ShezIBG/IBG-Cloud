import { EntityTypes } from './entity-types';
import { Category } from './category';
import { ABBMeter } from './abb-meter';
import { PM12 } from './pm12';
import { Entity } from './entity';
import { Breaker } from './breaker';

export class CT extends Entity {

	static type = EntityTypes.CT;
	static groupName = 'CTs';

	getTypeDescription() { return 'CT'; }
	getIconClass() { return 'md md-link'; }
	getDescription() { return (this.data.long_description || '').replace(/\sL1$|\sL2$|\sL3$/, ''); }
	getParent() {
		if (this.data.pm12_id) {
			return this.entityManager.get<PM12>(EntityTypes.PM12, this.data.pm12_id);
		} else if (this.data.abb_meter_id) {
			return this.entityManager.get<ABBMeter>(EntityTypes.ABBMeter, this.data.abb_meter_id);
		}
		return null;
	}
	getSort() {
		if (this.data.pm12_id) {
			return [this.data.pm12_pin_id];
		} else if (this.data.abb_meter_id) {
			return [this.data.abb_meter_pin_id];
		}
		return [this.data.long_description];
	}
	getTags() { return ['structure']; }

	getAssignedTo(): Entity[] {
		return this.data.breaker_id ? [this.entityManager.get<Breaker>(EntityTypes.Breaker, this.data.breaker_id)] : [];
	}

	getAssignedToInfo(entity: Entity) {
		if (this.getAssignedTo().indexOf(entity) === -1) return '';

		if (EntityTypes.isBreaker(entity)) {
			const db = entity.closest(EntityTypes.DistBoard);

			if (!db) return entity.getDescription();

			if (db.data.is_virtual) {
				return db.getDescription();
			} else {
				return db.getDescription() + ' / ' + entity.getDescription();
			}
		}

		return entity.getDescription();
	}

	isAssignableTo(entity: Entity) {
		if (EntityTypes.isBreaker(entity)) {
			if (this.is3P()) {
				// 3P CT, only 3P breaker is allowed
				return entity.is3P();
			} else {
				// 1P CT, locations must match
				return this.data.location === entity.data.location;
			}
		}
		return false;
	}

	assignTo(entity: Entity) {
		if (EntityTypes.isBreaker(entity)) {
			this.getAllCTSFromGroup().forEach((ct: CT) => {
				ct.data.breaker_id = entity.data.id;
				ct.refresh();
			});
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isBreaker(entity)) {
			this.getAllCTSFromGroup().forEach((ct: CT) => {
				ct.data.breaker_id = 0;
				ct.refresh();
			});
		}
	}

	beforeDelete() {
		// Delete all ct_category assignments
		this.items.slice().forEach((entity: Entity) => {
			this.entityManager.deleteEntity(entity);
		});

		// Delete all other CTs in group
		if (this.isMainCT()) {
			this.getAllCTSFromGroup().forEach((ct: CT) => {
				if (!ct.isMainCT()) ct.entityManager.deleteEntity(ct);
			});
		}
	}

	canDelete() {
		// Ignore items in check (ct_category only)

		const parent = this.getParent();
		if (parent && EntityTypes.isPM12(parent)) {
			// PM12 CTs must be unassigned before deleting them
			return !this.assigned.length && !this.getAssignedTo().length;
		}
		return !this.assigned.length;
	}

	getMainCTFromGroup() {
		return !this.isMainCT() ? this.entityManager.get<CT>(EntityTypes.CT, this.data.ct_group_id) : this;
	}

	getAllCTSFromGroup() {
		return this.data.ct_group_id ? this.entityManager.find<CT>(EntityTypes.CT, { ct_group_id: this.data.ct_group_id }) : [this];
	}

	is3P() {
		return !!this.data.ct_group_id;
	}

	isMainCT() {
		return this.data.id === this.data.ct_group_id || !this.data.ct_group_id;
	}

	getGroupedLocation() {
		return this.is3P() ? 'L1,2,3' : this.data.location;
	}

	getGroupedPin() {
		const mainCT = this.getMainCTFromGroup();

		if (mainCT.data.pm12_id) {
			return mainCT.is3P() ? '' + mainCT.data.pm12_pin_id + '-' + (mainCT.data.pm12_pin_id + 1) + '-' + (mainCT.data.pm12_pin_id + 2) : mainCT.data.pm12_pin_id;
		} else if (mainCT.data.abb_meter_id) {
			return mainCT.is3P() ? '' + mainCT.data.abb_meter_pin_id + '-' + (mainCT.data.abb_meter_pin_id + 1) + '-' + (mainCT.data.abb_meter_pin_id + 2) : mainCT.data.abb_meter_pin_id;
		}
		return '';
	}

	canUpdateLocation() {
		return !this.is3P() && !this.getAssignedTo().length;
	}

	updateLocation(location) {
		if (!this.canUpdateLocation()) return false;

		this.data.location = location;

		const parent = this.getParent();
		if (EntityTypes.isABBMeter(parent) && !parent.is3P()) {
			this.data.short_description = 'ABB 1P ' + location;
		}
	}

	getCategories(): Category[] {
		const result: Category[] = [];
		this.items.forEach((entity: Entity) => {
			if (EntityTypes.isCTCategory(entity)) {
				const category = entity.getAssignedTo()[0];
				if (EntityTypes.isCategory(category)) result.push(category);
			}
		});
		return result;
	}

	updateCategories(list: Category[]) {
		const categories = list.slice();
		const building = this.entityManager.getBuilding();

		// Process removed categories
		this.items.slice().forEach((entity: Entity) => {
			if (EntityTypes.isCTCategory(entity)) {
				const category = entity.getAssignedTo()[0];
				if (EntityTypes.isCategory(category)) {
					const index = categories.indexOf(category);
					if (index === -1) {
						// Category has been removed, delete ct_category
						this.entityManager.deleteEntity(entity);
					} else {
						// Category already exists, remove from new list
						categories.splice(index, 1);
					}
				}
			}
		});

		// Create new ct_category entities
		categories.forEach((category: Category) => {
			this.entityManager.createEntity({
				entity: 'ct_category',
				id: this.entityManager.getAutoId(),
				ct_id: this.data.id,
				category_id: category.data.id,
				building_id: building.data.id
			});
		});
	}

}
