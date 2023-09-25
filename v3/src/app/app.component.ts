import { Component, ViewChild, AfterViewInit, OnInit } from '@angular/core';
import { DomHandler } from 'primeng/primeng';
import { AppService } from './app.service';
import { HeaderComponent } from './shared/header/header.component';
import { NotificationsComponent } from './shared/notifications/notifications.component';
import { ModalLoaderComponent } from './shared/modal/modal-loader.component';
import { SidebarComponent } from './shared/sidebar/sidebar.component';
import { Router, NavigationEnd, ActivatedRoute } from '@angular/router';

declare var Mangler: any;

@Component({
	selector: 'app-root',
	templateUrl: './app.component.html'
})
export class AppComponent implements AfterViewInit, OnInit {

	@ViewChild(ModalLoaderComponent) modalLoader: ModalLoaderComponent;
	@ViewChild(NotificationsComponent) notifications: NotificationsComponent;
	@ViewChild(SidebarComponent) sidebar: SidebarComponent;
	@ViewChild(HeaderComponent) header: HeaderComponent;

	constructor(
		private app: AppService,
		private router: Router,
		private route: ActivatedRoute
	) {
		// Make sure primeng calendar uses higher zIndex values than the modal
		DomHandler.zindex = 2000;
	}

	ngOnInit() {
		this.router.events.subscribe(event => {
			if (event instanceof NavigationEnd) {
				this.refreshRouteData();
			}
		});
	}

	ngAfterViewInit() {
		this.app.modal = this.modalLoader;
		this.app.notifications = this.notifications;
		this.app.sidebar = this.sidebar;
		this.app.header = this.header;
	}

	private refreshRouteData() {
		this.app.routeData = { auth: true };
		let child = this.route;
		while (child) {
			if (child.snapshot) Mangler.merge(this.app.routeData, child.snapshot.data);
			child = child.firstChild;
		}
	}

}
