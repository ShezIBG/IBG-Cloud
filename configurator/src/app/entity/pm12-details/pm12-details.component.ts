import { PM12 } from './../pm12';
import { CT } from './../ct';
import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';
import { Entity } from './../entity';
import { Area } from './../area';
import { EntityTypes } from './../entity-types';

@Component({
	selector: 'app-pm12-details',
	templateUrl: './pm12-details.component.html'
})
@EntityDetailComponent(PM12)
export class PM12DetailsComponent implements OnInit {

	@Input() entity: PM12 = null;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as PM12;
	}

	addCT(pin, phase: string) {
		this.app.modal.open(CT.newComponents[0], {
			pin: pin,
			phase: phase,
			pm12: this.entity
		});
	}

	editCT(ct: CT) {
		this.app.modal.open(CT.detailComponents[0], { entity: ct });
	}

	getAreas(floor: Entity) {
		return this.entity.entityManager.find<Area>(EntityTypes.Area, { floor_id: floor.data.id });
	}

}
