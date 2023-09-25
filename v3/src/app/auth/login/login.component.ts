import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Util } from 'app/shared/util';

@Component({
	selector: 'app-login',
	templateUrl: './login.component.html',
	styleUrls: ['../auth.module.less']
})
export class LoginComponent implements OnInit {

	showForm = false;
	email = '';
	password = '';
	rememberme = false;

	constructor(
		public api: ApiService,
		public router: Router,
		public app: AppService
	) { }

	ngOnInit() {
		this.checkLogin();
	}

	login() {
		this.api.public.login(this.email, this.password, this.rememberme, (response) => {
			if (response.data.account.ok) {
				this.checkLogin();
			} else {
				this.router.navigate(response.data.account.redirect_route);
			}
		}, response => {
			this.app.notifications.showDanger(response.message);
			this.checkLogin();
		});
	}

	checkLogin() {
		this.api.auth.getBillingAccount(response => {
			// TODO: Redirect to URL passed or old root
			// Once old screens are purged, it will need updating.
			// this.router.navigate(['/']);
			this.showForm = false;
			if (response.data.ok) {
				this.app.redirect(this.app.getAppURL());
				}
			else {
				this.router.navigate(response.data.redirect_route);
			}
		}, () => {
			this.showForm = true;
		});
	}

}
