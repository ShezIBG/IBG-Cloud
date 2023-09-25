import { StockBundleQuestionEditModalComponent } from './../stock-bundle-question-edit-modal/stock-bundle-question-edit-modal.component';
import { StockBundleCounterEditModalComponent } from './../stock-bundle-counter-edit-modal/stock-bundle-counter-edit-modal.component';
import { BundleOptions } from './../../shared/bundle-options';
import { StockEntitySelectModalComponent } from './../stock-entity-select-modal/stock-entity-select-modal.component';
import { DecimalPipe } from './../../shared/decimal.pipe';
import { StockProductCloneModalComponent } from './../stock-product-clone-modal/stock-product-clone-modal.component';
import { StockProductSelectModalComponent } from './../stock-product-select-modal/stock-product-select-modal.component';
import { Location } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, ViewChild, NgModuleRef } from '@angular/core';

declare var Mangler: any;
declare var $: any;

@Component({
	selector: 'app-stock-product-edit',
	templateUrl: './stock-product-edit.component.html',
	styleUrls: ['./stock-product-edit.component.less']
})
export class StockProductEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	id;
	details;
	editable;
	owner;
	list;
	usedBy;
	barcodeURL;
	bundle: BundleOptions = null;
	hoveredQuestion = null;
	disabled = false;

	selectedPricingStructure = null;
	highlightedAlternative = null;
	highlightedPlaceholder = null;
	highlightedAccessory = null;
	highlightedBom = null;

	defaultPricingStructure = {
		distribution_method: 'custom',
		reseller_method: 'custom',
		trade_method: 'custom',
		retail_method: 'custom'
	};

	labourIndex = [];
	subscriptionIndex = [];
	recommendedLabourList = [];

	imageUrl = null;
	draggedOver = false;

	usedByTotal = 0;
	showIsSeparableColumn = false;

	labourPricing = false;
	subscriptionPricing = false;

	manufacturerSuppliers = [];

	tabs: any = {
		details: { id: 'details', title: 'Product details' },
		bom: { id: 'bom', title: 'Bill of materials', badgeClass: 'badge-success' },
		bundle: { id: 'bundle', title: 'Bundle options', badgeClass: 'badge-success' },
		placeholder: { id: 'placeholder', title: 'Placeholder items', badgeClass: 'badge-success' },
		alternative: { id: 'alternative', title: 'Alternative products', badgeClass: 'badge-success' },
		accessories: { id: 'accessories', title: 'Accessories', badgeClass: 'badge-success' },
		used: { id: 'used', title: 'Used by', badgeClass: 'badge-info' }
	};

	get hasBOM() { return !!this.details.has_bom; }
	set hasBOM(value) {
		this.details.has_bom = value ? 1 : 0;
		if (value) {
			this.isPlaceholder = false;
			this.isBundle = false;
			this.isStocked = true;
			this.recalculatePricing();
		}
		this.refreshTabs();
	}

	get isPlaceholder() { return !!this.details.is_placeholder; }
	set isPlaceholder(value) {
		this.details.is_placeholder = value ? 1 : 0;
		if (value) {
			this.hasBOM = false;
			this.isBundle = false;
			this.isStocked = false;
			this.recalculatePricing();
		}
		this.refreshTabs();
	}

	get isBundle() { return !!this.details.is_bundle; }
	set isBundle(value) {
		this.details.is_bundle = value ? 1 : 0;
		if (value) {
			this.hasBOM = false;
			this.isPlaceholder = false;
			this.isStocked = true;
			this.recalculatePricing();
		}
		this.refreshTabs();
	}

	get isStocked() { return !!this.details.is_stocked; }
	set isStocked(value) {
		this.details.is_stocked = value ? 1 : 0;
		if (!value) {
			this.hasBOM = false;
			this.recalculatePricing();
		} else {
			this.isPlaceholder = false;
			this.recalculatePricing();
		}
		this.refreshTabs();
	}

	get discontinued() { return !!this.details.discontinued; }
	set discontinued(value) {
		this.details.discontinued = value ? 1 : 0;
		this.refreshTabs();
	}

	get canChangeUnit() { return this.details && this.editable && this.usedBy.placeholder.length === 0 && this.usedBy.bom.length === 0 && (!this.isPlaceholder || !this.details.placeholders.length) && (this.isPlaceholder || !this.details.alternatives.length); }
	get canChangeAssembly() { return this.details && this.editable && (!this.isPlaceholder || !this.details.placeholders.length) && (!this.hasBOM || !this.details.bom.length); }
	get canChangeStocked() { return this.details && this.editable && !this.hasBOM && !this.isPlaceholder && !this.details.alternatives.length; }

	get soldToCustomer() { return !!this.details.sold_to_customer; }
	set soldToCustomer(value) { this.details.sold_to_customer = value ? 1 : 0; }

	get soldToReseller() { return !!this.details.sold_to_reseller; }
	set soldToReseller(value) { this.details.sold_to_reseller = value ? 1 : 0; }

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private router: Router,
		private location: Location,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			this.owner = params['owner'];

			this.details = null;
			this.list = null;
			this.usedBy = null;
			this.usedByTotal = 0;

			const success = response => {
				this.details = response.data.details || {};
				this.editable = response.data.editable;
				this.imageUrl = response.data.image_url;
				this.list = response.data.list;
				this.usedBy = response.data.used_by;
				this.labourPricing = response.data.labour_pricing;
				this.subscriptionPricing = response.data.subscription_pricing;
				this.barcodeURL = response.data.barcode_url;

				// Create bundle object
				if (this.details.bundle) {
					this.bundle = new BundleOptions(this.details.bundle);
					delete this.details.bundle;
				} else {
					this.bundle = null;
				}

				this.labourIndex = Mangler.index(this.list.labour_types, 'id');
				this.subscriptionIndex = Mangler.index(this.list.subscription_types, 'id');

				let cIndex, unassigned;

				// Put labour types into categories
				this.list.labour_categories.forEach(c => c.types = []);
				cIndex = Mangler.index(this.list.labour_categories, 'id');
				unassigned = { id: 0, description: 'Unassigned', types: [] };
				cIndex[0] = unassigned;
				this.list.labour_types.forEach(t => {
					const c = cIndex[t.category_id || 0] || cIndex[0];
					c.types.push(t);
				});
				if (unassigned.types.length) this.list.labour_categories.push(unassigned);

				// Put subscription types into categories
				this.list.subscription_categories.forEach(c => c.types = []);
				cIndex = Mangler.index(this.list.subscription_categories, 'id');
				unassigned = { id: 0, description: 'Unassigned', types: [] };
				cIndex[0] = unassigned;
				this.list.subscription_types.forEach(t => {
					const c = cIndex[t.category_id || 0] || cIndex[0];
					c.types.push(t);
				});
				if (unassigned.types.length) this.list.subscription_categories.push(unassigned);

				// Put systems into modules
				this.list.modules.forEach(c => c.systems = []);
				cIndex = Mangler.index(this.list.modules, 'id');
				unassigned = { id: 0, description: 'Unassigned', systems: [] };
				cIndex[0] = unassigned;
				this.list.systems.forEach(s => {
					const m = cIndex[s.module_id || 0] || cIndex[0];
					m.systems.push(s);
				});
				if (unassigned.systems.length) this.list.modules.push(unassigned);

				this.usedByTotal = 0;
				Mangler.each(this.usedBy, (k, v) => {
					if (Mangler.isArray(v)) this.usedByTotal += v.length;
				});

				// Make a list of recommended labour
				this.recommendedLabourList = this.details.labour.filter(l => !l.editable);

				// Make supplier flags boolean
				this.details.suppliers.forEach(s => { s.is_primary = !!s.is_primary });

				this.refreshLabour();
				this.refreshSelections();
				this.formatNumbers();

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Products', route: '/stock/product' },
					{ description: this.id === 'new' ? 'New Product' : this.details.model || this.details.sku }
				]);

				this.refreshTabs();
				this.app.header.setTab('details');

				if (this.id !== 'new' && this.editable) {
					this.app.header.addButton({
						icon: 'md md-content-copy',
						text: 'Clone this product',
						callback: () => this.cloneProduct()
					});
				}

				this.refreshManufacturerSuppliers();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newProduct(this.owner, success, fail);
			} else {
				this.api.products.getProduct(this.id, this.owner, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refreshTabs() {
		const oldTab = this.app.header.activeTab;

		// Add enabled tabs from pre-generated tab instance list

		this.app.header.clearTabs();
		this.app.header.addTab(this.tabs.details);
		if (this.editable && this.hasBOM) this.app.header.addTab(this.tabs.bom);
		if (this.editable && this.isBundle) this.app.header.addTab(this.tabs.bundle);
		if (this.editable && this.isPlaceholder) this.app.header.addTab(this.tabs.placeholder);
		if (this.editable && !this.isPlaceholder && !this.isBundle && this.isStocked) this.app.header.addTab(this.tabs.alternative);
		if (this.editable && !this.isPlaceholder) this.app.header.addTab(this.tabs.accessories);
		if (this.editable && this.usedByTotal) this.app.header.addTab(this.tabs.used);

		// Refresh tab badges

		this.tabs.used.badge = this.usedByTotal ? '' + this.usedByTotal : '';
		this.tabs.used.badgeClass = this.discontinued ? 'badge-danger' : 'badge-info';
		this.tabs.bom.badge = this.details.bom.length ? '' + this.details.bom.length : '';
		this.tabs.placeholder.badge = this.details.placeholders.length ? '' + this.details.placeholders.length : '';
		this.tabs.alternative.badge = this.details.alternatives.length ? '' + this.details.alternatives.length : '';
		this.tabs.accessories.badge = this.details.accessories.length ? '' + this.details.accessories.length : '';

		if (oldTab) this.app.header.setTab(oldTab);
	}

	setRecommendedLabour(value) {
		this.details.recommended_labour = value ? 1 : 0;
		this.refreshLabour();
	}

	refreshLabour() {
		// First, remove all recommended labour from the list
		this.details.labour = this.details.labour.filter(l => l.editable);

		// If recommended labour is enabled, add them back from the saved list for display
		if (this.details.recommended_labour) {
			this.details.labour = this.recommendedLabourList.concat(this.details.labour);
		}
	}

	refreshSelections() {
		this.selectedPricingStructure = this.details.pricing_structure_id ? Mangler.first(this.list.pricing_structures, { id: this.details.pricing_structure_id }) || this.defaultPricingStructure : this.defaultPricingStructure;
		this.recalculatePricing();
	}

	formatNumbers(addThousandSeparators: boolean = true) {
		this.details.width = parseInt(this.details.width, 10) || 0;
		this.details.height = parseInt(this.details.height, 10) || 0;
		this.details.depth = parseInt(this.details.depth, 10) || 0;
		this.details.unit_cost = DecimalPipe.transform(this.details.unit_cost, 2, 4, addThousandSeparators);
		this.details.distribution_price = DecimalPipe.transform(this.details.distribution_price, 2, 4, addThousandSeparators);
		this.details.reseller_price = DecimalPipe.transform(this.details.reseller_price, 2, 4, addThousandSeparators);
		this.details.trade_price = DecimalPipe.transform(this.details.trade_price, 2, 4, addThousandSeparators);
		this.details.retail_price = DecimalPipe.transform(this.details.retail_price, 2, 4, addThousandSeparators);

		this.showIsSeparableColumn = false;
		this.details.bom.forEach(item => {
			const unit = Mangler.findOne(item.info.units, { id: item.unit_id });
			item.info.canBeSeparable = false;
			if (unit) {
				const decimalPlaces = parseInt(unit.decimal_places, 10) || 0;
				item.quantity = DecimalPipe.transform(item.quantity, decimalPlaces, decimalPlaces, addThousandSeparators);
				if (decimalPlaces === 0 && item.info.is_placeholder) {
					this.showIsSeparableColumn = true;
					item.info.canBeSeparable = true;
				}
			}
		});

		this.details.labour.forEach(item => {
			item.labour_hours = DecimalPipe.transform(item.labour_hours, 2, 2, addThousandSeparators);
		});

		this.details.subscription.forEach(item => {
			item.quantity = DecimalPipe.transform(item.quantity, 2, 2, addThousandSeparators);
		});

		this.recalculatePricing('', addThousandSeparators);
	}

	recalculatePricing(dontReformat = '', addThousandSeparators: boolean = true) {
		// Calculate BOM costs
		this.details.bom.forEach(item => {
			const unit = Mangler.findOne(item.info.units, { id: item.unit_id });
			item.cost = 0;
			if (unit) {
				item.cost = DecimalPipe.parse(item.info.unit_cost) * DecimalPipe.parse(item.quantity) * DecimalPipe.parse(unit.units);
			}
		});

		// Calculate product cost
		let cost = DecimalPipe.parse(this.details.unit_cost);

		// Recalculate cost for product owners only
		if (this.editable) {
			if (this.hasBOM) {
				cost = 0;
				this.details.bom.forEach(item => cost += item.cost);
			} else if (this.isPlaceholder) {
				cost = 0;
				this.details.placeholders.forEach(item => {
					const itemCost = DecimalPipe.parse(item.unit_cost);
					if (cost < itemCost) cost = itemCost;
				});
			}
		}

		// Calculate prices
		['distribution', 'reseller', 'trade', 'retail'].forEach(tier => {
			const method = this.selectedPricingStructure[tier + '_method'];
			const value = DecimalPipe.parse(this.selectedPricingStructure[tier + '_value']);
			const round = this.selectedPricingStructure[tier + '_round'];
			const nearest = DecimalPipe.parse(this.selectedPricingStructure[tier + '_round_to_nearest']);
			const minimum = DecimalPipe.parse(this.selectedPricingStructure[tier + '_minimum_price']);

			let price = 0;

			switch (method) {
				case 'custom':
					// Leave it as-is
					return;

				case 'recommended':
					if (!this.editable) {
						price = this.details.recommended_price[tier + '_price'];
					} if (this.hasBOM) {
						this.details.bom.forEach(item => {
							const unit = Mangler.findOne(item.info.units, { id: item.unit_id });
							if (unit) price += DecimalPipe.parse(item.info[tier + '_price']) * DecimalPipe.parse(item.quantity) * DecimalPipe.parse(unit.units);
						});
					}
					break;

				case 'markup':
					price = cost * (1 + value / 100);
					break;

				case 'margin':
					if (value >= 100) {
						price = 0;
					} else {
						price = cost / (1 - (value / 100));
					}
					break;

				case 'profit':
					price = cost + value;
					break;
			}

			if (round) {
				if (nearest > 0) {
					price /= nearest;
					switch (round) {
						case 'round': price = Math.round(price); break;
						case 'floor': price = Math.floor(price); break;
						case 'ceiling': price = Math.ceil(price); break;
					}
					price *= nearest;
				}
			}

			if (method !== 'custom' && method !== 'recommended') price = Math.max(price, minimum);

			this.details[tier + '_price'] = DecimalPipe.transform(price, 2, 4, addThousandSeparators);
		});

		if (dontReformat !== 'cost') this.details.unit_cost = DecimalPipe.transform(cost, 2, 4, addThousandSeparators);
	}

	getLabourCost(labour) {
		const labourType = this.labourIndex[labour.labour_type_id];
		if (!labourType) return 0;

		const hours = DecimalPipe.parse(labour.labour_hours);
		const cost = DecimalPipe.parse(labourType.hourly_cost);
		return hours * cost;
	}

	getLabourPrice(labour) {
		const labourType = this.labourIndex[labour.labour_type_id];
		if (!labourType) return 0;

		const hours = DecimalPipe.parse(labour.labour_hours);
		const price = DecimalPipe.parse(labourType.hourly_price);
		return hours * price;
	}

	addLabour() {
		this.details.labour.push({
			id: 'new',
			labour_type_id: null,
			labour_hours: 1,
			editable: true
		});
		this.details.labour = this.details.labour.slice();
		this.formatNumbers();
	}

	deleteLabour(labour) {
		const i = this.details.labour.indexOf(labour);

		if (i !== -1) {
			this.details.labour.splice(i, 1);
			this.details.labour = this.details.labour.slice();

			if (labour.id !== 'new') {
				if (!Mangler.isArray(this.details.labour_deleted)) this.details.labour_deleted = [];
				this.details.labour_deleted.push(labour);
			}
		}
	}

	getSubscriptionCost(subscription) {
		const subscriptionType = this.subscriptionIndex[subscription.subscription_type_id];
		if (!subscriptionType) return 0;

		const quantity = DecimalPipe.parse(subscription.quantity);
		const cost = DecimalPipe.parse(subscriptionType.unit_cost);
		return quantity * cost;
	}

	getSubscriptionPrice(subscription) {
		const subscriptionType = this.subscriptionIndex[subscription.subscription_type_id];
		if (!subscriptionType) return 0;

		const quantity = DecimalPipe.parse(subscription.quantity);
		const price = DecimalPipe.parse(subscriptionType.unit_price);
		return quantity * price;
	}

	addSubscription() {
		this.details.subscription.push({
			id: 'new',
			subscription_type_id: null,
			quantity: 1,
			selection: 'fixed',
			editable: true
		});
		this.details.subscription = this.details.subscription.slice();
		this.formatNumbers();
	}

	deleteSubscription(subscription) {
		const i = this.details.subscription.indexOf(subscription);

		if (i !== -1) {
			this.details.subscription.splice(i, 1);
			this.details.subscription = this.details.subscription.slice();

			if (subscription.id !== 'new') {
				if (!Mangler.isArray(this.details.subscription_deleted)) this.details.subscription_deleted = [];
				this.details.subscription_deleted.push(subscription);
			}
		}
	}

	addBomProduct() {
		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.owner
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const product = event.data;

				if (product.id === this.details.id) {
					this.app.notifications.showDanger('Product cannot be put in its own bill of materials.');
					return;
				}

				this.disabled = true;
				this.api.products.getBomProduct(this.id, this.owner, product.id, response => {
					this.disabled = false;
					const bomItem = response.data;

					this.highlightedBom = bomItem;
					this.details.bom.push(bomItem);
					this.details.bom = this.details.bom.slice();
					this.formatNumbers();
					this.refreshTabs();
				}, response => {
					this.disabled = false;
					this.app.notifications.showDanger(response.message);
				});
			}
		});
	}

	deleteBomProduct(item) {
		const i = this.details.bom.indexOf(item);
		if (i !== -1) {
			const removedItem = this.details.bom.splice(i, 1)[0];
			if (removedItem && removedItem.id !== 'new') {
				if (!this.details.bom_deleted) this.details.bom_deleted = [];
				this.details.bom_deleted.push(removedItem);
			}
			this.details.bom = this.details.bom.slice();
			this.formatNumbers();
			this.refreshTabs();
		}
	}

	addAlternativeProduct() {
		if (!this.details.unit_id) {
			this.app.notifications.showDanger('Select this product\'s unit of measure first.');
			return;
		}

		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.owner,
			unit: this.details.unit_id,
			is_placeholder: 0,
			is_bundle: 0
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const product = event.data;
				const found = Mangler.findOne(this.details.alternatives, { id: event.data.id });

				if (found) {
					this.highlightedAlternative = found;
					this.app.notifications.showDanger('Product is already an alternative.');
					return;
				}

				if (product.id === this.details.id) {
					this.app.notifications.showDanger('Product cannot be its own alternative.');
					return;
				}

				const alt = {
					id: product.id,
					sku: product.sku,
					manufacturer_name: product.manufacturer_name,
					model: product.model,
					image_url: product.image_url,
					unit_id: product.unit_id,
					unit_cost: product.unit_cost,
					relationship: '0'
				};

				this.highlightedAlternative = alt;
				this.details.alternatives.push(alt);
				this.details.alternatives = this.details.alternatives.slice();

				this.refreshTabs();
			}
		});
	}

	deleteAlternativeProduct(item) {
		const i = this.details.alternatives.indexOf(item);
		if (i !== -1) {
			this.details.alternatives.splice(i, 1);
			this.details.alternatives = this.details.alternatives.slice();
			this.refreshTabs();
		}
	}

	addAccessory() {
		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.owner,
			is_placeholder: 0,
			is_bundle: 0
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const product = event.data;
				const found = Mangler.findOne(this.details.accessories, { id: event.data.id });

				if (found) {
					this.highlightedAccessory = found;
					this.app.notifications.showDanger('Product is already an accessory.');
					return;
				}

				if (product.id === this.details.id) {
					this.app.notifications.showDanger('Product cannot be its own accessory.');
					return;
				}

				const accessory = {
					id: product.id,
					sku: product.sku,
					manufacturer_name: product.manufacturer_name,
					model: product.model,
					image_url: product.image_url,
					system_id: null,
					default_quantity: 0
				};

				this.highlightedAccessory = accessory;
				this.details.accessories.push(accessory);
				this.details.accessories = this.details.accessories.slice();

				this.refreshTabs();
			}
		});
	}

	deleteAccessory(item) {
		const i = this.details.accessories.indexOf(item);
		if (i !== -1) {
			this.details.accessories.splice(i, 1);
			this.details.accessories = this.details.accessories.slice();
			this.refreshTabs();
		}
	}

	addPlaceholderProduct() {
		if (!this.details.unit_id) {
			this.app.notifications.showDanger('Select this product\'s unit of measure first.');
			return;
		}

		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.owner,
			unit: this.details.unit_id,
			is_placeholder: 0,
			is_bundle: 0
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const product = event.data;
				const found = Mangler.findOne(this.details.placeholders, { id: event.data.id });

				if (found) {
					this.highlightedPlaceholder = found;
					this.app.notifications.showDanger('Product is already a placeholder item.');
					return;
				}

				if (product.id === this.details.id) {
					this.app.notifications.showDanger('Product cannot be its own placeholder.');
					return;
				}

				const alt = {
					id: product.id,
					sku: product.sku,
					manufacturer_name: product.manufacturer_name,
					model: product.model,
					image_url: product.image_url,
					unit_id: product.unit_id,
					unit_cost: product.unit_cost
				};

				this.highlightedPlaceholder = alt;
				this.details.placeholders.push(alt);
				this.details.placeholders = this.details.placeholders.slice();

				this.recalculatePricing();
				this.refreshTabs();
			}
		});
	}

	deletePlaceholderProduct(item) {
		const i = this.details.placeholders.indexOf(item);
		if (i !== -1) {
			this.details.placeholders.splice(i, 1);
			this.details.placeholders = this.details.placeholders.slice();
			this.recalculatePricing();
			this.refreshTabs();
		}
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.formatNumbers(false);
		const productDetails = Mangler.clone(this.details);
		productDetails.bundle = this.bundle.getBundleData();
		this.formatNumbers();

		this.disabled = true;
		this.api.products.saveProduct(this.owner, productDetails, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Product created.');
			} else {
				this.app.notifications.showSuccess('Product updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	fileDragOver(ev) {
		this.draggedOver = true;
		ev.preventDefault();
	}

	fileDrop(ev) {
		this.draggedOver = false;
		ev.preventDefault();

		// If dropped items aren't files, reject them
		const dt = ev.dataTransfer;
		let file = null;
		if (dt.items) {
			// Use DataTransferItemList interface to access the file(s)
			if (dt.items.length) file = dt.items[0].getAsFile();
		} else {
			// Use DataTransfer interface to access the file(s)
			if (dt.files.length) file = dt.files[0];
		}

		if (file) {
			this.disabled = true;
			this.uploadFile(file, uc => {
				this.disabled = false;
				this.details.image_id = uc.id;
				this.imageUrl = uc.url;
			}, error => {
				this.disabled = false;
				this.app.notifications.showDanger(error);
			});
		}
	}

	changeImage() {
		$(this.fileInput.nativeElement).val('').click();
	}

	removeImage() {
		this.details.image_id = null;
		this.imageUrl = null;
	}

	addImageURL() {
		const url = prompt('Enter image URL:');
		if (url) {
			this.disabled = true;
			this.uploadFileByURL(url, uc => {
				this.disabled = false;
				this.details.image_id = uc.id;
				this.imageUrl = uc.url;
			}, error => {
				this.disabled = false;
				this.app.notifications.showDanger(error);
			});
		}
	}

	uploadFile(file, success, failure) {
		const formData = new FormData();
		formData.append('userfile', file);

		this.api.general.uploadImage(formData, 512, 512, res => {
			try {
				const resFile = res.data.files[0];
				const uc = {
					id: resFile.id,
					url: resFile.url
				};
				success(uc);
			} catch (ex) {
				failure('No file uploaded.');
			}
		}, () => {
			failure('No file uploaded.');
		});
	}

	uploadFileByURL(url, success, failure) {
		this.api.general.uploadImageURL(url, 512, 512, res => {
			try {
				const resFile = res.data.files[0];
				const uc = {
					id: resFile.id,
					url: resFile.url
				};
				success(uc);
			} catch (ex) {
				failure('Invalid image URL.');
			}
		}, () => {
			failure('Invalid image URL.');
		});
	}

	uploadUserContent(fileElement, success, failure) {
		if (!fileElement) {
			failure('No file uploaded.');
			return;
		}

		const fileBrowser = fileElement.nativeElement;
		if (fileBrowser.files && fileBrowser.files[0]) {
			this.uploadFile(fileBrowser.files[0], success, failure);
		} else {
			failure('No file uploaded.');
			return;
		}
	}

	uploadImage() {
		this.disabled = true;
		this.uploadUserContent(this.fileInput, uc => {
			this.disabled = false;
			this.details.image_id = uc.id;
			this.imageUrl = uc.url;
		}, error => {
			this.disabled = false;
			this.app.notifications.showDanger(error);
		});
	}

	cloneProduct() {
		if (this.id === 'new' || !this.editable) return;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				this.formatNumbers(false);
				const productDetails = Mangler.clone(this.details);
				this.formatNumbers();

				this.disabled = true;
				this.api.products.saveProduct(this.owner, productDetails, () => {
					this.api.products.cloneProduct(event.data, response => {
						this.disabled = false;
						this.app.notifications.showSuccess('Product cloned.');
						this.router.navigate(['../..', response.data, this.owner], { replaceUrl: true, relativeTo: this.route });
					}, cloneResponse => {
						this.disabled = false;
						this.app.notifications.showDanger(cloneResponse.message);
					});
				}, response => {
					this.disabled = false;
					this.app.notifications.showDanger(response.message);
				});
			}
		});

		const cloneOptions = {
			accessories: !this.isPlaceholder && this.details.accessories.length,
			alternatives: !this.isPlaceholder && !this.isBundle && this.isStocked && this.details.alternatives.length,
			bom: this.hasBOM && this.details.bom.length,
			placeholders: this.isPlaceholder && this.details.placeholders.length,
			labour: !!this.details.labour.length,
			subscription: !!this.details.subscription.length,
			warehouses: !!this.details.warehouses.length,
			bundle: this.isBundle
		};

		this.app.modal.open(StockProductCloneModalComponent, this.moduleRef, {
			id: this.id,
			sku: this.details.sku || '',
			model: this.details.model || '',
			short_description: this.details.short_description || '',
			long_description: this.details.long_description || '',
			clone: cloneOptions,
			allowed: Mangler.clone(cloneOptions)
		});
	}

	barcodeImageURL(code) {
		return this.barcodeURL + encodeURIComponent(code);
	}

	addSupplier(record) {
		this.details.suppliers.push({
			id: record.id,
			name: record.name,
			posttown: record.posttown,
			postcode: record.postcode,
			is_primary: !this.details.suppliers.length
		});

		this.details.suppliers = this.details.suppliers.slice();
	}

	removeSupplier(id) {
		const item = Mangler.findOne(this.details.suppliers, { id });

		if (item) {
			const i = this.details.suppliers.indexOf(item);

			if (i !== -1) {
				this.details.suppliers.splice(i, 1);
				this.details.suppliers = this.details.suppliers.slice();

				if (this.details.suppliers.length && !Mangler.findOne(this.details.suppliers, { is_primary: true })) {
					this.setPrimarySupplier(this.details.suppliers[0].id);
				}
			}
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

	setPrimarySupplier(id) {
		this.details.suppliers.forEach(s => {
			s.is_primary = (s.id === id);
		});
	}

	refreshManufacturerSuppliers(override = false) {
		if (!this.editable) return;

		this.manufacturerSuppliers = [];

		if (this.details.manufacturer_id) {
			this.api.products.getEntity(this.details.manufacturer_id, response => {
				if (this.details.manufacturer_id === response.data.details.id) {
					this.manufacturerSuppliers = response.data.details.suppliers || [];
					if (override) this.addManufacturerSuppliers();
				}
			}, () => {
				this.manufacturerSuppliers = [];
			});
		} else {
			if (override) this.addManufacturerSuppliers();
		}
	}

	addManufacturerSuppliers(hardReset = false) {
		if (!this.editable) return;

		if (hardReset) {
			if (!confirm('Are you sure you want to reset all suppliers for this product?')) return;

			// Clear all current suppliers
			this.details.suppliers = [];
		} else {
			// Only leave suppliers with customised SKU
			const customised = [];
			this.details.suppliers.forEach(s => {
				s.is_primary = false;
				if (s.sku) customised.push(s);
			});
			this.details.suppliers = customised;
		}

		// Add manufacturer's suppliers
		this.manufacturerSuppliers.forEach(s => {
			let item = Mangler.findOne(this.details.suppliers, { id: s.id });

			if (item) {
				// Already in the list
				if (s.is_primary) item.is_primary = true;
			} else {
				// Add
				item = Mangler.clone(s);
				item.sku = null;
				this.details.suppliers.push(item);
			}
		});
	}

	bundleAddBaseProduct() {
		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.owner,
			is_placeholder: 0,
			is_bundle: 0
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				const product = event.data;
				const found = Mangler.findOne(this.bundle.products, { product_id: event.data.id });

				if (found) {
					this.app.notifications.showDanger('Product is already a base product.');
					return;
				}

				if (product.id === this.details.id) {
					this.app.notifications.showDanger('Product cannot be its own base products.');
					return;
				}

				this.bundle.addProduct({
					product_id: event.data.id,
					model: event.data.model,
					short_description: event.data.short_description,
					manufacturer_name: event.data.manufacturer_name,
					image_url: event.data.image_url,
					quantity: 1
				});
			}
		});
	}

	bundleEditCounter(counter = null) {
		this.app.modal.open(StockBundleCounterEditModalComponent, this.moduleRef, {
			owner: this.owner,
			bundle: this.bundle,
			counter: counter ? Mangler.clone(counter) : this.bundle.getNewCounterData()
		});
	}

	bundleEditQuestion(parent = null, question = null) {
		// Remove recursive structure for cloning
		this.bundle.questions.forEach(q => delete q.children);

		this.app.modal.open(StockBundleQuestionEditModalComponent, this.moduleRef, {
			owner: this.owner,
			bundle: this.bundle,
			question: question ? Mangler.clone(question) : this.bundle.getNewQuestionData(parent)
		});

		// Restore structure
		this.bundle.refreshStructure();
	}

	bundleQuestionDefaultValue(q) {
		switch (q.type) {
			case 'numeric':
				return q.default_value;

			case 'checkbox':
				return q.default_value ? 'checked' : 'unchecked';

			case 'select':
			case 'multi-select':
				const list = Mangler.find(q.select_options, { $where: o => !!(q.default_value & o.value) });
				const valueList = list.map(o => '' + o.description);
				return valueList.join(', ');
		}
	}

	bundleQuestionCondition(q) {
		if (!q.parent) return '';

		const type = q.parent.type;
		const mode = q.parent_mode;
		const value = q.parent_value || 0;
		const maxValue = q.parent_max_value || 0;
		const field = 'Parent';

		let valueDescription = '';
		let valueList = [];
		let list = [];

		// Types: numeric, select, multi-select, checkbox
		// Modes: set, value, range, lt, gt, all, any

		switch (type) {
			case 'numeric':
				switch (mode) {
					case 'set': return field + ' is not 0';
					case 'value': return field + ' = ' + value;
					case 'range': return field + ' between ' + value + ' and ' + maxValue;
					case 'lt': return field + ' < ' + value;
					case 'gt': return field + ' > ' + value;

					default: return '';
				}

			case 'select':
				list = Mangler.find(q.parent.select_options, { $where: o => !!(value & o.value) });
				valueList = list.map(o => '' + o.description);
				valueDescription = valueList.join(', ');

				switch (mode) {
					case 'set': return field + ' is set';
					case 'value': return field + ' is ' + valueDescription;
					case 'any': return field + ' is one of ' + valueDescription;

					default: return '';
				}

			case 'multi-select':
				list = Mangler.find(q.parent.select_options, { $where: o => !!(value & o.value) });
				valueList = list.map(o => '' + o.description);
				valueDescription = valueList.join(', ');

				switch (mode) {
					case 'set': return field + ' is set';
					case 'value': return field + ' is exactly ' + valueDescription;
					case 'any': return field + ' has any of ' + valueDescription;
					case 'all': return field + ' has all of ' + valueDescription;

					default: return '';
				}

			case 'checkbox':
				switch (mode) {
					case 'set': return field + ' is checked';
					case 'value': return field + ' is ' + (value ? 'not checked' : 'checked');

					default: return '';
				}

			default:
				return '';
		}
	}

}
