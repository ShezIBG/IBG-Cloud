import { EntityTypes } from './entity-types';
import { Area } from './area';
import { AppService } from './../app.service';
import { EntitySortPipe } from './entity-sort.pipe';
import { EventEmitter } from '@angular/core';
import { EntityManager } from './entity-manager';

declare var $: any;
declare var Mangler: any;

export interface EntityCloneOption {
	key: string;
	name: string;
	default?: boolean;
}

export interface MovableEntity {
	canMove(): boolean;
	moveToArea(area: Area): boolean;
}
export function isMovableEntity(e): e is MovableEntity { return e && !!e.moveToArea && !!e.canMove; }

export interface ActivatableEntity {
	isActive: boolean;
}
export function isActivatableEntity(e): e is ActivatableEntity { return e && 'isActive' in e; }

export class Entity {

	//
	// Static fields (deals with identifying the object type and entity registration)
	//

	static readonly BUS_TYPE_NONE = 'none';
	static readonly BUS_TYPE_MODBUS = 'modbus';
	static readonly BUS_TYPE_MBUS = 'mbus';
	static readonly BUS_TYPE_DALI = 'dali';
	static readonly BUS_TYPE_RS485 = 'rs485';
	static readonly DEVICE_TYPE_MODBUS = 'abb';

	static type = EntityTypes.Entity;
	static groupName = 'Entities';
	static treeComponents = [];
	static detailComponents = [];
	static newComponents = [];
	static assignComponents = [];
	static canScroll = true;

	//
	// Instance properties and methods inherited by all entities
	//

	type = null; // Automatically resolved in constructor, do not change
	data = null;
	originalData = null;
	items = [];
	assigned = [];
	deleted = false;
	recordOnDelete = true; // Set it to false to prevent sending through deleted state of this entity

	lastParent: Entity = null;
	lastAssignedTo: Entity[] = [];

	// Events

	onItemAddedEvent = new EventEmitter<Entity>();
	onItemRemovedEvent = new EventEmitter<Entity>();
	onItemAssignedEvent = new EventEmitter<Entity>();
	onItemUnassignedEvent = new EventEmitter<Entity>();

	constructor(data, public entityManager: EntityManager) {
		this.type = this.getConstructor().type;
		this.data = data;
		this.originalData = Mangler.clone(data);

		// Auto-generate ID if not set
		if (!this.data.id && this.data.id !== 0) {
			this.data.id = entityManager.getAutoId();
		}
	}

	getConstructor() {
		return Object.getPrototypeOf(this).constructor;
	}

	getGroupName() {
		return this.getConstructor().groupName;
	}

	getTreeComponent() {
		return this.getConstructor().treeComponents[0];
	}

	getDetailComponent() {
		return this.getConstructor().detailComponents[0];
	}

	getNewComponent() {
		return this.getConstructor().newComponents[0];
	}

	getAssignComponent() {
		return this.getConstructor().assignComponents[0];
	}

	// Returns all items and sub-items recursively
	getSubitems() {
		const subitems = Mangler();
		subitems.add(this.items);
		this.items.forEach(item => {
			subitems.add(item.getSubitems());
		});
		return subitems.items;
	}

	closest(type: string): Entity {
		let o: Entity = this;
		while (o && o.type !== type) o = o.getParent();
		return o;
	}

	isNew() {
		return ('' + this.data.id).startsWith('newid_');
	}

	hasTag(tag: any) {
		if (!(tag instanceof Array)) tag = [tag];
		return Mangler.test(this.getTags(), { $all: tag });
	}

	hasAnyTag(tag: string[]) {
		return Mangler.test(this.getTags(), { $in: tag });
	}

	refresh() {
		let i;

		// Refresh relationships: remove from last known, add to new

		const rippleSlice = function (entity: Entity) {
			// Recursive function to slice all arrays
			// This force-triggers Angular's change detection
			entity.assigned = entity.assigned.slice();
			entity.items = entity.items.slice();
			entity.getAssignedTo().forEach(assignedTo => { rippleSlice(assignedTo); });

			const parent = entity.getParent();
			if (parent) rippleSlice(parent);
		};

		const currentParent = this.getParent();
		if (currentParent !== this.lastParent) {
			if (this.lastParent !== null) {
				i = this.lastParent.items.indexOf(this);
				if (i !== -1) {
					this.lastParent.items.splice(i, 1);
					rippleSlice(this.lastParent);
					this.lastParent.onItemRemovedEvent.emit(this);
				}
			}
			this.lastParent = currentParent;
			if (currentParent !== null) {
				currentParent.items.push(this);
				currentParent.items = EntitySortPipe.transform(currentParent.items);
				rippleSlice(currentParent);
				currentParent.onItemAddedEvent.emit(this);
			}
		}

		// Refresh assignments: remove from last known, add to new

		const currentAssignedTo = this.getAssignedTo();

		this.lastAssignedTo.forEach(assignedTo => {
			if (currentAssignedTo.indexOf(assignedTo) === -1) {
				// Item was unassigned
				i = assignedTo.assigned.indexOf(this);
				if (i !== -1) {
					assignedTo.assigned.splice(i, 1);
					rippleSlice(assignedTo);
					assignedTo.onItemUnassignedEvent.emit(this);
				}
			}
		});

		currentAssignedTo.forEach(assignedTo => {
			if (this.lastAssignedTo.indexOf(assignedTo) === -1) {
				assignedTo.assigned.push(this);
				assignedTo.assigned = EntitySortPipe.transform(assignedTo.assigned);
				rippleSlice(assignedTo);
				assignedTo.onItemAssignedEvent.emit(this);
			}
		});

		this.lastAssignedTo = currentAssignedTo;
	}

