import { SettingsPaymentGatewayEditModalComponent } from './../settings-payment-gateway-edit-modal/settings-payment-gateway-edit-modal.component';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, Input, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-settings-payment-gateways',
	templateUrl: './settings-payment-gateways.component.html'
})
export class SettingsPaymentGatewaysComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list;
	gocardlessVerificationUrl = '';

	constructor(
		private api: ApiService,
		private app: AppService,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.reload();
	}

	reload() {
		if (this.level) {
			this.api.settings.listPaymentGateways(this.level, this.levelId, response => {
				this.list = response.data.list || [];
				this.gocardlessVerificationUrl = response.data.gocardless_verification_url || '';
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	add(type) {
		this.api.settings.newPaymentGateway(this.level, this.levelId, type, response => {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
				modalSub.unsubscribe();
				this.reload();
			});

			this.app.modal.open(SettingsPaymentGatewayEditModalComponent, this.moduleRef, {
				id: 'new',
				data: response.data.record
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	edit(id) {
		this.api.settings.getPaymentGateway(id, response => {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
				modalSub.unsubscribe();
				this.reload();
			});

			this.app.modal.open(SettingsPaymentGatewayEditModalComponent, this.moduleRef, {
				id: id,
				data: response.data.record
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	authorise(id) {
		this.api.settings.authorisePaymentGateway(id, response => {
			this.app.redirect(response.data);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
