import { BuildingServer } from './../building-server';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-building-server-details',
	templateUrl: './building-server-details.component.html'
})
@EntityDetailComponent(BuildingServer)
export class BuildingServerDetailsComponent implements OnInit {

	@Input() entity: BuildingServer = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as BuildingServer;
	}

}
