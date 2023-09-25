import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-logout',
	template: ``
})
export class LogoutComponent implements OnInit {

	constructor(
		private api: ApiService
	) { }

	ngOnInit() {
		this.api.logout();
	}


}
