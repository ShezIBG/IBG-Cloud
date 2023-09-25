import { EntityTypes } from './entity-types';
import { TenantedArea } from './tenanted-area';
import { EntitySortPipe } from './entity-sort.pipe';
import { FloorPlanAssignment } from './floorplan-assignment';
import { FloorPlan } from './floorplan';
import { AppService } from './../app.service';
import { EntityManager } from './entity-manager';
import { Floor } from './floor';
import { Entity } from './entity';

declare var Mangler: any;

export class Area extends Entity {

	static type = EntityTypes.Area;
	static groupName = 'Areas';

	count = {
		structure: 0,
		equipment: 0
	}

	get custom_floorplan() { return !!this.data.custom_floorplan; }
	set custom_floorplan(value) { this.data.custom_floorplan = value ? 1 : 0; }

	get is_tenanted() { return !!this.data.is_tenanted; }
	set is_tenanted(value) {
		this.data.is_tenanted = value ? 1 : 0;
		if (value) {
			this.data.is_owner_occupied = 0;
			this.createTenantedAreaIfNeeded();
		} else {
			if (!this.is_owner_occupied) this.deleteTenantedAreaIfNeeded();
		}
	}

	get is_owner_occupied() { return !!this.data.is_owner_occupied; }
	set is_owner_occupied(value) {
		this.data.is_owner_occupied = value ? 1 : 0;
		if (value) {
			this.data.is_tenanted = 0;
			this.createTenantedAreaIfNeeded();
		} else {
			if (!this.is_tenanted) this.deleteTenantedAreaIfNeeded();
		}
	}

	constructor(data, entityManager: EntityManager) {
		super(data, entityManager);

		this.onItemAddedEvent.subscribe((entity: Entity) => {
			if (entity.hasTag('structure')) this.count.structure++;
			if (entity.hasTag('equipment')) this.count.equipment++;
		});

		this.onItemRemovedEvent.subscribe((entity: Entity) => {
			if (entity.hasTag('structure')) this.count.structure--;
			if (entity.hasTag('equipment')) this.count.equipment--;
		});

	}

	getTypeDescription() { return 'Area'; }
	getIconClass() { return 'ei ei-area'; }
	getParent() { return this.entityManager.get<Floor>(EntityTypes.Floor, this.data.floor_id); }
	getSort() { return this.data.display_order; }
	getTags() { return ['structure', 'structure-tree', 'equipment', 'equipment-tree', 'assign-tree', 'floorplan-tree']; }

	createEntity(data: any) {
		return this.entityManager.createEntity(Mangler.clone(data));
	}

	getAssignedTo() {
		const floor = this.entityManager.get<Floor>(EntityTypes.Floor, this.data.floor_id);
		return floor ? [floor] : [];
	}

	canDelete() {
		const tenanted = this.getTenantedArea();
		if (tenanted && !tenanted.canDelete()) return false;
		return super.canDelete();
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
		if (this.data.custom_floorplan) {
			let list = [];

			this.entityManager.find<FloorPlanAssignment>(EntityTypes.FloorPlanAssignment, { area_id: this.data.id }).forEach((item: Entity) => {
				const floorPlan = this.entityManager.get<FloorPlan>(EntityTypes.FloorPlan, item.data.floorplan_id);
				if (floorPlan) list.push(floorPlan);
			});

			list = EntitySortPipe.transform(list);
			return list;
		} else {
			const floor = this.closest(EntityTypes.Floor) as Floor;
			return floor ? floor.getFloorplans() : [];
		}
	}

	hasTenant() {
		let result = false;
		this.assigned.forEach(entity => {
			if (EntityTypes.isTenant(entity)) result = true;
		});
		return result;
	}

	getTenantedArea() {
		let tenantedArea: TenantedArea = null;
		this.items.forEach(entity => {
			if (EntityTypes.isTenantedArea(entity)) tenantedArea = entity;
		});
		return tenantedArea;
	}

	private createTenantedAreaIfNeeded() {
		if (!this.getTenantedArea()) {
			this.entityManager.createEntity({
				entity: 'tenanted_area',
				area_id: this.data.id,
				tenant_type: 'serviced'
			});
		}
	}

	private deleteTenantedAreaIfNeeded() {
		const tenanted = this.getTenantedArea();
		if (tenanted) tenanted.delete();
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
