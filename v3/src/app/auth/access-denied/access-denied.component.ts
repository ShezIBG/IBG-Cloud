import { AppService } from './../../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'app-access-denied',
	templateUrl: './access-denied.component.html',
	styleUrls: ['../auth.module.less']
})
export class AccessDeniedComponent {

	constructor(public app: AppService) { }

}
