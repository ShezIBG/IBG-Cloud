import { EntityTypes } from './../entity-types';
import { RS485Catalogue } from './../rs485-catalogue';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { Gateway } from './../gateway';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-gateway-assign',
	templateUrl: './gateway-assign.component.html'
})
@EntityAssignComponent(Gateway)
export class GatewayAssignComponent implements OnInit {

	@Input() entity: Gateway = null;

	hovered;

	rs485id = '1';
	catalogue: RS485Catalogue = null;
	multiplier = '1';
	rs485error = '';
	unit = 'kWh';

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as Gateway;
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	getCatalogueItems() {
		const list = [];
		const entity = this.screen.assignables[0];
		if (!entity) return list;

		this.entity.entityManager.entities.rs485_catalogue.forEach(item => {
			const cat = item as RS485Catalogue;

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
	}

	assignOne(mid) {
		const entity = this.screen.assignables.shift();
		if (!entity) return;

		if (entity.hasBus(Entity.BUS_TYPE_MODBUS)) {
			entity.assignTo(this.entity, { modbus_id: mid });
		} else if (entity.hasBus(Entity.BUS_TYPE_DALI)) {
			entity.assignTo(this.entity, { dali_address: mid });
		}
	}

	assignReplace() {
		const entity = this.screen.assignables.shift();
		if (entity) {
			// Unassign all items
			this.entity.assigned.slice().forEach((item: Entity) => {
				item.unassignFrom(this.entity);
			});

			entity.assignTo(this.entity);
		}
	}

	assignRS485() {
		this.rs485error = '';
		const rsid = parseInt(this.rs485id, 10) || 0;
		if (rsid < 1) {
			this.rs485error = 'Please enter a valid RS-485 ID.';
			return;
		}

		// Check if RS-485 ID is already assigned
		this.entity.assigned.forEach((entity: Entity) => {
			if (EntityTypes.isMeter(entity)) {
				const device = entity.getRS485Device();
				if (device.data.rs485_id === rsid) {
					this.rs485error = 'RS-485 ID is already assigned to ' + entity.getDescription();
				}
			}
		});
		if (this.rs485error) return;

		if (!this.catalogue) {
			this.rs485error = 'Please select device type.';
			return;
		}

		if (!this.unit) {
			this.rs485error = 'Please select unit.';
			return;
		}

		const meter = this.screen.assignables.shift();
		if (!meter) return;

		meter.assignTo(this.entity, {
			catalogue: this.catalogue,
			rs485_id: rsid,
			value_multiplier: parseFloat(this.multiplier) || 0,
			unit: this.unit || 'kWh'
		});

		this.rs485error = '';
		this.rs485id = '1';
	}

}
