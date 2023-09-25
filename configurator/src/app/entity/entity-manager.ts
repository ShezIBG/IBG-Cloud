import { SmoothPower } from './smoothpower';
import { RelayPin } from './relay-pin';
import { AirconModel } from './aircon-model';
import { AirconManufacturer } from './aircon-manufacturer';
import { CoolPlug } from './coolplug';
import { CoolHub } from './coolhub';
import { BuildingServer } from './building-server';
import { CalculatedMeter } from './calculated-meter';
import { EntityTypes } from './entity-types';
import { TenantedArea } from './tenanted-area';
import { Tenant } from './tenant';
import { RS485Device } from './rs485-device';
import { RS485Catalogue } from './rs485-catalogue';
import { FloorPlanAssignment } from './floorplan-assignment';
import { UserContent } from './user-content';
import { FiberOLT } from './fiber-olt';
import { FiberONU } from './fiber-onu';
import { FiberONUType } from './fiber-onu-type';
import { FiberHeadendServer } from './fiber-headend-server';
import { FloorPlanItem } from './floorplan-item';
import { FloorPlan } from './floorplan';
import { EmLightType } from './em-light-type';
import { EmLight } from './em-light';
import { Weather } from './weather';
import { ConfiguratorHistory } from './configurator-history';
import { User } from './user';
import { MBusCatalogue } from './mbus-catalogue';
import { MBusDevice } from './mbus-device';
import { MBusMaster } from './mbus-master';
import { EntityChanges } from './entity-changes';
import { CTCategory } from './ct-category';
import { Category } from './category';
import { CT } from './ct';
import { ABBMeter } from './abb-meter';
import { PM12 } from './pm12';
import { Gateway } from './gateway';
import { Router } from './router';
import { AppService } from './../app.service';
import { EventEmitter } from '@angular/core';
import { Meter } from './meter';
import { Breaker } from './breaker';
import { DistBoard } from './distboard';
import { Building } from './building';
import { Area } from './area';
import { Floor } from './floor';
import { Entity } from './entity';
import { RelayDevice } from './relay-device';
import { RelayEndDevice } from './relay-end-device';
import { DaliLight } from './dali-light';

declare var Mangler: any;

export class EntityManager {

	//
	// Static
	//

	static types = {};

	//
	// Instance
	//

	data = [];
	entities: any = {};
	deletedEntities = {};
	index = {};

	lastDisplayOrder = 0;
	lastAutoId = 0;

	onEntityAddedEvent = new EventEmitter<Entity>();
	onEntityDeletedEvent = new EventEmitter<Entity>();

	// This function registers all entities
	static registerAll() {
		this.register(Building);
		this.register(Floor);
		this.register(Area);
		this.register(DistBoard);
		this.register(Breaker);
		this.register(Meter);
		this.register(CalculatedMeter);
		this.register(Router);
		this.register(Gateway);
		this.register(PM12);
		this.register(ABBMeter);
		this.register(CT);
		this.register(Category);
		this.register(CTCategory);
		this.register(MBusCatalogue);
		this.register(MBusMaster);
		this.register(MBusDevice);
		this.register(RS485Catalogue);
		this.register(RS485Device);
		this.register(User);
		this.register(ConfiguratorHistory);
		this.register(Weather);
		this.register(EmLight);
		this.register(EmLightType);
		this.register(FloorPlan);
		this.register(FloorPlanItem);
		this.register(FloorPlanAssignment);
		this.register(UserContent);
		this.register(Tenant);
		this.register(TenantedArea);
		this.register(FiberHeadendServer);
		this.register(FiberOLT);
		this.register(FiberONU);
		this.register(FiberONUType);
		this.register(BuildingServer);
		this.register(CoolHub);
		this.register(CoolPlug);
		this.register(AirconManufacturer);
		this.register(AirconModel);
		this.register(RelayDevice);
		this.register(RelayEndDevice);
		this.register(RelayPin);
		this.register(SmoothPower);
		this.register(DaliLight);
	}

	private static register(constructor) {
		// Register constructor for dynamic instantiation
		this.types[constructor.type] = constructor;

		// Register in Mangler.js to make objects searchable
		Mangler.registerType(constructor, {
			get: function (object, key) {
				return object.data[key];
			}
		});
	}

	static getConstructor(type: string) {
		return this.types[type];
	}

	constructor(data: any[]) {
		// Parse all objects and build up collection
		Object.keys(EntityManager.types).forEach(name => {
			this.entities[name] = [];
			this.index[name] = {};
		});

		// Create entities
		data.forEach(item => {
			const eid = item['entity'];
			const c = EntityManager.getConstructor(eid);
			if (c && this.entities[eid]) this.addEntity(new c(item, this), false);
		});

		// Call initial refresh on all entities
		this.refresh();

		// -------------
		// Sanity checks
		// -------------

		// Delete all ct_categories that point to non-existing categories
		this.find<CTCategory>(EntityTypes.CTCategory).forEach(category => {
			if (category.getAssignedTo().length === 0) category.delete();
		});
	}

	addEntity(entity: Entity, refresh: Boolean = true) {
		if (entity.data.display_order) {
			if (entity.data.display_order > this.lastDisplayOrder) this.lastDisplayOrder = entity.data.display_order;
		}

		this.entities[entity.type].push(entity);
		this.data.push(entity.data);
		if (entity.data.id) this.index[entity.type][entity.data.id] = entity;
		if (refresh) entity.refresh();
		this.onEntityAddedEvent.emit(entity);
	}

