import { ModalService } from './../../shared/modal/modal.service';
import { BundleOptions } from './../../shared/bundle-options';
import { Pagination } from './../../shared/pagination';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';
import { AppService } from 'app/app.service';
import { ApiService } from 'app/api.service';

declare var Mangler: any;
declare var $: any;

@Component({
	selector: 'app-stock-bundle-question-edit-modal',
	templateUrl: './stock-bundle-question-edit-modal.component.html',
	styleUrls: ['./stock-bundle-question-edit-modal.component.less']
})
export class StockBundleQuestionEditModalComponent implements OnInit {

	@ViewChild('modalFileInput') fileInput;
	@ViewChild(ModalComponent) modal: ModalComponent;

	tabs: any[] = [
		{ id: 'details', description: 'Question details' },
		{ id: 'products', description: 'Add product' },
		{ id: 'image', description: 'Image from product' },
	];
	selectedTab = 'details';
	title = '';
	buttons = ['0|Cancel', '1|*Save'];

	listProducts: any[] = [];
	search = '';
	pagination = new Pagination();

	owner;
	bundle: BundleOptions;
	question: any;

	selectedProduct = null;
	newVersion = false;

	draggedOver = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.owner = this.modalService.data.owner;
		this.bundle = this.modalService.data.bundle;
		this.question = this.modalService.data.question;
		this.title = this.question.question;
	}

	selectTab(id) {
		this.selectedTab = id;

		if (id === 'products' || id === 'image') {
			this.api.products.listProducts({
				product_owner: this.owner,
				is_placeholder: 0,
				is_bundle: 0
			}, response => {
				this.listProducts = response.data.list;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	hasProduct(id) {
		return !!Mangler.findOne(this.question.products, { product_id: id });
	}

	addProduct(item) {
		if (!this.hasProduct(item.id)) {
			this.question.products.push(this.bundle.getNewQuestionProductData(this.question, item));
			this.question.products = this.question.products.slice();
		}
	}

	modalHandler(event) {
		if (event.data && event.data.id === 1) {
			if (this.question.question_id) {
				this.bundle.updateQuestion(this.question);
				if (this.newVersion) this.bundle.requireNewVersion();
			} else {
				this.bundle.addQuestion(this.question);
			}
		}

		this.modal.close();
	}

	formatProductNumbers(item) {
		item.quantity = parseFloat(item.quantity) || 0;
		item.question_value = parseInt(item.question_value, 10) || 0;
		item.question_max_value = parseInt(item.question_max_value, 10) || 0;
	}

	formatCounterNumbers(item) {
		item.value = parseInt(item.value, 10) || 0;
	}

	formatQuestionNumbers(item) {
		item.default_value = parseInt(item.default_value, 10) || 0;
		item.min_value = (item.min_value === '' || item.min_value === null) ? null : (parseInt(item.min_value, 10) || 0);
		item.max_value = (item.max_value === '' || item.max_value === null) ? null : (parseInt(item.max_value, 10) || 0);
		item.parent_value = parseInt(item.parent_value, 10) || 0;
		item.parent_max_value = parseInt(item.parent_max_value, 10) || 0;
	}

	removeProduct(item) {
		const i = this.question.products.indexOf(item);
		if (i !== -1) {
			this.question.products.splice(i, 1);
			this.question.products = this.question.products.slice();
			this.selectedProduct = null;
			this.newVersion = true;
		}
	}

	getQuestionMultiplyDescription(id) {
		if (id === null) return 'x 1';
		if (id === 0 || id === -1 || id === this.question.question_id) return 'x This answer';

		const q = Mangler.findOne(this.bundle.questions, { question_id: id });
		return q ? 'x ' + q.question : '';
	}

	getCondition(product) {
		const type = this.question.type;
		const mode = product.question_mode;
		const value = product.question_value || 0;
		const maxValue = product.question_max_value || 0;
		const field = 'Answer';

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
				list = Mangler.find(this.question.select_options, { $where: o => !!(value & o.value) });
				valueList = list.map(o => '' + o.description);
				valueDescription = valueList.join(', ');

				switch (mode) {
					case 'set': return field + ' is set';
					case 'value': return field + ' is ' + valueDescription;
					case 'any': return field + ' is one of ' + valueDescription;

					default: return '';
				}

			case 'multi-select':
				list = Mangler.find(this.question.select_options, { $where: o => !!(value & o.value) });
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

	questionTypeChanged() {
		this.question.default_value = 0;
		if (!this.question.new_question) this.newVersion = true;
	}

	deleteSelectOption(o) {
		const i = this.question.select_options.indexOf(o);
		if (i !== -1) {
			this.question.select_options.splice(i, 1);
			this.question.select_options = this.question.select_options.slice();
			this.newVersion = true;
		}
	}

	addSelectOption() {
		this.question.select_options.push(this.bundle.getNewQuestionSelectOptionData(this.question));
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

	optionsDrop(event) {
		// Update data model
		const previousIndex = event.previousIndex;
		const currentIndex = event.currentIndex;

		if (previousIndex === currentIndex) return; // No change

		const item = this.question.select_options.splice(previousIndex, 1)[0];
		this.question.select_options.splice(currentIndex, 0, item);

		// Update display orders
		let i = 0;
		this.question.select_options.forEach(o => o.display_order = ++i);
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
			this.uploadFile(file, uc => {
				this.question.image_id = uc.id;
				this.question.image_url = uc.url;
			}, error => {
				this.app.notifications.showDanger(error);
			});
		}
	}

	changeImage() {
		$(this.fileInput.nativeElement).val('').click();
	}

	removeImage() {
		this.question.image_id = null;
		this.question.image_url = null;
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
		this.uploadUserContent(this.fileInput, uc => {
			this.question.image_id = uc.id;
			this.question.image_url = uc.url;
		}, error => {
			this.app.notifications.showDanger(error);
		});
	}

	imageFromProduct(product) {
		this.question.image_id = product.image_id;
		this.question.image_url = product.image_url;
		this.selectTab('details');
	}

}
