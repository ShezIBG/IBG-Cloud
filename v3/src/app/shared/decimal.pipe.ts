import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'decimal'
})
export class DecimalPipe implements PipeTransform {

	static parse(value: any) {
		value = ('' + value).replace(/[£,]/g, '');
		return Math.max(Math.min(parseFloat(value), 1e+20), -1e+20) || 0;
	}

	static transform(value: any, min: number = null, max: number = null, addThousandSeparators: boolean = true, currency: string = ''): any {
		if (max === null && min === null) {
			min = 0;
			max = 10;
		} else if (max === null) {
			max = min;
		}

		if (min < 0 || min === null) min = 0;
		if (max < 0 || max === null) max = 0;

		if (min > max) {
			[min, max] = [max, min];
		}

		const chunks = this.parse(value).toFixed(max).split('.', 2);

		// Handle trailing zeroes
		if (min !== max && max > 0) {
			while (chunks[1].length > min && chunks[1].slice(-1) === '0') {
				chunks[1] = chunks[1].slice(0, chunks[1].length - 1);
			}
		}

		// Add thousand separators
		if (addThousandSeparators) {
			chunks[0] = chunks[0].replace(/(\d)(?=(?:\d{3})+$)/g, '$1,');
		}

		const result = chunks[1] === '' ? chunks[0] : chunks.join('.');

		switch (currency) {
			case 'GBP':
				return (result[0] === '-' ? '-' : '') + '£' + result.replace('-', '');

			default: return result;
		}
	}

	transform(value: any, min: number = null, max: number = null, addThousandSeparators: boolean = true, currency: string = ''): any {
		return DecimalPipe.transform(value, min, max, addThousandSeparators, currency);
	}

}
