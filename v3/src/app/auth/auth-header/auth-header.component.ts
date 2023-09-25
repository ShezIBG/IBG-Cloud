import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'app-auth-header',
	templateUrl: './auth-header.component.html',
	styleUrls: ['./auth-header.component.less']
})
export class AuthHeaderComponent {

	@Input() signOut = false;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	logout() {
		this.api.logout();
	}

}
