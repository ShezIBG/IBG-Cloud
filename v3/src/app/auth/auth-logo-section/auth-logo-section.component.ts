import { AppService } from './../../app.service';
import { Component } from '@angular/core';
import { Util } from 'app/shared/util';

@Component({
	selector: 'app-auth-logo-section',
	templateUrl: './auth-logo-section.component.html',
	styleUrls: ['./auth-logo-section.component.less']
})
export class AuthLogoSectionComponent {

	constructor(public app: AppService) { }

	isMobile() {
		return Util.isMobile;
	}

}
