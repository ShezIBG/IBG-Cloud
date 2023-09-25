import { DecimalPipe } from './../shared/decimal.pipe';
import { Injectable } from '@angular/core';

@Injectable()
export class StockService {

	productFields = {
		id: { field: 'id', title: '#', cls: 'text-right small', order: true },
		sku: { field: 'sku', title: 'SKU', cls: 'nowrap', order: true, width: '200px' },
		manufacturer_name: { field: 'manufacturer_name', title: 'Manufacturer', order: true },
		model: { field: 'model', title: 'Model / Manufacturer', order: true, width: '200px' },
		category_name: { field: 'category_name', title: 'Category', order: true },
		short_description: { field: 'short_description', title: 'Short description', order: true, width: '200px' },
		unit_name: { field: 'unit_name', title: 'Unit', order: true },
		unit_cost: { field: 'unit_cost', title: 'Cost', order: true, cls: 'text-right', format: 'currency' },
		pricing_structure_description: { field: 'pricing_structure_description', title: 'Pricing structure', order: true, format: 'default:Custom' },
		distribution_price: { field: 'distribution_price', title: 'Distribution price', order: true, cls: 'text-right', format: 'currency' },
		reseller_price: { field: 'reseller_price', title: 'Reseller price', order: true, cls: 'text-right', format: 'currency' },
		trade_price: { field: 'trade_price', title: 'Trade price', order: true, cls: 'text-right', format: 'currency' },
		retail_price: { field: 'retail_price', title: 'Retail price', order: true, cls: 'text-right', format: 'currency' },
		height: { field: 'height', title: 'Height', order: true, cls: 'text-right' },
		width: { field: 'width', title: 'Width', order: true, cls: 'text-right' },
		depth: { field: 'depth', title: 'Depth', order: true, cls: 'text-right' },
		has_bom: { field: 'has_bom', title: 'BOM?', order: true },
		is_placeholder: { field: 'is_placeholder', title: 'Placeholder?', order: true },
		is_stocked: { field: 'is_stocked', title: 'Stocked?', order: true },
		sold_to_customer: { field: 'sold_to_customer', title: 'Sold to customer?', order: true },
		sold_to_reseller: { field: 'sold_to_reseller', title: 'Sold to reseller?', order: true },
		tags: { field: 'tags', title: 'Tags', order: false },
		image_url: { field: 'image_url', title: '', order: false, format: 'clear', width: '1%' },
		owner_name: { field: 'owner_name', title: 'Owned by', order: true },
		whinfo: { field: 'whinfo', title: 'Stock info', order: false, format: 'clear' }
	};

	productViews = [
		{
			description: 'Details',
			columns: [
				this.productFields.image_url,
				this.productFields.model,
				this.productFields.short_description,
				this.productFields.sku,
				this.productFields.category_name,
				this.productFields.owner_name,
				this.productFields.retail_price,
				this.productFields.id
			]
		},
		{
			description: 'Pricing',
			columns: [
				this.productFields.image_url,
				this.productFields.model,
				this.productFields.pricing_structure_description,
				this.productFields.unit_cost,
				this.productFields.distribution_price,
				this.productFields.reseller_price,
				this.productFields.trade_price,
				this.productFields.retail_price,
				this.productFields.id
			]
		},
		{
			description: 'Stock info',
			columns: [
				this.productFields.image_url,
				this.productFields.model,
				this.productFields.short_description,
				this.productFields.sku,
				this.productFields.owner_name,
				this.productFields.whinfo,
				this.productFields.id
			]
		}
	];

	productSearch = '';
	productView = this.productViews[0];
	productOrder = 'sku';
	productDiscontinued = false;
	productFilters = false;

	showArchivedEntities = false;

	formatField(data, format) {
		const [type, param] = ('' + format).split(':');

		switch (type) {
			case 'currency': return '£' + DecimalPipe.transform(data, 2, 4);
			case 'clear': return '';
			case 'default': return data || param;
			default: return data;
		}
	}

}
