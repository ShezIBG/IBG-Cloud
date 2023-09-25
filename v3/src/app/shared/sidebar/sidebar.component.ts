import { AppService } from './../../app.service';
import { Router, NavigationEnd } from '@angular/router';
import { Component, OnInit, OnChanges, HostBinding } from '@angular/core';

export interface SidebarMenuItem {
	name: string,
	header?: boolean,
	icon?: string,
	url?: string,
	route?: string,
	items?: SidebarSubMenuItem[],
	badge?: string,
	badgeIcon?: string,
	selected?: boolean
}

export interface SidebarSubMenuItem {
	name: string,
	url?: string,
	route?: string,
}

export interface SidebarMenuData {
	menu?: SidebarMenuItem[],
	dropdown?: SidebarMenuItem[]
}

@Component({
	selector: 'app-sidebar',
	templateUrl: './sidebar.component.html'
})
export class SidebarComponent implements OnInit, OnChanges {

	@HostBinding('class') class = 'theme-dark';

	expanded: any[] = [];
	autoExpanded: any[] = [];
	active: any[] = [];

	private _menu: SidebarMenuItem[] = [];
	get menu() { return this._menu; }

	private _dropdown: SidebarMenuItem[] = [];
	get dropdown() { return this._dropdown; }

	constructor(
		private router: Router,
		public app: AppService
	) { }

	ngOnInit() {
		this.router.events.subscribe(event => {
			if (event instanceof NavigationEnd) {
				this.onRouteChange();
			}
		});
		this.onRouteChange();
	}

	ngOnChanges() {
		this.onRouteChange();
	}

	onRouteChange() {
		const newActive = [];
		const newAutoExpanded = [];

		this.menu.forEach(item => {
			if (item.header) return;

			const hasSub = item.items && item.items.length > 0;

			// If route is an exact match, highlight and expand
			// We also allow partial matching if it has no children
			if (item.route && this.router.isActive(item.route, hasSub)) {
				newActive.push(item);
				if (hasSub) newAutoExpanded.push(item);
			}

			if (hasSub) {
				// Loop through all subitems
				item.items.forEach(subItem => {
					// If route is an exact match, highlight subitem
					if (subItem.route && this.router.isActive(subItem.route, true)) {
						newActive.push(subItem);

						// If parent is not auto-expanded yet, add to the list
						if (newAutoExpanded.indexOf(item) === -1) newAutoExpanded.push(item);
					}
				});
			}
		});

		this.active = newActive;
		this.autoExpanded = newAutoExpanded;
	}

	toggleOpen(item: any) {
		if (!item.items || item.items.length === 0) return;
		const index = this.expanded.indexOf(item);
		if (index === -1) {
			this.expanded.push(item);
		} else {
			this.expanded.splice(index, 1);
		}
	}

	isOpen(item: any) {
		return this.autoExpanded.indexOf(item) !== -1 || this.expanded.indexOf(item) !== -1;
	}

	isActive(item: any) {
		return this.active.indexOf(item) !== -1;
	}

	setMenuData(data: SidebarMenuData) {
		this._menu = (data ? data.menu : []) || [];
		this._dropdown = (data ? data.dropdown : []) || [];
		this.onRouteChange();
	}

	setMenu(menu: SidebarMenuItem[], dropdown: SidebarMenuItem[] = []) {
		this._menu = menu || [];
		this._dropdown = dropdown || [];
		this.onRouteChange();
	}

}
