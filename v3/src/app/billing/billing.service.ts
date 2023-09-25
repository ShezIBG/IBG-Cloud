import { Injectable, EventEmitter } from '@angular/core';

@Injectable()
export class BillingService {

	private owner = 0;
	public showArchivedCustomers = false;
	public withActiveContracts = false;
	public showArchivedInvoiceEntities = false;
	public invoiceEntitiesWithActiveContracts = false;
	public invoiceFilters;

	public onOwnerChanged: EventEmitter<number> = new EventEmitter<number>();

	get id() { return this.owner; }
	set id(value) {
		if (this.owner !== value) {
			this.owner = value;
			this.onOwnerChanged.emit(this.owner);
		}
	}

}
