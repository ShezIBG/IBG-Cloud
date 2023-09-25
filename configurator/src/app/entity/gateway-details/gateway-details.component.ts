import { EntityTypes } from './../entity-types';
import { Gateway } from './../gateway';
import { EntityAssignmentsPipe } from './../entity-assignments.pipe';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-gateway-details',
	templateUrl: './gateway-details.component.html'
})
@EntityDetailComponent(Gateway)
export class GatewayDetailsComponent implements OnInit {

	@Input() entity: Gateway = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as Gateway;
	}

	getTotal() {
		if (!this.entity) return 0;
		return EntityAssignmentsPipe.transform(this.entity.getAssignedTo().concat(this.entity.assigned), this.entity).length;
	}

	getAreaDescription(e: Entity) {
		const area = e.closest(EntityTypes.Area);
		return area ? area.getDescription() : '&ndash;';
	}

	serialChanged() {
		if (!this.entity) return;
		this.entity.data.pi_serial = (this.entity.data.pi_serial || '').toLowerCase().replace(/\s+/g, '');
	}

}
