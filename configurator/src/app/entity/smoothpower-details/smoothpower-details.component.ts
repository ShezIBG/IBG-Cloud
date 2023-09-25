import { Component, OnInit, Input } from '@angular/core';
import { SmoothPower } from '../smoothpower';
import { EntityDetailComponent } from 'app/entity-decorators';
import { ScreenService } from 'app/screen/screen.service';

@Component({
	selector: 'app-smoothpower-details',
	templateUrl: './smoothpower-details.component.html'
})
@EntityDetailComponent(SmoothPower)
export class SmoothPowerDetailsComponent implements OnInit {

	@Input() entity: SmoothPower = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as SmoothPower;
	}

}
