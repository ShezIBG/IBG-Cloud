declare var Mangler: any;

export class BundleOptions {

	public record;

	public products;

	public questions;
	public question_products;
	public question_counters;
	public question_select_options;

	public counters;
	public counter_products;

	public rootQuestions = [];

	constructor(data: any) {
		this.record = data.record;
		this.products = data.products;
		this.questions = data.questions;
		this.question_products = data.question_products;
		this.question_counters = data.question_counters;
		this.question_select_options = data.question_select_options;
		this.counters = data.counters;
		this.counter_products = data.counter_products;

		// Add answers to questions (if any)

		const answers = data.answers || [];

		this.questions.forEach(q => q.answer = q.default_value);

		answers.forEach(a => {
			const q = Mangler.findOne(this.questions, { question_id: a.question_id });
			if (q) q.answer = a.answer;
		});

		this.refreshStructure();
		this.refreshAnswers();
	}

	getBundleData() {
		// Remove added fields and tree structures

		this.questions.forEach(q => {
			delete q.parent;
			delete q.children;
			delete q.select_options;
			delete q.products;
			delete q.counters;
			delete q.depth;
			delete q.visible;
			delete q.answer;
			delete q.first_child;
			delete q.last_child;
		});

		this.counters.forEach(c => {
			delete c.products;
			delete c.value;
		});

		this.question_counters.forEach(qc => {
			delete qc.counter;
		});

		const data = Mangler.clone({
			record: this.record,
			products: this.products,
			questions: this.questions,
			question_products: this.question_products,
			question_counters: this.question_counters,
			question_select_options: this.question_select_options,
			counters: this.counters,
			counter_products: this.counter_products
		});

		// Add back removed references
		this.refreshStructure();

		return data;
	}

	getAnswerData() {
		const result = [];

		this.refreshAnswers();
		this.questions.forEach(q => {
			if (q.visible) result.push({ question_id: q.question_id, answer: q.answer || 0 });
		});

		return result;
	}

	getNewProductData(productRecord, quantity = 1) {
		return {
			product_id: productRecord.id,
			model: productRecord.model,
			short_description: productRecord.short_description,
			manufacturer_name: productRecord.manufacturer_name,
			image_url: productRecord.image_url,
			quantity: quantity
		};
	}

	getNewCounterData() {
		return {
			counter_id: 0,
			description: 'New Accumulator',

			products: [],
			value: 0
		};
	}

	getNewCounterProductData(counter, productRecord, quantity = 1) {
		const product: any = this.getNewProductData(productRecord, quantity);
		return Mangler.merge(product, {
			counter_id: counter.counter_id,
			multiply_by_counter: 0,
			range_start: 0,
			range_end: 0
		});
	}

	getNewQuestionData(parent = null) {
		const counters = [];

		this.counters.forEach(c => {
			counters.push({
				question_id: 0,
				counter_id: c.counter_id,
				counter: c,
				value: 0,
				multiply_by_question_id: null
			});
		});

		return {
			question_id: 0,
			parent_id: parent ? parent.question_id : null,
			question: 'New Question',
			type: 'numeric',
			image_id: null,
			image_url: null,
			is_required: 0,
			default_value: 0,
			min_value: null,
			max_value: null,
			parent_mode: 'set',
			parent_value: null,
			parent_max_value: null,
			display_order: 0,

			children: [],
			select_options: [],
			products: [],
			counters: counters,
			parent: parent,
			depth: 0,
			visible: false,
			answer: 0,
			new_question: true,
			first_child: false,
			last_child: false
		};
	}

	getNewQuestionSelectOptionData(question) {
		let values = [];
		let maxDisplayOrder = 0;
		for (let i = 0; i < 32; i++) values.push(Math.pow(2, i));
		question.select_options.forEach(o => {
			values = Mangler.filter(values, { $ne: o.value });
			if (o.display_order > maxDisplayOrder) maxDisplayOrder = o.display_order;
		});

		if (!values.length) return null;

		return {
			question_id: question.question_id,
			value: values[0],
			description: '',
			display_order: maxDisplayOrder + 1
		};
	}

	getNewQuestionProductData(question, productRecord, quantity = 1) {
		const product: any = this.getNewProductData(productRecord, quantity);
		return Mangler.merge(product, {
			question_id: question.question_id,
			multiply_by_question_id: null,
			question_mode: 'set',
			question_value: null,
			question_max_value: null
		});
	}

