import { CoolHub } from './../coolhub';
import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-coolhub-details',
	templateUrl: './coolhub-details.component.html'
})
@EntityDetailComponent(CoolHub)
export class CoolHubDetailsComponent implements OnInit {

	@Input() entity: CoolHub = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as CoolHub;
	}

}
