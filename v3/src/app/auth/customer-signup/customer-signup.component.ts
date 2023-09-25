import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { Subscription } from 'rxjs';

@Component({
	selector: 'app-customer-signup',
	templateUrl: './customer-signup.component.html',
	styleUrls: ['../auth.module.less']
})
export class CustomerSignupComponent implements OnInit, OnDestroy {

	id;
	hash;
	name;

	message = '';
	error = false;
	showForm = false;

	password = '';
	passwordConf = '';

	private sub: Subscription;

	constructor(
		public api: ApiService,
		public router: Router,
		private route: ActivatedRoute,
		public app: AppService
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.showForm = false;
			this.id = params['id'];
			this.hash = params['hash'];

			this.api.public.getCustomerSignup({
				id: this.id,
				hash: this.hash
			}, response => {
				this.showForm = true;
				this.message = '';
				this.error = false;
				this.name = response.data.name;
			}, response => {
				this.showForm = true;
				this.error = true;
				this.message = response.message;
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	signUp() {
		this.api.public.submitCustomerSignup({
			id: this.id,
			hash: this.hash,
			password: this.password,
			password_conf: this.passwordConf
		}, response => {
			this.message = 'success';
			this.error = false;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