	/** Sorts questions by tree order. Also removes dead islands and enforces data integrity. */
	refreshStructure() {
		const displayOrderSort = (a, b) => a.display_order - b.display_order;

		this.rootQuestions = [];
		const sorted = [];

		// Find root questions

		this.questions.forEach(q => {
			q.children = [];
			q.select_options = [];
			q.products = [];
			q.counters = [];
			q.parent = null;
			q.depth = 0;
			q.first_child = false;
			q.last_child = false;
			if (!q.parent_id) this.rootQuestions.push(q);
		});

		// Make sure there is a question counter record for every single counter

		this.questions.forEach(q => {
			this.counters.forEach(c => {
				const qc = Mangler.findOne(this.question_counters, { question_id: q.question_id, counter_id: c.counter_id });
				if (!qc) {
					this.question_counters.push({
						question_id: q.question_id,
						counter_id: c.counter_id,
						value: 0,
						multiply_by_question_id: null
					});
				}
			});
		});

		// Link select options

		this.question_select_options.forEach(o => {
			const q = Mangler.findOne(this.questions, { question_id: o.question_id });
			if (q) q.select_options.push(o);
		});

		// Link products

		this.question_products.forEach(p => {
			const q = Mangler.findOne(this.questions, { question_id: p.question_id });
			if (q) q.products.push(p);
		});

		// Link counters

		this.question_counters.forEach(qc => {
			const q = Mangler.findOne(this.questions, { question_id: qc.question_id });
			if (q) q.counters.push(qc);

			const c = Mangler.findOne(this.counters, { counter_id: qc.counter_id });
			qc.counter = c || null;
		});

		// Add children and link parents

		this.questions.forEach(q => {
			if (q.parent_id) {
				const p = Mangler.findOne(this.questions, { question_id: q.parent_id });
				if (p) {
					q.parent = p;
					p.children.push(q);
				}
			}
		});

		// Sort roots by display_order

		this.rootQuestions.sort(displayOrderSort);
		if (this.rootQuestions.length) {
			this.rootQuestions[0].first_child = true;
			this.rootQuestions[this.rootQuestions.length - 1].last_child = true;
		}

		// Sort children and select_options by display_order

		this.questions.forEach(q => {
			q.children.sort(displayOrderSort);
			if (q.children.length) {
				q.children[0].first_child = true;
				q.children[q.children.length - 1].last_child = true;
			}

			q.select_options.sort(displayOrderSort);
		});

		// All sorted, crunch tree down to array

		const questionIds = [];

		const walk = (q, p) => {
			questionIds.push(q.question_id);

			q.depth = p ? p.depth + 1 : 0;
			sorted.push(q);
			q.children.forEach(c => {
				walk(c, q);
			});
		};

		this.rootQuestions.forEach(q => {
			walk(q, null);
		});

		this.questions = sorted;

		// Add counter products to counters

		this.counters.forEach(c => c.products = []);
		this.counter_products.forEach(p => {
			const c = Mangler.findOne(this.counters, { counter_id: p.counter_id });
			if (c) c.products.push(p);
		});

		// Sort counters

		this.counters.sort((a, b) => ('' + a.description).localeCompare('' + b.description));

		// Sort counter products

		const counterIds = [];

		this.counters.forEach(c => {
			counterIds.push(c.counter_id);
			c.products.sort((a, b) => a.range_start - b.range_start || a.range_end - b.range_end);
		});

		//
		// Data integrity
		//

		// Remove orphaned records

		this.question_select_options = Mangler.filter(this.question_select_options, { question_id: { $in: questionIds } }) || [];
		this.question_products = Mangler.filter(this.question_products, { question_id: { $in: questionIds } }) || [];
		this.question_counters = Mangler.filter(this.question_counters, { question_id: { $in: questionIds } }) || [];
		this.question_counters = Mangler.filter(this.question_counters, { counter_id: { $in: counterIds } }) || [];
		this.counter_products = Mangler.filter(this.counter_products, { counter_id: { $in: counterIds } }) || [];

		// Fix broken links

		this.question_products.forEach(p => {
			if (p.multiply_by_question_id && questionIds.indexOf(p.multiply_by_question_id) === -1) p.multiply_by_question_id = null;
		});

		this.question_counters.forEach(c => {
			if (c.multiply_by_question_id && questionIds.indexOf(c.multiply_by_question_id) === -1) c.multiply_by_question_id = null;
		});
	}