	createEntity(data: any): Entity {
		const eid = data['entity'];
		const c = EntityManager.getConstructor(eid);
		if (c && this.entities[eid]) {
			const entity = new c(data, this);
			this.addEntity(entity);
			return entity;
		}
		return null;
	}

	deleteEntity(entity: Entity) {
		let i, parent;

		// Don't do anything if entity is being (or was) deleted
		if (entity.deleted) return true;

		// Don't do anything if entity cannot be deleted
		if (!entity.canDelete()) return false;

		// Set deleted flag to avoid multiple nested delete requests to be processed
		entity.deleted = true;

		// Notify entity that it's about to be deleted
		entity.beforeDelete();

		// Remove entity from its parent
		parent = entity.lastParent;
		if (parent) {
			i = parent.items.indexOf(entity);
			if (i !== -1) {
				parent.items.splice(i, 1);
				parent.items = parent.items.slice();
			}
		}

		// Unassign entity from its parents
		const assignedParents = entity.lastAssignedTo.slice();
		assignedParents.forEach((assignedTo: Entity) => {
			if (assignedTo) {
				i = assignedTo.assigned.indexOf(entity);
				if (i !== -1) {
					assignedTo.assigned.splice(i, 1);
					assignedTo.assigned = assignedTo.assigned.slice();
				}
			}
		});

		// Remove from index
		if (entity.data.id) delete this.index[entity.type][entity.data.id];

		// Remove from entity list
		i = this.entities[entity.type].indexOf(entity);
		if (i !== -1) this.entities[entity.type].splice(i, 1);

		// Remove from data
		i = this.data.indexOf(entity.data);
		if (i !== -1) this.data.splice(i, 1);

		// Notify parent of removal
		if (parent) parent.onItemRemovedEvent.emit(entity);

		// Notify assigned parents of unassignment
		assignedParents.forEach((assignedTo: Entity) => assignedTo.onItemUnassignedEvent.emit(entity));

		// Notify everyone else
		this.onEntityDeletedEvent.emit(entity);

		// Add entity to deleted items container (only if it was written to the database before)
		if (!entity.isNew() && entity.recordOnDelete) {
			if (this.deletedEntities[entity.type]) {
				this.deletedEntities[entity.type].push(entity);
			} else {
				this.deletedEntities[entity.type] = [entity];
			}
		}

		return true;
	}

	deleteEntityConfirm(app: AppService, entity: Entity) {
		// TODO: Unfinished
	}

	// Returns the building (root object)
	getBuilding(): Building {
		return this.entities[Building.type][0];
	}

	get<T extends Entity>(type: string, id: string): T {
		return this.index[type][id] || null;
	}

	find<T extends Entity>(type: string, filter = {}): T[] {
		return Mangler.find(this.entities[type], filter);
	}

	findOne<T extends Entity>(type: string, filter = {}): T {
		return Mangler.findOne(this.entities[type], filter);
	}

	refresh() {
		Object.keys(this.entities).forEach(type => {
			this.entities[type].forEach(entity => {
				if (entity.data.display_order === 0) {
					this.lastDisplayOrder++;
					entity.data.display_order = this.lastDisplayOrder;
				}
				entity.refresh();
			});
		});
	}

	getAutoId() {
		this.lastAutoId++;
		return 'newid_' + this.lastAutoId;
	}

	getAutoDisplayOrder() {
		this.lastDisplayOrder++;
		return this.lastDisplayOrder;
	}

	getGroupName(type: string) {
		const c = EntityManager.getConstructor(type);
		return c ? c.groupName : '';
	}

	getChanges() {
		const changes = new EntityChanges(this);

		// Process deleted entities
		changes.deletedTypes = Object.keys(this.deletedEntities);
		changes.deletedEntities = this.deletedEntities;

		// Process newly added entities
		changes.addedTypes = [];
		changes.addedEntities = {};

		Mangler.each(this.entities, (type: string, items: Entity[]) => {
			let first = true;
			Mangler.each(items, (i, entity: Entity) => {
				if (entity.isNew()) {
					if (first) {
						first = false;
						changes.addedTypes.push(type);
						changes.addedEntities[type] = [entity];
					} else {
						changes.addedEntities[type].push(entity);
					}
				}
			});
		});

		// Process modified entities
		changes.modifiedTypes = [];
		changes.modifiedEntities = {};

		Mangler.each(this.entities, (type: string, items: Entity[]) => {
			let first = true;
			Mangler.each(items, (i, entity: Entity) => {
				if (!entity.isNew() && !(EntityTypes.isWeather(entity))) {
					// Detect data changes
					const dataChanges = {};
					Mangler.each(entity.data, (key: string, value: any) => {
						// Non-strict inequality check is justified here, as a number fields might have changed to a string due to
						// an input field on a form, but the value is still the same and doesn't need updating in the database.
						// tslint:disable-next-line:triple-equals
						if (value != entity.originalData[key]) {
							dataChanges[key] = value;
						}
					});

					if (!Mangler.isEmpty(dataChanges)) {
						if (first) {
							first = false;
							changes.modifiedTypes.push(type);
							changes.modifiedEntities[type] = [{ entity: entity, changes: dataChanges }];
						} else {
							changes.modifiedEntities[type].push({ entity: entity, changes: dataChanges });
						}
					}
				}
			});
		});

		return changes;
	}

}

EntityManager.registerAll();
