import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { IspAreaNoteModalComponent } from '../isp-area-note-modal/isp-area-note-modal.component';

@Component({
	selector: 'app-isp-area-details',
	templateUrl: './isp-area-details.component.html'
})
export class IspAreaDetailsComponent implements OnInit, OnDestroy {

	isp_id;
	area_id;
	data;
	timer;
	destroyed;

	first = true;
	dirty = false;
	wifi_ssid;
	wifi_password;
	pending_wifi_password;
	wifi_ssid_disabled = false;
	wifi_password_disabled = false;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.area_id = params['area'] || '';
			this.isp_id = params['isp'] || '';
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
		clearTimeout(this.timer);
	}

	refresh() {
		clearTimeout(this.timer);

		this.api.isp.getArea(this.area_id, response => {
			if (this.destroyed) return;
			this.data = response.data;

			if (this.first) {
				this.first = false;

				if (this.data.area.onu) {
					this.wifi_ssid = this.data.area.onu.pending_wifi_ssid || this.data.area.onu.wifi_ssid || '';
					this.wifi_password = this.data.area.onu.pending_wifi_password || this.data.area.onu.wifi_password || '';
				}
			}
			this.wifi_ssid_disabled = !!this.data.area.onu.pending_wifi_ssid;
			this.wifi_password_disabled = !!this.data.area.onu.pending_wifi_password;

			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
			if (!this.data.area.isp_notes) this.app.header.addButton({
				icon: 'md md-add',
				text: 'Add note',
				callback: () => {
					this.editAreaNote();
				}
			});
			this.timer = setTimeout(() => this.refresh(), 30000);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
			this.timer = setTimeout(() => this.refresh(), 30000);
		});
	}

	editAreaNote() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.refresh();
		});

		this.app.modal.open(IspAreaNoteModalComponent, this.moduleRef, {
			id: this.area_id,
			notes: this.data.area.isp_notes
		});
	}

	isPackageActive(pkg) {
		if (this.data.area.onu && this.data.area.onu.active_package) {
			return this.data.area.onu.active_package.id === pkg.id;
		}
		return false;
	}

	setPackage(p) {
		if (!this.data.area.onu) return;

		this.api.isp.setOnuPackage(this.data.area.onu.id, p, () => {
			this.app.notifications.showSuccess('Command sent to ONU.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	reboot() {
		if (!this.data.area.onu) return;
		if (!confirm('Are you sure you want to reboot this ONU?')) return;

		this.api.isp.rebootOnu(this.data.area.onu.id, () => {
			this.app.notifications.showSuccess('Command sent to ONU.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	saveWiFiSettings() {
		if (this.data.area.onu) {
			this.api.isp.setWiFiSettings({
				id: this.data.area.onu.id,
				wifi_ssid: this.wifi_ssid,
				wifi_password: this.wifi_password
			}, () => {
				this.app.notifications.showSuccess('Wi-Fi settings saved.');
				this.dirty = false;
				this.first = true;
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	pendingWiFiSettings() {
		if (this.data.area.onu) {
			this.api.isp.pendWiFiSettings({
				id: this.data.area.onu.id,
				wifi_ssid: this.wifi_ssid,
				wifi_password: this.wifi_password
			}, () => {
				this.app.notifications.showSuccess('Wi-Fi settings saved.');
				this.dirty = false;
				this.first = true;
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}



	todoWiFiSettings() {
		if (this.data.area.onu) {
			this.api.isp.todoWiFiSettings({
				id: this.data.area.onu.id,
				wifi_ssid: this.wifi_ssid,
				wifi_password: this.wifi_password
			}, () => {
				this.app.notifications.showSuccess('Wi-Fi settings saved.');
				this.dirty = true;
				this.first = true;
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	cancelSettings() {
		if (this.data.area.onu) {
			this.api.isp.cancelWiFiSettings({
				id: this.data.area.onu.id,
				pending_wifi_ssid: null,
				pending_wifi_password: null
			}, () => {
				this.app.notifications.showSuccess('Wi-Fi settings cancelled.');
				this.dirty = false;
				this.first = true;
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

}
