import { IspInvoiceNoEditModalComponent } from './../isp-invoice-no-edit-modal/isp-invoice-no-edit-modal.component';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-isp-invoice-details',
	templateUrl: './isp-invoice-details.component.html',
	styleUrls: ['./isp-invoice-details.component.less']
})
export class IspInvoiceDetailsComponent implements OnInit, OnDestroy {

	id: any;
	data: any;
	ownerAddress = [];
	customerAddress = [];
	disabled = false;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['invoice'];
			this.data = null;
			this.refresh();
		});
	}

	refresh() {
		this.disabled = true;
		this.api.isp.getInvoice(this.id, response => {
			this.disabled = false;
			this.data = response.data;

			// Compile address arrays
			this.ownerAddress = [];
			if (this.data.invoice.owner_address_line_1) this.ownerAddress.push(this.data.invoice.owner_address_line_1);
			if (this.data.invoice.owner_address_line_2) this.ownerAddress.push(this.data.invoice.owner_address_line_2);
			if (this.data.invoice.owner_address_line_3) this.ownerAddress.push(this.data.invoice.owner_address_line_3);
			if (this.data.invoice.owner_posttown) this.ownerAddress.push(this.data.invoice.owner_posttown);
			if (this.data.invoice.owner_postcode) this.ownerAddress.push(this.data.invoice.owner_postcode);

			this.customerAddress = [];
			if (this.data.invoice.customer_address_line_1) this.customerAddress.push(this.data.invoice.customer_address_line_1);
			if (this.data.invoice.customer_address_line_2) this.customerAddress.push(this.data.invoice.customer_address_line_2);
			if (this.data.invoice.customer_address_line_3) this.customerAddress.push(this.data.invoice.customer_address_line_3);
			if (this.data.invoice.customer_posttown) this.customerAddress.push(this.data.invoice.customer_posttown);
			if (this.data.invoice.customer_postcode) this.customerAddress.push(this.data.invoice.customer_postcode);

			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
			this.app.header.addButton({
				icon: 'md md-print',
				text: 'Print',
				callback: () => {
					window.open(this.data.print_url, '_blank');
				}
			});
			if (this.data.invoice.status !== 'not_approved') {
				this.app.header.addButton({
					icon: 'md md-mail',
					text: 'Email',
					callback: () => {
						if (confirm('Are you sure you want to re-send the invoice to the customer?')) {
							this.api.isp.resendInvoiceEmail(this.id, () => {
								this.app.notifications.showSuccess('Email sent.');
							}, emailResp => {
								this.app.notifications.showDanger(emailResp.message);
							});
						}
					}
				});
			}
			if (this.data.invoice.status === 'not_approved') {
				this.app.header.addButton({
					icon: 'md md-edit',
					text: 'Edit invoice number',
					callback: () => {
						this.editInvoiceNo();
					}
				});
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	approveInvoice() {
		if (confirm('Are you sure you want to approve this invoice?')) {
			this.disabled = true;
			this.api.isp.approveInvoice(this.id, _ => {
				this.app.notifications.showSuccess('Invoice has been approved.');
				this.refresh();
			}, response => {
				this.disabled = false;
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	setInvoiceStatus(status) {
		switch (status) {
			case 'paid':
				if (!confirm('Are you sure you want to mark this invoice as PAID?')) return;
				break;

			case 'outstanding':
				if (!confirm('Are you sure you want to mark this invoice as OUTSTANDING?')) return;
				break;

			case 'cancelled':
				if (!confirm('Are you sure you want to CANCEL this invoice?')) return;
				break;

			default:
				// Invalid status
				return;
		}

		this.disabled = true;
		this.api.isp.setInvoiceStatus(this.id, status, () => {
			this.app.notifications.showSuccess('Invoice updated.');
			this.refresh();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	editInvoiceNo() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.refresh();
		});

		this.app.modal.open(IspInvoiceNoEditModalComponent, this.moduleRef, {
			invoice_id: this.id
		});
	}

}
