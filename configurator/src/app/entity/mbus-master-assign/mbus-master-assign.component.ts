import { EntityTypes } from './../entity-types';
import { MBusCatalogue } from './../mbus-catalogue';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { MBusMaster } from './../mbus-master';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-mbus-master-assign',
	templateUrl: './mbus-master-assign.component.html'
})
@EntityAssignComponent(MBusMaster)
export class MBusMasterAssignComponent implements OnInit {

	@Input() entity: MBusMaster = null;

	hovered = null;
	mbusID = '';
	catalogue: MBusCatalogue = null;
	outputNo = 1;
	multiplier = '1';
	mbusError = '';
	unit = 'kWh';

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as MBusMaster;
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	getCatalogueItems() {
		const list = [];
		const entity = this.screen.assignables[0];
		if (!entity) return list;

		this.entity.entityManager.entities.mbus_catalogue.forEach(item => {
			const cat = item as MBusCatalogue;

			if (EntityTypes.isMeter(entity)) {
				if (!cat.isAssignableToMeter()) return;
				if (!cat.hasUtility(entity.data.meter_type)) return;
				list.push(cat);
			} else if (EntityTypes.isABBMeter(entity)) {
				if (!cat.isAssignableToABB()) return;
				if (!cat.hasUtility('E')) return;
				list.push(cat);
			}
		});

		return list;
	}

	catalogueItemChanged() {
		if (!this.catalogue) return;

		this.multiplier = this.catalogue.data.default_multiplier;
		if (this.outputNo > this.catalogue.getOutputs()) this.outputNo = 1;
	}

	getOutputList() {
		const result = [1];
		if (!this.catalogue) return result;

		const max = this.catalogue.getOutputs();
		for (let i = 2; i <= max; i++) result.push(i);
		return result;
	}

	assignMBus() {
		this.mbusError = '';
		const mbid = parseInt(this.mbusID, 10) || 0;
		if (mbid < 1) {
			this.mbusError = 'Please enter a valid M-Bus ID.';
			return;
		}

		// Check if M-Bus ID is already assigned
		this.entity.assigned.forEach((entity: Entity) => {
			if (EntityTypes.isMeter(entity)) {
				const device = entity.getMBusDevice();
				if (device.data.mbus_id === mbid) {
					this.mbusError = 'M-Bus ID is already assigned to ' + entity.getDescription();
				}
			}
		});
		if (this.mbusError) return;

		if (!this.catalogue) {
			this.mbusError = 'Please select device type.';
			return;
		}

		if (!this.unit) {
			this.mbusError = 'Please select unit.';
			return;
		}

		const meter = this.screen.assignables.shift();
		if (!meter) return;

		meter.assignTo(this.entity, {
			catalogue: this.catalogue,
			mbus_id: mbid,
			value_multiplier: parseFloat(this.multiplier) || 0,
			output_no: this.outputNo || 1,
			unit: this.unit || 'kWh'
		});

		this.mbusError = '';
		this.mbusID = '';
	}

}
