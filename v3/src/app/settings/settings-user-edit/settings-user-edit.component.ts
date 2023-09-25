import { SettingsSelectLevelModalComponent } from './../settings-select-level-modal/settings-select-level-modal.component';
import { AppService } from './../../app.service';
import { Router, ActivatedRoute } from '@angular/router';
import { Location } from '@angular/common';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, Input, NgModuleRef } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-settings-user-edit',
	templateUrl: './settings-user-edit.component.html'
})
export class SettingsUserEditComponent implements OnInit, OnDestroy {

	@Input() userId;

	private sub: any;

	id;
	data;
	disabled = false;
	emailCheck = true;
	emailToValidate = '';

	list = [];
	removed = [];
	deleteFlag = false;

	highlightLevel = null;
	highlightId = null;

	level = '';
	levelId = '';

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute,
		private location: Location,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || this.userId || 'new';
			this.data = null;
			this.level = params['level'];
			this.levelId = params['levelId'];

			this.highlightLevel = this.level;
			this.highlightId = this.levelId;

			if (this.id === 'new') {
				if (this.level) {
					this.api.settings.getNewUserCrumbs(this.level, this.levelId, response => {
						this.app.header.clearAll();
						this.app.header.addCrumbs(response.data['breadcrumbs']);
					});
				} else {
					setTimeout(() => {
						this.app.header.clearAll();
						this.app.header.addCrumb({ description: 'New User' });
					}, 100);
				}
			} else {
				this.getUser();
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	getUser() {
		this.api.settings.getUser(this.id, this.level, this.levelId, response => {
			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
			this.data = response.data;
			this.removed = [];
			this.refreshList();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	validateEmail() {
		this.disabled = true;
		this.api.settings.getUserIdByEmail(this.emailToValidate, validateResponse => {
			if (validateResponse.data === 'new') {
				this.api.settings.newUser(this.emailToValidate, this.level, this.levelId, response => {
					this.disabled = false;
					this.emailCheck = false;
					this.app.header.clearCrumbs();
					this.app.header.addCrumbs(response.data.breadcrumbs);
					this.data = response.data;
					this.removed = [];
					this.refreshList();
				}, response => {
					this.disabled = false;
					this.app.notifications.showDanger(response.message);
				});
			} else {
				this.disabled = false;
				if (this.level) {
					this.router.navigate(['/settings/user', validateResponse.data, this.level, this.levelId], { replaceUrl: true });
				} else {
					this.router.navigate(['/settings/user', validateResponse.data], { replaceUrl: true });
				}
				this.app.notifications.showPrimary('User already has an account.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshList() {
		this.deleteFlag = false;
		this.list = [];
		if (!this.data.levels) return;

		const sorted = this.data.levels.slice();
		sorted.sort((a, b) => {
			// Sort by level then description
			if (a.level_index < b.level_index) return -1;
			if (a.level_index > b.level_index) return 1;
			return ('' + a.description).localeCompare(b.description);
		});

		const tree = Mangler();
		const allRoles = {};
		sorted.forEach(item => {
			item.deleteFlag = false;

			// Add roles to the lookup
			item.roles.forEach(group => {
				group.items.forEach(role => {
					allRoles[role.id] = role;
				});
			});

			// Find parent with smallest level_index among the already inserted elements
			let bestParent = null;
			Mangler.each(item.parents, (k, v) => {
				const parent = tree.first({ level: k, id: v });
				if (parent) {
					if (!bestParent || parent.level_index > bestParent.level_index) {
						bestParent = parent;
					}
				}
			});

			// No parent found, add and return
			if (!bestParent) {
				item.depth = 0;
				tree.push(item);
				return;
			} else {
				if (bestParent.deleteFlag || (bestParent.selected && allRoles[bestParent.selected].is_admin)) {
					item.deleteFlag = true;
					this.deleteFlag = true;
				}

				item.depth = bestParent.depth + 32;
				let i = tree.items.indexOf(bestParent) + 1;

				// Find last item at the items depth to insert after
				while (tree.items[i] && tree.items[i].depth >= item.depth) i++;

				tree.items.splice(i, 0, item);
			}
		});

		this.list = tree.items;
	}

	goBack() {
		this.location.back();
	}

	deleteLevel(level) {
		const i = this.data.levels.indexOf(level);
		if (i === -1) return;

		this.highlightLevel = level.level;
		this.highlightId = level.id;

		this.removed.push(level);
		this.data.levels.splice(i, 1);
		this.refreshList();
	}

	undeleteLevel(level) {
		const i = this.removed.indexOf(level);
		if (i === -1) return;

		this.highlightLevel = level.level;
		this.highlightId = level.id;

		this.data.levels.push(level);
		this.removed.splice(i, 1);
		this.refreshList();
	}

	addLevel() {
		this.app.modal.open(SettingsSelectLevelModalComponent, this.moduleRef, {});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const level = event.data[0];
				const id = event.data[1];

				this.highlightLevel = level;
				this.highlightId = id;

				if (Mangler.first(this.data.levels, { level, id }) || Mangler.first(this.removed, { level, id })) {
					this.app.notifications.showWarning('The selected level is already in the list', 'It has been highlighted.');
				} else {
					this.api.settings.getPermissionLevelDetails(level, id, response => {
						this.data.levels.push(response.data);
						this.refreshList();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
				}
			}
		});
	}

	suspendUser() {
		this.list.forEach(level => this.deleteLevel(level));
	}

	save() {
		this.disabled = true;

		const data = {
			details: this.data.details,
			roles: {
				added: [],
				modified: [],
				deleted: []
			}
		}

		this.removed.forEach(item => {
			if (item.original !== null) data.roles.deleted.push({ level: item.level, id: item.id });
		});

		this.list.forEach(item => {
			if (item.deleteFlag) {
				if (item.original !== null) data.roles.deleted.push({ level: item.level, id: item.id });
			} else if (item.original === null) {
				data.roles.added.push({ level: item.level, id: item.id, role_id: item.selected });
			} else if (item.original !== item.selected) {
				data.roles.modified.push({ level: item.level, id: item.id, role_id: item.selected });
			}
		});

		this.api.settings.saveUser(data, () => {
			this.disabled = false;
			if (!this.userId) {
				this.goBack();
				this.app.notifications.showSuccess(this.id === 'new' ? 'User created.' : 'User updated.');
			} else {
				this.app.notifications.showSuccess('User profile updated.');
				this.getUser();
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
