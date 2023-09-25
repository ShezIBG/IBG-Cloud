import { AppService } from './../../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'app-reset-successful',
	templateUrl: './reset-successful.component.html',
	styleUrls: ['../auth.module.less']
})
export class ResetSuccessfulComponent {

	constructor(public app: AppService) { }

}
