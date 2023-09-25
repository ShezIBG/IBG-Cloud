import { EntityTypes } from './../entity-types';
import { AirconModel } from './../aircon-model';
import { CoolPlug } from './../coolplug';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-coolplug-details',
	templateUrl: './coolplug-details.component.html'
})
@EntityDetailComponent(CoolPlug)
export class CoolPlugDetailsComponent implements OnInit {

	@Input() entity: CoolPlug = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as CoolPlug;
	}

	getModelDescription() {
		const m = this.entity.entityManager.findOne<AirconModel>(EntityTypes.AirconModel, { id: this.entity.data.model_series_id });
		return m ? m.data.desc : '';
	}

	onModelChanged() {
		const modelId = this.entity.data.model_series_id;
		if (!modelId) return;

		const modelEntity = this.entity.entityManager.get<AirconModel>(EntityTypes.AirconModel, modelId);

		if (modelEntity) {
			this.entity.minSetpoint = modelEntity.data.min_setpoint;
			this.entity.maxSetpoint = modelEntity.data.max_setpoint;
		}
	}

}
