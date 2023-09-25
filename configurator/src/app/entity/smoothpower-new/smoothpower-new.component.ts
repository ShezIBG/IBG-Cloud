import { EntityTypes } from './../entity-types';
import { AppService } from './../../app.service';
import { Entity } from './../entity';
import { Area } from './../area';
import { SmoothPower } from './../smoothpower';
import { Component } from '@angular/core';
import { EntityNewComponent } from 'app/entity-decorators';
import { ModalService } from 'app/modal/modal.service';
import { ModalEvent } from 'app/modal/modal.component';

declare var Mangler: any;

@Component({
	selector: 'app-smoothpower-new',
	templateUrl: './smoothpower-new.component.html'
})
@EntityNewComponent(SmoothPower)
export class SmoothPowerNewComponent {

	area: Area;
	entity: Entity;

	units = [];
	selectedUnit = null;

	constructor(
		public app: AppService,
		public modalService: ModalService
	) {
		this.area = this.modalService.data.area;
		this.entity = this.modalService.data.entity;

		this.units = Mangler.find(this.app.smoothPowerUnits, { building_id: null });
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			if (!this.selectedUnit) return;

			const data = Mangler.clone(this.selectedUnit);
			data.entity = EntityTypes.SmoothPower;

			const e = this.area.entityManager.createEntity(data) as SmoothPower;
			if (e) {
				// This is a newly added SmoothPower box. If we delete it in the same session,
				// there is no need to send the delete event through to the backend.
				e.recordOnDelete = false;
				e.moveToArea(this.area);
				e.entityManager.onEntityAddedEvent.emit(e);

				// Mark unit as installed
				this.selectedUnit.building_id = e.data.building_id;
				this.selectedUnit.area_id = e.data.area_id;
				this.selectedUnit.router_id = e.data.router_id;
			}

			event.modal.close();
		} else {
			event.modal.close();
		}
	}

}
