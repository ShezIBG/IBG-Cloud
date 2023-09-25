import { AppService } from './../../app.service';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component } from '@angular/core';

@Component({
	selector: 'app-reset',
	templateUrl: './reset.component.html',
	styleUrls: ['../auth.module.less']
})
export class ResetComponent {

	email = '';

	constructor(
		public app: AppService,
		public api: ApiService,
		public router: Router,
		private route: ActivatedRoute,
	) { }

	resetPassword() {
		this.api.public.resetPassword(this.email, () => {
			this.app.notifications.showSuccess('Password reset email has been sent.');
			this.router.navigate(['..', 'reset-successful'], { relativeTo: this.route });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
