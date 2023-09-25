import { Injectable, EventEmitter } from '@angular/core';

@Injectable()
export class IspService {

	private ispId = 0;
	public showArchivedCustomers = false;
	public withActiveContracts = false;
	public invoiceFilters;

	public onIspChanged: EventEmitter<number> = new EventEmitter<number>();

	get id() { return this.ispId; }
	set id(value) {
		if (this.ispId !== value) {
			this.ispId = value;
			this.onIspChanged.emit(this.ispId);
		}
	}

}
