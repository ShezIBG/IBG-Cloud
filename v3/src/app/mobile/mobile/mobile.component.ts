import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AppService } from 'app/app.service';
import { ModuleComponent } from 'app/shared/module/module.component';
import { Util } from 'app/shared/util';

@Component({
	selector: 'app-mobile',
	template: `
		<div class="layout-full {{app.branding}}">
			<div class="flex-col flex-1">
				<app-mobile-header></app-mobile-header>
				<div class="flex-1 scrollable p-20">
					<router-outlet></router-outlet>
				</div>
			</div>
		</div>
	`
})
export class MobileComponent extends ModuleComponent implements OnInit {

	constructor(
		public app: AppService,
		private router: Router
	) {
		super(app);
	}

	ngOnInit() {
		// Redirect to desktop site if not on mobile
		// TODO: Uncomment line below
		// if (!Util.isMobile) this.router.navigate(['/']);
	}

}