	getScrollClass() {
		return 'entitycontainer-' + this.type + '-' + this.data.id;
	}

	scrollIntoView() {
		if (!Entity.canScroll) return;

		setTimeout(() => {
			$('.' + this.getScrollClass()).scrollintoview({ duration: 100 });
		}, 0);
	}

	delete() {
		return this.entityManager.deleteEntity(this);
	}

	hasAssignableItemsTo(entity: Entity, unassignedOnly = false) {
		if (this.hasAssignableChildrenTo(entity, unassignedOnly)) return true;

		let ret = false;
		this.items.forEach((item: Entity) => {
			if (item.hasAssignableItemsTo(entity, unassignedOnly)) ret = true;
		});
		return ret;
	}

	hasAssignableChildrenTo(entity: Entity, unassignedOnly = false) {
		let ret = false;
		this.items.forEach((item: Entity) => {
			if (unassignedOnly) {
				if (item.canAssignTo(entity)) ret = true;
			} else {
				if (item.isAssignableTo(entity)) ret = true;
			}
		});
		return ret;
	}

	hasUnassignedItems() {
		if (this.hasUnassignedChildren()) return true;

		let ret = false;
		this.items.forEach((item: Entity) => {
			if (item.hasUnassignedItems()) ret = true;
		});
		return ret;
	}

	hasUnassignedChildren() {
		let ret = false;
		this.items.forEach((item: Entity) => {
			if (item.isUnassigned()) ret = true;
		});
		return ret;
	}

	jumpTo(app: AppService) {
		// Get closest area
		let area: Entity = this;
		while (area && area.type !== 'area') {
			area = area.getParent();
		}
		if (!area) return;

		// Get parent who is immediate child of area
		let parent: Entity = this;
		while (parent && parent.getParent() !== area) {
			parent = parent.getParent();
		}
		if (!parent) return;

		if (parent.hasTag('structure')) {
			app.selectedTab = 0;
			app.structureScreenService.selectTreeEntity(area);
			setTimeout(() => { app.structureScreenService.selectDetailEntity(parent) }, 0);
		} else if (parent.hasTag('equipment')) {
			app.selectedTab = 1;
			app.equipmentScreenService.selectTreeEntity(area);
			setTimeout(() => { app.equipmentScreenService.selectDetailEntity(parent) }, 0);
		}
	}

	// Overridable methods

	// Common tags: structure, equipment, structure-tree, equipment-tree, assign-tree, assignables-tree
	getTags() { return []; }

	canDelete() { return !this.items.length && !this.assigned.length; }
	beforeDelete() { /* override */ }

	get monitoring_device_type(){return this.data.monitoring_device_type}
	get monitoring_bus_type() { return this.data.monitoring_bus_type; }
	get m_bus_type(){return this.data.monitoring_device_type}

	

	getDescription() { return this.data.description || ''; }
	getBreakerId() { return this.data.breaker_id || ''; }
	getSubtitle() { return ''; }
	getTypeDescription() { return ''; }
	getIconClass() { return ''; }
	getParent(): Entity { return null; }
	getSort(): any[] { return [this.data.id] }

	hasBus(type: string): boolean { return false; }
	getBusID(type: string): any { return null; }

	copyToArea(area: Area): Entity { return null; }
	canClone() { return false; }
	getCloneDescription() { return ''; }
	getCloneOptions(): EntityCloneOption[] { return [] as EntityCloneOption[]; }
	clone(description: string, options: any) { return null; };

	isUnassigned() { return false; }
	isAssignableTo(entity: Entity) { return false; }
	isAssignedTo(entity: Entity) { return this.getAssignedTo().indexOf(entity) !== -1; }
	canAssignTo(entity: Entity) { return this.isAssignableTo(entity) && !this.getAssignedToType(entity); }

	assignTo(entity: Entity, options: any = {}) { /* override */ }
	unassignFrom(entity: Entity) { /* override */ }

	getAssignedTo(): Entity[] { return []; }
	getAssignedToType(entity: Entity) {
		let ret = null;
		this.getAssignedTo().forEach((assignedTo: Entity) => {
			if (assignedTo.type === entity.type) ret = assignedTo;
		});
		return ret;
	}
	getAssignedToInfo(entity: Entity) { return this.isAssignedTo(entity) ? entity.getDescription() : ''; }
}
