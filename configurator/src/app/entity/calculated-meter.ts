import { Meter } from './meter';
import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class CalculatedMeter extends Entity {

	static type = EntityTypes.CalculatedMeter;
	static groupName = 'Calculated Meters';
	static operators = [
		{ id: 'add', description: 'Add' },
		{ id: 'subtract', description: 'Subtract' }
	];

	get operators() { return CalculatedMeter.operators; }

	get meter_id_a() { return this.data.meter_id_a; } set meter_id_a(value) { this.data.meter_id_a = value; this.refresh(); }
	get meter_id_b() { return this.data.meter_id_b; } set meter_id_b(value) { this.data.meter_id_b = value; this.refresh(); }
	get meter_id_c() { return this.data.meter_id_c; } set meter_id_c(value) { this.data.meter_id_c = value; this.refresh(); }
	get meter_id_d() { return this.data.meter_id_d; } set meter_id_d(value) { this.data.meter_id_d = value; this.refresh(); }
	get meter_id_e() { return this.data.meter_id_e; } set meter_id_e(value) { this.data.meter_id_e = value; this.refresh(); }
	get meter_id_f() { return this.data.meter_id_f; } set meter_id_f(value) { this.data.meter_id_f = value; this.refresh(); }
	get meter_id_g() { return this.data.meter_id_g; } set meter_id_g(value) { this.data.meter_id_g = value; this.refresh(); }
	get meter_id_h() { return this.data.meter_id_h; } set meter_id_h(value) { this.data.meter_id_h = value; this.refresh(); }
	get meter_id_i() { return this.data.meter_id_i; } set meter_id_i(value) { this.data.meter_id_i = value; this.refresh(); }
	get meter_id_j() { return this.data.meter_id_j; } set meter_id_j(value) { this.data.meter_id_j = value; this.refresh(); }
	get meter_id_k() { return this.data.meter_id_k; } set meter_id_k(value) { this.data.meter_id_k = value; this.refresh(); }

	get meter() { return this.data.calculated_meter_id ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.calculated_meter_id) : null; }

	getTypeDescription() { return 'Calculated Meter'; }
	getDescription() { return 'Calculation: ' + (this.meter ? this.meter.getDescription() : '') + ' (' + this.data.operator + ')'; }
	getIconClass() { return this.data.operator === 'add' ? 'md md-add-circle-outline' : 'md md-remove-circle-outline'; }
	getParent() { return this.meter ? this.meter.closest(EntityTypes.Area) : null; }
	getSort() { return this.data.id; }
	canDelete() { return true; }

	getAssignedTo(): Entity[] {
		const assignedTo = [];
		let entity = null;

		entity = this.data.meter_id_a ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_a) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_b ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_b) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_c ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_c) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_d ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_d) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_e ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_e) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_f ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_f) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_g ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_g) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_h ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_h) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_i ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_i) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_j ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_j) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.meter_id_k ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id_k) : null;
		if (entity) assignedTo.push(entity);

		return assignedTo;
	}

	unassignFrom(entity: Entity) {
		if (EntityTypes.isMeter(entity)) {
			if (this.meter_id_a === entity.data.id) this.meter_id_a = 0;
			if (this.meter_id_b === entity.data.id) this.meter_id_b = 0;
			if (this.meter_id_c === entity.data.id) this.meter_id_c = 0;
			if (this.meter_id_d === entity.data.id) this.meter_id_d = 0;
			if (this.meter_id_e === entity.data.id) this.meter_id_e = 0;
			if (this.meter_id_f === entity.data.id) this.meter_id_f = 0;
			if (this.meter_id_g === entity.data.id) this.meter_id_g = 0;
			if (this.meter_id_h === entity.data.id) this.meter_id_h = 0;
			if (this.meter_id_i === entity.data.id) this.meter_id_i = 0;
			if (this.meter_id_j === entity.data.id) this.meter_id_j = 0;
			if (this.meter_id_k === entity.data.id) this.meter_id_k = 0;
		}
	}

}
