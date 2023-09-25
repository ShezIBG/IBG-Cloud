import { AppService } from './../../app.service';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-reset-link',
	templateUrl: './reset-link.component.html',
	styleUrls: ['../auth.module.less']
})
export class ResetLinkComponent implements OnInit, OnDestroy {

	showForm = false;
	formMode = '';

	email = '';
	data = {
		token: '',
		new_password: '',
		new_password_conf: ''
	};

	private sub: any;

	constructor(
		public api: ApiService,
		private route: ActivatedRoute,
		public router: Router,
		public app: AppService
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.data.token = params['token'];
			this.api.public.checkResetToken(this.data.token, response => {
				this.email = response.data.email_addr;
				this.showForm = true;
				this.formMode = 'reset';
			}, () => {
				this.showForm = true;
				this.formMode = 'invalid';
			});
		});
	}

	updatePassword() {
		this.api.public.updatePassword(this.data, () => {
			this.showForm = true;
			this.formMode = 'success';
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
