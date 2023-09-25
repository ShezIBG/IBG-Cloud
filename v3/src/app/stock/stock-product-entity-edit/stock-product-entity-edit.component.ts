import { StockEntitySelectModalComponent } from './../stock-entity-select-modal/stock-entity-select-modal.component';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-product-entity-edit',
	templateUrl: './stock-product-entity-edit.component.html',
	styleUrls: ['./stock-product-entity-edit.component.less']
})
export class StockProductEntityEditComponent implements OnInit, OnDestroy {

	id;
	details;
	owner;
	manufacturerProductCount = 0;
	supplierProductCount = 0;
	ownerHasEntity = false;
	disabled = false;

	private sub: any;

	get suppliesOwnItems() {
		if (!this.details.is_manufacturer || !this.details.is_supplier) return false;
		return !!Mangler.findOne(this.details.manufacturers, { id: this.details.id });
	}

	get hasNew() {
		return !!Mangler.findOne(this.details.suppliers, { added: true }) || !!Mangler.findOne(this.details.manufacturers, { added: true });
	}

	get hasRemoved() {
		return !!this.details.removed_suppliers.length || !!this.details.removed_manufacturers.length;
	}

	get hasRecords() {
		return !!this.details.suppliers.length || !!this.details.manufacturers.length;
	}

	get showUpdateOptions() {
		return this.hasNew || this.hasRemoved || this.hasRecords;
	}

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			this.owner = params['owner'];

			this.details = null;

