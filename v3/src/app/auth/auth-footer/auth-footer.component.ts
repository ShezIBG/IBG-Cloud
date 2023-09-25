import { AppService } from './../../app.service';
import { Component } from '@angular/core';
import { Util } from 'app/shared/util';

@Component({
	selector: 'app-auth-footer',
	templateUrl: './auth-footer.component.html',
	styleUrls: ['./auth-footer.component.less']
})
export class AuthFooterComponent {

	constructor(public app: AppService) { }

	isMobile() {
		return Util.isMobile;
	}

}
