import { Router, ActivatedRoute } from '@angular/router';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-isp-auto-select',
	template: ''
})
export class IspAutoSelectComponent implements OnInit {

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private router: Router
	) { }

	ngOnInit() {
		this.api.isp.getFirstIspId(response => {
			this.router.navigate([response.data], { relativeTo: this.route, replaceUrl: true });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