			const success = response => {
				this.details = response.data.details || {};
				this.manufacturerProductCount = response.data.manufacturer_product_count || 0;
				this.supplierProductCount = response.data.supplier_product_count || 0;
				this.ownerHasEntity = response.data.owner_has_entity;

				this.owner = this.details.owner_level + '-' + this.details.owner_id;

				this.details.is_manufacturer = !!this.details.is_manufacturer;
				this.details.is_supplier = !!this.details.is_supplier;
				this.details.is_owner = !!this.details.is_owner;

				this.details.suppliers.forEach(s => { s.is_primary = !!s.is_primary; });
				this.details.manufacturers.forEach(m => { m.is_primary = !!m.is_primary; });

				this.details.removed_manufacturers = [];
				this.details.removed_suppliers = [];

				// Update flags
				this.details.update_new = true;
				this.details.update_remove = true;
				this.details.update_primary = false;

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Manufacturers and Suppliers', route: '/stock/product-config/entity' },
					{ description: this.id === 'new' ? 'New Entity' : this.details.name }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newEntity(this.owner, success, fail);
			} else {
				this.api.products.getEntity(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.products.saveEntity(this.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Manufacturer created.');
			} else {
				this.app.notifications.showSuccess('Manufacturer updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	archive() {
		if (!confirm('This will remove this entity\'s manufacturer and supplier references from all products. Are you sure you want to archive this entity?')) return;

		if (this.id === 'new') return;

		this.disabled = true;
		this.api.products.archiveEntity(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Entity archived.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	unarchive() {
		if (this.id === 'new') return;

		this.disabled = true;
		this.api.products.unarchiveEntity(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Entity restored.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	addThisEntity() {
		// To assign this entity to itself, it must be a manufacturer AND a supplier

		if (!this.details.is_manufacturer || !this.details.is_supplier) return;
		if (this.suppliesOwnItems) return;

		const primaryFlag = !this.details.suppliers.length;

		this.details.suppliers.push({
			id: this.details.id,
			name: this.details.name,
			posttown: this.details.posttown,
			postcode: this.details.postcode,
			is_primary: primaryFlag,
			added: true
		});

		this.details.suppliers = this.details.suppliers.slice();

		this.details.manufacturers.push({
			id: this.details.id,
			name: this.details.name,
			posttown: this.details.posttown,
			postcode: this.details.postcode,
			is_primary: primaryFlag,
			added: true
		});

		this.details.manufacturers = this.details.manufacturers.slice();
	}

	addSupplier(record) {
		// Handle the currently edited entity separately
		if (record.id === this.details.id) {
			this.addThisEntity();
			return;
		}

		this.details.suppliers.push({
			id: record.id,
			name: record.name,
			posttown: record.posttown,
			postcode: record.postcode,
			is_primary: !this.details.suppliers.length,
			added: true
		});

		this.details.suppliers = this.details.suppliers.slice();
	}

	addManufacturer(record) {
		// Handle the currently edited entity separately
		if (record.id === this.details.id) {
			this.addThisEntity();
			return;
		}

		// Supplier is not in the list, add
		this.details.manufacturers.push({
			id: record.id,
			name: record.name,
			posttown: record.posttown,
			postcode: record.postcode,
			is_primary: false,
			added: true
		});

		this.details.manufacturers = this.details.manufacturers.slice();
	}

	removeSupplier(id) {
		const item = Mangler.findOne(this.details.suppliers, { id });

		if (item) {
			const i = this.details.suppliers.indexOf(item);

			if (i !== -1) {
				this.details.suppliers.splice(i, 1);
				this.details.suppliers = this.details.suppliers.slice();

				if (!item.added) {
					this.details.removed_suppliers.push(item);
					this.details.removed_suppliers = this.details.removed_suppliers.slice();
				}

				if (this.details.suppliers.length && !Mangler.findOne(this.details.suppliers, { is_primary: true })) {
					this.setPrimarySupplier(this.details.suppliers[0].id);
				}

				if (id === this.details.id) this.removeManufacturer(id);
			}
		}
	}

	removeManufacturer(id) {
		const item = Mangler.findOne(this.details.manufacturers, { id });

		if (item) {
			const i = this.details.manufacturers.indexOf(item);

			if (i !== -1) {
				this.details.manufacturers.splice(i, 1);
				this.details.manufacturers = this.details.manufacturers.slice();

				if (!item.added) {
					this.details.removed_manufacturers.push(item);
					this.details.removed_manufacturers = this.details.removed_manufacturers.slice();
				}

				if (id === this.details.id) this.removeSupplier(id);
			}
		}
	}

	restoreSupplier(id) {
		const item = Mangler.findOne(this.details.removed_suppliers, { id });

		if (item) {
			const i = this.details.removed_suppliers.indexOf(item);

			this.details.removed_suppliers.splice(i, 1);
			this.details.removed_suppliers = this.details.removed_suppliers.slice();

			item.is_primary = false;
			this.details.suppliers.push(item);
			this.details.suppliers = this.details.suppliers.slice();

			if (id === this.details.id) this.restoreManufacturer(id);

			if (this.details.suppliers.length && !Mangler.findOne(this.details.suppliers, { is_primary: true })) {
				this.setPrimarySupplier(this.details.suppliers[0].id);
			}
		}
	}

	restoreManufacturer(id) {
		const item = Mangler.findOne(this.details.removed_manufacturers, { id });

		if (item) {
			const i = this.details.removed_manufacturers.indexOf(item);

			this.details.removed_manufacturers.splice(i, 1);
			this.details.removed_manufacturers = this.details.removed_manufacturers.slice();

			this.details.manufacturers.push(item);
			this.details.manufacturers = this.details.manufacturers.slice();

			if (id === this.details.id) this.restoreSupplier(id);
		}
	}

	modalAddSupplier() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			const supplier = event.data;
			if (supplier) {
				if (Mangler.findOne(this.details.suppliers, { id: supplier.id })) {
					this.app.notifications.showPrimary('Supplier is already in the list.');
					return;
				}

				this.addSupplier(supplier);
			}
		});

		this.app.modal.open(StockEntitySelectModalComponent, this.moduleRef, {
			owner: this.owner,
			filters: { is_supplier: true }
		});
	}

	modalAddManufacturer() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			const manufacturer = event.data;
			if (manufacturer) {
				if (Mangler.findOne(this.details.manufacturers, { id: manufacturer.id })) {
					this.app.notifications.showPrimary('Manufacturer is already in the list.');
					return;
				}

				this.addManufacturer(manufacturer);
			}
		});

		this.app.modal.open(StockEntitySelectModalComponent, this.moduleRef, {
			owner: this.owner,
			filters: { is_manufacturer: true }
		});
	}

	setPrimarySupplier(id) {
		this.details.suppliers.forEach(s => {
			s.is_primary = (s.id === id);
		});

		// Update primary state of this entity's record in the manufacturer table as well
		this.details.manufacturers.forEach(m => {
			if (m.id === this.details.id) m.is_primary = (m.id === id);
		});
	}

}
