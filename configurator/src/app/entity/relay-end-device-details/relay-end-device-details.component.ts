import { EntityTypes } from './../entity-types';
import { RelayEndDevice } from './../relay-end-device';
import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';
import { RelayDevice } from '../relay-device';

@Component({
	selector: 'app-relay-end-device-details',
	templateUrl: './relay-end-device-details.component.html'
})
@EntityDetailComponent(RelayEndDevice)
export class RelayEndDeviceDetailsComponent implements OnInit {

	@Input() entity: RelayEndDevice = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as RelayEndDevice;
	}

	getAssignableRelayDevices(currentId) {
		const list = this.entity.entityManager.find<RelayDevice>(EntityTypes.RelayDevice);
		return list.filter(relay => {
			for (let i = 0; i < relay.items.length; i++) {
				const pin = relay.items[i];
				if (this.isPinAssignable(pin, currentId)) return true;
			}
			return false;
		});
	}

	isPinAssignable(pin, currentId) {
		return EntityTypes.isRelayPin(pin) && (pin.data.id === currentId || pin.isUnassigned());
	}

}
