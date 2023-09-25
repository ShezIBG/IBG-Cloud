import { BundleOptions } from './../../shared/bundle-options';
import { DecimalPipe } from './../../shared/decimal.pipe';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-line-edit-modal',
	templateUrl: './sales-project-line-edit-modal.component.html',
	styleUrls: ['./sales-project-line-edit-modal.component.less']
})
export class SalesProjectLineEditModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	get showAccessories() { return !!this.data.details.show_accessories; }
	set showAccessories(value) { this.data.details.show_accessories = value ? 1 : 0; }

	buttons = [];

	data;
	editable = false;
	accessory = false;
	labourIndex = [];
	subscriptionIndex = [];
	subscriptionSetupIndex = [];

	subscriptionFixedList = [];
	subscriptionOptionalList = [];
	subscriptionSelectList = [];
	subscriptionSelected = null;

	bundle: BundleOptions = null;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		const processData = () => {
			this.labourIndex = Mangler.index(this.data.labour_types, 'id');
			this.subscriptionIndex = Mangler.index(this.data.subscription_types, 'id');
			this.subscriptionSetupIndex = Mangler.index(this.data.subscription_setup, 'id');

			let cIndex, unassigned;

			// Put labour types into categories
			this.data.labour_categories.forEach(c => c.types = []);
			cIndex = Mangler.index(this.data.labour_categories, 'id');
			unassigned = { id: 0, description: 'Unassigned', types: [] };
			cIndex[0] = unassigned;
			this.data.labour_types.forEach(t => {
				const c = cIndex[t.category_id || 0] || cIndex[0];
				c.types.push(t);
			});
			if (unassigned.types.length) this.data.labour_categories.push(unassigned);

			// Put subscription types into categories
			this.data.subscription_categories.forEach(c => c.types = []);
			cIndex = Mangler.index(this.data.subscription_categories, 'id');
			unassigned = { id: 0, description: 'Unassigned', types: [] };
			cIndex[0] = unassigned;
			this.data.subscription_types.forEach(t => {
				const c = cIndex[t.category_id || 0] || cIndex[0];
				c.types.push(t);
			});
			if (unassigned.types.length) this.data.subscription_categories.push(unassigned);

			// Put systems into modules
			this.data.modules.forEach(m => m.systems = []);
			cIndex = Mangler.index(this.data.modules, 'id');
			unassigned = { id: 0, description: 'Unassigned', systems: [] };
			cIndex[0] = unassigned;
			this.data.systems.forEach(s => {
				const m = cIndex[s.module_id || 0] || cIndex[0];
				m.systems.push(s);
			});
			if (unassigned.systems.length) this.data.modules.push(unassigned);

			// Remove empty modules
			Mangler.filter(this.data.modules, { $not: { systems: { $size: 0 } } });

			// Set editable flag
			this.editable = this.data.project.stage === 'lead' || this.data.project.stage === 'survey';
			this.accessory = this.data.details.parent_id !== null;

			// Process subscriptions
			if (this.editable) {
				// Prepare setup records
				this.data.subscription_setup.forEach(setup => {
					setup.matched = null;
					switch (setup.selection) {
						case 'fixed':
							this.subscriptionFixedList.push(setup);
							break;
						case 'optional':
							this.subscriptionOptionalList.push(setup);
							break;
						case 'select':
							this.subscriptionSelectList.push(setup);
							break;
					}
				});

				// First, match added subscriptions with the product setup
				this.data.details.subscription.forEach(sub => {
					if (sub.product_subscription_id !== null) {
						// This entry references a specific setup record, check
						const setup = this.subscriptionSetupIndex[sub.product_subscription_id];
						if (setup) {
							switch (setup.selection) {
								case 'fixed':
								case 'optional':
									if (!setup.matched) {
										// Match this sub to setup
										setup.matched = sub;
										sub.quantity = setup.quantity;
									} else {
										// This rule was already matched, break reference
										sub.product_subscription_id = null;
									}
									break;

								case 'select':
									if (this.subscriptionSelected) {
										// A subscription was already selected, break reference
										sub.product_subscription_id = null;
									} else {
										this.subscriptionSelected = setup;
										setup.matched = sub;
										sub.quantity = setup.quantity;
									}
									break;

								default:
									// Invalid record, break reference
									sub.product_subscription_id = null;
									break;
							}
						} else {
							// The referenced setup record was not found, remove reference
							// This makes the record an additional subscription
							sub.product_subscription_id = null;
						}
					}
				});

				// Create all unmatched fixed subscription records
				this.data.subscription_setup.forEach(setup => {
					if (setup.selection === 'fixed' && !setup.matched) {
						this.setSubscriptionSetup(setup, true);
					}

					// Add all optional subscriptions if it's a new record
					if (this.modalService.data.id === 'new' && setup.selection === 'optional' && !setup.matched) {
						this.setSubscriptionSetup(setup, true);
					}
				});
			}

			this.bundle = null;
			if (this.data.bundle) {
				this.bundle = new BundleOptions(this.data.bundle);
			}

			this.formatNumbers();
		}

		if (this.modalService.data.id === 'new') {
			this.api.sales.newProjectLine(this.modalService.data, response => {
				this.data = response.data;
				processData();

				this.buttons = [this.editable ? '0|Cancel' : '0|Close'];
				if (this.editable) this.buttons.push('1|*Add product');
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		} else {
			this.api.sales.getProjectLine(this.modalService.data.id, response => {
				this.data = response.data;
				processData();

				this.buttons = [this.editable ? '0|Cancel' : '0|Close'];
				if (this.editable) {
					this.buttons.push('1|*Save');
					this.buttons.push('2|<!Delete');
				}
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	modalHandler(event) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					this.formatNumbers(false);
					const projectDetails = Mangler.clone(this.data.details);
					this.formatNumbers();

					if (this.bundle) {
						projectDetails.bundle_answers = this.bundle.getAnswerData();
						projectDetails.bundle_products = this.bundle.resolveProducts();
					}

					this.api.sales.saveProjectLine(projectDetails, response => {
						this.app.notifications.showSuccess('Line updated.');
						this.modal.close(response.data);
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				case 2:
					this.api.sales.deleteProjectLine(this.data.details.id, () => {
						this.app.notifications.showSuccess('Line deleted.');
						this.modal.close();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				default:
					this.modal.close();
					break;
			}
		} else {
			this.modal.close();
		}
	}

	increaseQuantity(value) {
		const quantity = DecimalPipe.parse(this.data.details.quantity);
		this.data.details.quantity = quantity + value;
		this.formatNumbers();
	}

	increaseAccessoryQuantity(item, value) {
		const quantity = DecimalPipe.parse(item.quantity);
		item.quantity = Math.max(quantity + value, 0);
		this.formatNumbers();
	}

	formatNumbers(addThousandSeparators: boolean = true) {
		this.data.details.quantity = DecimalPipe.transform(this.data.details.quantity, 0, this.data.details.unit_decimal_places, addThousandSeparators);

		this.data.details.labour.forEach(item => {
			item.labour_hours = DecimalPipe.transform(item.labour_hours, 2, 2, addThousandSeparators);
		});

		this.data.details.subscription.forEach(item => {
			item.quantity = DecimalPipe.transform(item.quantity, 2, 2, addThousandSeparators);
		});

		this.data.details.accessories.forEach(item => {
			item.quantity = DecimalPipe.transform(item.quantity, 0, item.unit_decimal_places, addThousandSeparators);
		});

		// If line has no product, format cost and price
		if (this.data.details.product_id === null) {
			this.data.details.unit_cost = DecimalPipe.transform(this.data.details.unit_cost, 2, 2, addThousandSeparators);
			this.data.details.unit_price = DecimalPipe.transform(this.data.details.unit_price, 2, 2, addThousandSeparators);
		}
	}

	getLabourDescription(labour) {
		const labourType = this.labourIndex[labour.labour_type_id];
		return labourType ? labourType.description || '' : '';
	}

	getLabourCost(labour) {
		const hours = DecimalPipe.parse(labour.labour_hours);
		const cost = DecimalPipe.parse(labour.hourly_cost);
		return hours * cost;
	}

	getLabourPrice(labour) {
		const hours = DecimalPipe.parse(labour.labour_hours);
		const price = DecimalPipe.parse(labour.hourly_price);
		return hours * price;
	}

	addLabour() {
		this.data.details.labour.push({
			id: 'new',
			labour_type_id: null,
			labour_hours: 1,
			hourly_cost: 0,
			hourly_price: 0,
			product_labour_id: null
		});
		this.data.details.labour = this.data.details.labour.slice();
		this.formatNumbers();
	}

	deleteLabour(labour) {
		if (labour.product_labour_id !== null) return;
		const i = this.data.details.labour.indexOf(labour);

		if (i !== -1) {
			this.data.details.labour.splice(i, 1);
			this.data.details.labour = this.data.details.labour.slice();

			if (labour.id !== 'new') {
				if (!Mangler.isArray(this.data.details.labour_deleted)) this.data.details.labour_deleted = [];
				this.data.details.labour_deleted.push(labour);
			}
		}
	}

	updateLabour(labour) {
		const labourType = this.labourIndex[labour.labour_type_id];
		labour.hourly_cost = labourType ? labourType.hourly_cost || 0 : 0;
		labour.hourly_price = labourType ? labourType.hourly_price || 0 : 0;
	}

	getSubscriptionDescription(subscription) {
		const subscriptionType = this.subscriptionIndex[subscription.subscription_type_id];
		return subscriptionType ? subscriptionType.description || '' : '';
	}

	getSubscriptionCost(subscription) {
		const quantity = DecimalPipe.parse(subscription.quantity);
		const cost = DecimalPipe.parse(subscription.unit_cost);
		return quantity * cost;
	}

	getSubscriptionPrice(subscription) {
		const quantity = DecimalPipe.parse(subscription.quantity);
		const price = DecimalPipe.parse(subscription.unit_price);
		return quantity * price;
	}

	addSubscription() {
		this.data.details.subscription.push({
			id: 'new',
			subscription_type_id: null,
			quantity: 1,
			unit_cost: 0,
			unit_price: 0,
			frequency: '',
			product_subscription_id: null
		});
		this.data.details.subscription = this.data.details.subscription.slice();
		this.formatNumbers();
	}

	deleteSubscription(subscription) {
		const i = this.data.details.subscription.indexOf(subscription);

		if (i !== -1) {
			this.data.details.subscription.splice(i, 1);
			this.data.details.subscription = this.data.details.subscription.slice();

			if (subscription.id !== 'new') {
				if (!Mangler.isArray(this.data.details.subscription_deleted)) this.data.details.subscription_deleted = [];
				this.data.details.subscription_deleted.push(subscription);
			}
		}
	}

	updateSubscription(subscription) {
		const subscriptionType = this.subscriptionIndex[subscription.subscription_type_id];
		subscription.unit_cost = subscriptionType ? subscriptionType.unit_cost || 0 : 0;
		subscription.unit_price = subscriptionType ? subscriptionType.unit_price || 0 : 0;
		subscription.frequency = subscriptionType ? subscriptionType.frequency : '';
	}

	setSubscriptionSetup(setup, enabled) {
		if (!enabled) {
			if (setup.matched) {
				this.deleteSubscription(setup.matched);
				setup.matched = null;
			}
		} else {
			if (!setup.matched) {
				const type = this.subscriptionIndex[setup.subscription_type_id];

				if (type) {
					setup.matched = {
						id: 'new',
						subscription_type_id: type.id,
						quantity: setup.quantity,
						unit_cost: type.unit_cost,
						unit_price: type.unit_price,
						frequency: type.frequency,
						product_subscription_id: setup.id
					};

					this.data.details.subscription.push(setup.matched);
				}
			}
		}
	}

	updateSubscriptionSelection() {
		this.subscriptionSelectList.forEach(setup => {
			if (setup.matched) this.setSubscriptionSetup(setup, false);
		});
		if (this.subscriptionSelected) this.setSubscriptionSetup(this.subscriptionSelected, true);
	}

	getSlotProductDescription(product) {
		const desc = [];
		if (product.sku) desc.push(product.sku);
		if (product.model) desc.push(product.model);
		return desc.join(' - ');
	}

	getLineSystemDescription() {
		const system = Mangler.findOne(this.data.systems, { id: this.data.details.system_id });
		return system ? system.description : '';
	}

	increaseBundleAnswer(question, value) {
		question.answer += value;
		this.bundle.refreshAnswers();
	}

	bundleSingleAnswerClick(question, value) {
		question.answer = question.answer === value ? 0 : value;
		this.bundle.refreshAnswers();
	}

	bundleMultiAnswerClick(question, value) {
		question.answer = this.toggleFlag(question.answer, value);
		this.bundle.refreshAnswers();
	}

	getFlag(value, flag) {
		return !!(value & flag);
	}

	setFlag(value, flag) {
		return value | flag;
	}

	unsetFlag(value, flag) {
		if (this.getFlag(value, flag)) return value - flag;
	}

	toggleFlag(value, flag) {
		if (this.getFlag(value, flag)) {
			return this.unsetFlag(value, flag);
		} else {
			return this.setFlag(value, flag);
		}
	}

}
