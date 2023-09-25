import { Router, NavigationEnd } from '@angular/router';
import { Component } from '@angular/core';

declare var Mangler: any;

export interface HeaderTab {
	id: any,
	title?: string,
	route?: any,
	badge?: string,
	badgeClass?: string,
	hidden?: boolean
}

export interface HeaderCrumb {
	description: string,
	route?: string,
	compact?: boolean
}

export interface HeaderButton {
	icon?: string,
	text?: string,
	cls?: string,
	callback: Function
}

@Component({
	selector: 'app-header',
	templateUrl: './header.component.html',
	styleUrls: ['./header.component.less']
})
export class HeaderComponent {

	crumbs: HeaderCrumb[] = [];
	tabs: HeaderTab[] = [];
	buttons: HeaderButton[] = [];
	activeTab = null;
	headerVisible = false;
	sidebarVisible = true;

	constructor(private router: Router) {
		this.router.events.subscribe(event => {
			if (event instanceof NavigationEnd) {
				this.routeChanged();
			}
		});
	}

	routeChanged() {
		this.headerVisible = false;
	}

	clearAll() {
		this.clearCrumbs();
		this.clearTabs();
		this.clearButtons();
	}

	private refreshVisibility() {
		let crumbsVisible = !!this.crumbs.length;
		if (this.crumbs.length === 1 && this.crumbs[0].compact) crumbsVisible = false;

		this.headerVisible = !!(crumbsVisible || this.tabs.length || this.buttons.length);
	}

	clearCrumbs() {
		this.crumbs = [];
		this.refreshVisibility();
	}

	addCrumbs(crumbs: HeaderCrumb[]) {
		crumbs.forEach(crumb => this.crumbs.push(crumb));
		this.refreshVisibility();
	}

	addCrumb(crumb: HeaderCrumb) {
		this.crumbs.push(crumb);
		this.crumbs = this.crumbs.slice();
		this.refreshVisibility();
	}

	clearTabs() {
		this.tabs = [];
		this.refreshVisibility();
	}

	addTab(tab: HeaderTab) {
		if (this.tabs.length === 0) this.activeTab = tab.id;
		this.tabs.push(tab);
		this.tabs = this.tabs.slice();
		this.refreshVisibility();
	}

	setTab(id) {
		if (id === null || typeof id === 'undefined') return;
		if (Mangler.findOne(this.tabs, { id })) {
			this.activeTab = id;
		}
	}

	getTab(id: string) {
		return Mangler.findOne(this.tabs, { id });
	}

	clearButtons() {
		this.buttons = [];
		this.refreshVisibility();
	}

	addButton(button: HeaderButton) {
		if (!button.icon) button.icon = '';
		if (!button.text) button.text = '';
		if (!button.cls) button.cls = 'btn btn-primary';

		setTimeout(() => {
			this.buttons.push(button);
			this.buttons = this.buttons.slice();
			this.refreshVisibility();
		}, 50);
	}

	addButtons(buttonList: HeaderButton[]) {
		setTimeout(() => {
			buttonList.forEach(button => {
				if (!button.icon) button.icon = '';
				if (!button.text) button.text = '';
				if (!button.cls) button.cls = 'btn btn-primary';

				this.buttons.push(button);
			});
			this.buttons = this.buttons.slice();
			this.refreshVisibility();
		}, 50);
	}

	removeButton(button: HeaderButton) {
		const i = this.buttons.indexOf(button);
		if (i !== -1) {
			this.buttons.splice(i, 1);
			this.buttons.slice();
			this.refreshVisibility();
		}
	}

}
