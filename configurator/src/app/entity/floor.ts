import { EntityTypes } from './entity-types';
import { EntitySortPipe } from './entity-sort.pipe';
import { FloorPlanAssignment } from './floorplan-assignment';
import { FloorPlan } from './floorplan';
import { AppService } from './../app.service';
import { Building } from './building';
import { Entity } from './entity';

export class Floor extends Entity {

	static type = EntityTypes.Floor;
	static groupName = 'Floors';

	getTypeDescription() { return 'Block'; }
	getIconClass() { return 'ei ei-floor'; }
	getParent() { return this.entityManager.get<Building>(EntityTypes.Building, this.data.building_id); }
	getSort() { return this.data.display_order; }
	getTags() { return ['structure', 'structure-tree', 'equipment', 'equipment-tree', 'assign-tree', 'floorplan-tree']; }

	getAssignedTo() {
		const building = this.entityManager.get<Building>(EntityTypes.Building, this.data.building_id);
		return building ? [building] : [];
	}

	jumpTo(app: AppService) {
		if (app.selectedTab !== 0 && app.selectedTab !== 1) app.selectedTab = 0;

		if (app.selectedTab === 0) {
			app.structureScreenService.selectTreeEntity(this);
		} else if (app.selectedTab = 1) {
			app.equipmentScreenService.selectTreeEntity(this);
		}
	}

	getFloorplans(): FloorPlan[] {
		let list = [];
		this.entityManager.find<FloorPlanAssignment>(EntityTypes.FloorPlanAssignment, { floor_id: this.data.id }).forEach((item: Entity) => {
			const floorPlan = this.entityManager.get<FloorPlan>(EntityTypes.FloorPlan, item.data.floorplan_id);
			if (floorPlan) list.push(floorPlan);
		});
		list = EntitySortPipe.transform(list);
		return list;
	}

	getPrevSibling() {
		let prev = null;
		let sort = 0;
		this.getParent().items.forEach(item => {
			if (item.type === this.type && item.data.display_order > sort && item.data.display_order < this.data.display_order) {
				prev = item;
				sort = item.data.display_order;
			}
		});
		return prev;
	}

	getNextSibling() {
		let next = null;
		let sort = Number.MAX_SAFE_INTEGER;
		this.getParent().items.forEach(item => {
			if (item.type === this.type && item.data.display_order < sort && item.data.display_order > this.data.display_order) {
				next = item;
				sort = item.data.display_order;
			}
		});
		return next;
	}

	moveUp() {
		const prev = this.getPrevSibling();
		const temp = this.data.display_order;
		this.data.display_order = prev.data.display_order;
		prev.data.display_order = temp;

		const parent = this.getParent();
		if (parent) parent.items = parent.items.slice();
	}

	moveDown() {
		const prev = this.getNextSibling();
		const temp = this.data.display_order;
		this.data.display_order = prev.data.display_order;
		prev.data.display_order = temp;
		this.refresh();

		const parent = this.getParent();
		if (parent) parent.items = parent.items.slice();
	}

}