	private pushQuestionRecords(q) {
		// Add select options
		if ((q.type === 'select' || q.type === 'multi-select') && q.select_options) {
			q.select_options.forEach(o => {
				o.question_id = q.question_id;
				this.question_select_options.push(o);
			});
		}

		// Add products
		if (q.products) {
			q.products.forEach(p => {
				p.question_id = q.question_id;
				if (p.multiply_by_question_id === 0 || p.multiply_by_question_id === -1) p.multiply_by_question_id = q.question_id;
				this.question_products.push(p);
			});
		}

		// Add counters
		if (q.counters) {
			q.counters.forEach(qc => {
				qc.question_id = q.question_id;
				if (qc.multiply_by_question_id === 0 || qc.multiply_by_question_id === -1) qc.multiply_by_question_id = qc.question_id;
				if (qc.value) this.question_counters.push(qc); // Only add counter if value is set
			});
		}

		// Add new question to list and link parents
		this.questions.push(q);
		this.refreshStructure();
	}

	addQuestion(q) {
		// Create new ID
		this.record.last_question_id += 1
		q.question_id = this.record.last_question_id;
		q.display_order = 0;

		this.pushQuestionRecords(q);

		// Resolve display order to add question to end of list
		const siblings = q.parent ? q.parent.children : this.rootQuestions;
		siblings.forEach(s => {
			if (s.display_order > q.display_order) q.display_order = s.display_order;
		});
		q.display_order += 1;
		this.refreshStructure();
	}

	updateQuestion(q) {
		this.questions = Mangler.filter(this.questions, { question_id: { $ne: q.question_id } }) || [];
		this.question_select_options = Mangler.filter(this.question_select_options, { question_id: { $ne: q.question_id } }) || [];
		this.question_products = Mangler.filter(this.question_products, { question_id: { $ne: q.question_id } }) || [];
		this.question_counters = Mangler.filter(this.question_counters, { question_id: { $ne: q.question_id } }) || [];

		this.pushQuestionRecords(q);
	}

	/** Removes a question by passing the object or its ID. */
	removeQuestion(q) {
		if (!Mangler.isObject(q)) q = Mangler.findOne(this.questions, { question_id: q });

		const i = this.questions.indexOf(q);
		if (i !== -1) {
			this.questions.splice(i, 1);
			this.refreshStructure();
			this.requireNewVersion();
		}
	}

	/** Swaps the question with its sibling <offset> spaces away. Can be used to move questions up or down. */
	moveQuestion(q, offset) {
		const siblings = q.parent ? q.parent.children : this.rootQuestions;

		const qi = siblings.indexOf(q);
		if (qi !== -1) {
			const q2 = siblings[qi + offset];
			if (q2) {
				const temp = q.display_order;
				q.display_order = q2.display_order;
				q2.display_order = temp;
				this.refreshStructure();
			}
		}
	}

	private pushCounterRecords(c) {
		// Add products
		if (c.products) {
			c.products.forEach(p => {
				p.counter_id = c.counter_id;

				// Make sure range_start and range_end values are in the correct order
				if (p.range_start > p.range_end) {
					const temp = p.range_start;
					p.range_start = p.range_end;
					p.range_end = temp;
				}

				this.counter_products.push(p);
			});
		}

		// Add new counter to list
		this.counters.push(c);
		this.refreshStructure();
	}

	addCounter(c) {
		// Create new ID
		this.record.last_counter_id += 1
		c.counter_id = this.record.last_counter_id;

		this.pushCounterRecords(c);
	}

	updateCounter(c) {
		this.counters = Mangler.filter(this.counters, { counter_id: { $ne: c.counter_id } }) || [];
		this.counter_products = Mangler.filter(this.counter_products, { counter_id: { $ne: c.counter_id } }) || [];

		this.pushCounterRecords(c);
	}

	/** Removes a counter by passing the object or its ID. */
	removeCounter(c) {
		if (!Mangler.isObject(c)) c = Mangler.findOne(this.counters, { counter_id: c });

		const i = this.counters.indexOf(c);
		if (i !== -1) {
			this.counters.splice(i, 1);
			this.refreshStructure();
			this.requireNewVersion();
		}
	}

