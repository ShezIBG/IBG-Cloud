import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';
import { RelayDevice } from '../relay-device';

@Component({
	selector: 'app-relay-device-details',
	templateUrl: './relay-device-details.component.html'
})
@EntityDetailComponent(RelayDevice)
export class RelayDeviceDetailsComponent implements OnInit {

	@Input() entity: RelayDevice = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as RelayDevice;
	}

	addPin() {
		this.entity.entityManager.createEntity({
			entity: 'relay_pin',
			description: '',
			direction: 'output',
			active: 1,
			relay_device_id: this.entity.data.id,
			port: 0
		});
	}

}
