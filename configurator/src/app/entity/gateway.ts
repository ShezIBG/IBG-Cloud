import { EntityTypes } from './entity-types';
import { EntityManager } from './entity-manager';
import { Router } from './router';
import { Area } from './area';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class Gateway extends Entity implements MovableEntity {

	static type = EntityTypes.Gateway;
	static groupName = 'Collectors';

	static gatewayTypes = {
		'EC10': 'PM12',
		'ABB10': 'ABB',
		'MB30': 'M-Bus',
		'DLC64': 'DALI',
		'RS32': 'RS-485',
		'MAR10': 'MarCom'
	};

	modbus: Entity[] = [];
	dali: Entity[] = [];

	get ssh_port() { return this.data.ssh_port; }
	set ssh_port(value) { this.data.ssh_port = parseInt(value, 10) || null; }

	constructor(data, entityManager: EntityManager) {
		super(data, entityManager);

		if (this.hasBus(Entity.BUS_TYPE_MODBUS)) {
			this.onItemAssignedEvent.subscribe(() => this.refreshModbus());
			this.onItemUnassignedEvent.subscribe(() => this.refreshModbus());
			this.refreshModbus();
		} else if (this.hasBus(Entity.BUS_TYPE_DALI)) {
			this.onItemAssignedEvent.subscribe(() => this.refreshDali());
			this.onItemUnassignedEvent.subscribe(() => this.refreshDali());
			this.refreshDali();
		}
	}

	getTypeDescription() { return Gateway.gatewayTypes[this.data.type] + ' Collector'; }
	getSubtitle() { return this.data.pi_serial; }
	getIconClass() { return 'ei ei-gateway'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	hasBus(type: string): boolean {
		let busType = 'Unknown';
		if (this.data.type === 'EC10' || this.data.type === 'ABB10' || this.data.type === 'MAR10') {
			busType = Entity.BUS_TYPE_MODBUS;
		} else if (this.data.type === 'MB30') {
			busType = Entity.BUS_TYPE_MBUS;
		} else if (this.data.type === 'DLC64') {
			busType = Entity.BUS_TYPE_DALI;
		} else if (this.data.type === 'RS32') {
			busType = Entity.BUS_TYPE_RS485;
		}
		return type === busType;
	}

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		return area.createEntity(data);
	}

	canMove() { return true; }

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.refresh();
			return true;
		}
		return false;
	}

	getAssignedTo() {
		const router = this.entityManager.get<Router>(EntityTypes.Router, this.data.router_id);
		return router ? [router] : [];
	}

	isUnassigned() {
		return !this.data.router_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isRouter(entity);
	}

	assignTo(entity: Entity) {
		if (EntityTypes.isRouter(entity)) {
			this.data.router_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isRouter(entity)) {
			this.data.router_id = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	refreshModbus() {
		if (!this.hasBus(Entity.BUS_TYPE_MODBUS)) return;

		let modbusCount = 31;
		if (this.data.type === 'MAR10') modbusCount = 1;

		const modbus = [];
		for (let i = 0; i < modbusCount; i++) modbus[i] = null;
		this.assigned.forEach((entity: Entity) => {
			const busID = entity.getBusID(Entity.BUS_TYPE_MODBUS);
			if (entity.hasBus(Entity.BUS_TYPE_MODBUS) && busID) {
				modbus[busID - 1] = entity;
			}
		});

		this.modbus = modbus;
	}

	refreshDali() {
		if (!this.hasBus(Entity.BUS_TYPE_DALI)) return;

		const dali = [];
		for (let i = 0; i < 64; i++) dali[i] = null;
		this.assigned.forEach((entity: Entity) => {
			const busID = entity.getBusID(Entity.BUS_TYPE_DALI);
			if (entity.hasBus(Entity.BUS_TYPE_DALI) && (busID === 0 || busID)) {
				dali[busID] = entity;
			}
		});

		this.dali = dali;
	}

}