	addProduct(p) {
		this.products.push(p);
	}

	updateProduct(p) {
		this.removeProduct(p);
		this.addProduct(p);
	}

	removeProduct(p) {
		if (!Mangler.isObject(p)) p = Mangler.findOne(this.products, { product_id: p });

		const i = this.products.indexOf(p);
		if (i !== -1) {
			this.products.splice(i, 1);
			this.products = this.products.slice();
			this.requireNewVersion();
		}
	}

	formatProductQuantities() {
		this.products.forEach(p => {
			p.quantity = parseFloat(p.quantity) || 0;
		});
	}

	/** Check num against the conditions supported by the bundle. Used for evaluating question visibility and product availability. */
	private checkCondition(num, mode, value, max) {
		num = parseInt(num, 10) || 0;
		value = parseInt(value, 10) || 0;
		max = parseInt(max, 10) || 0;

		switch (mode) {
			case 'set': return !!num;
			case 'value': return num === value;
			case 'range': return num >= value && num <= max;
			case 'lt': return num < value;
			case 'gt': return num > value;
			case 'all': return (num & value) === value;
			case 'any': return (num & value) > 0;
		}

		return false;
	}

	/** Refresh visibility and counters based on answers. */
	refreshAnswers() {
		this.questions.forEach(q => q.visible = false);
		this.counters.forEach(c => c.value = 0);

		// Validate answers

		this.questions.forEach(q => {
			q.answer = parseInt(q.answer, 10) || 0;

			if (q.type === 'numeric') {
				if (q.min_value !== null && q.answer < q.min_value) q.answer = q.min_value;
				if (q.max_value !== null && q.answer > q.max_value) q.answer = q.max_value;
			}
		});

		// Evaluate visibility

		const walk = q => {
			if (!q.parent || this.checkCondition(q.parent.answer, q.parent_mode, q.parent_value, q.parent_max_value)) {
				q.visible = true;
				q.children.forEach(walk);
			}
		};

		this.rootQuestions.forEach(walk);

		// Update counters

		this.questions.forEach(q => {
			if (q.visible && q.answer) {
				q.counters.forEach(qc => {
					let value = qc.value || 0;

					if (qc.multiply_by_question_id) {
						const mq = Mangler.findOne(this.questions, { question_id: qc.multiply_by_question_id });
						if (mq && mq.visible) value *= parseInt(mq.answer, 10) || 0;
					}

					if (value) {
						const c = Mangler.findOne(this.counters, { counter_id: qc.counter_id });
						if (c) c.value += value;
					}
				});
			}
		});
	}

	/** Returns final product list based on the answers. */
	resolveProducts() {
		const result = [];
		const resultIndex = {};

		const addProduct = (p, quantity) => {
			if (!resultIndex[p.product_id]) {
				const product = { product_id: p.product_id, quantity: 0 };
				result.push(product);
				resultIndex[p.product_id] = product;
			}
			resultIndex[p.product_id].quantity += quantity;
		};

		this.refreshAnswers();

		// Add base products

		this.products.forEach(p => {
			if (p.quantity >= 0) addProduct(p, p.quantity);
		});

		// Add counter products

		this.counters.forEach(c => {
			c.products.forEach(p => {
				if (c.value >= p.range_start && c.value <= p.range_end) {
					let quantity = p.quantity || 0;
					if (c.multiply_by_counter) quantity *= c.value || 0;

					if (quantity >= 0) addProduct(p, quantity);
				}
			});
		});

		// Add question products

		this.questions.forEach(q => {
			if (q.visible && q.answer) {
				q.products.forEach(p => {
					if (this.checkCondition(q.answer, p.question_mode, p.question_value, p.question_max_value)) {
						let quantity = p.quantity || 0;

						if (p.multiply_by_question_id) {
							const mq = Mangler.findOne(this.questions, { question_id: p.multiply_by_question_id });
							if (mq && mq.visible) quantity *= parseInt(mq.answer, 10) || 0;
						}

						if (quantity >= 0) addProduct(p, quantity);
					}
				});
			}
		});

		return result;
	}

	requireNewVersion() {
		this.record.new_version = true;
	}

}
