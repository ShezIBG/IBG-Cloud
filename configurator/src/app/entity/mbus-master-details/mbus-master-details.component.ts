import { MBusMaster } from './../mbus-master';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-mbus-master-details',
	templateUrl: './mbus-master-details.component.html'
})
@EntityDetailComponent(MBusMaster)
export class MBusMasterDetailsComponent implements OnInit {

	@Input() entity: MBusMaster = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as MBusMaster;
	}

}
