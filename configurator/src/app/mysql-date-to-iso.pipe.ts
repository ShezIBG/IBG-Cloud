import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'mySQLDateToISO'
})
export class MySQLDateToISOPipe implements PipeTransform {

	static stringToDate(s: string) {
		return s ? new Date(MySQLDateToISOPipe.transform(s)) : null;
	}

	static dateToString(d: Date, addTime = true) {
		if (!d || !(d instanceof Date)) return null;
		d = new Date(d);
		d.setMinutes(d.getMinutes() - d.getTimezoneOffset());

		let result = d.toISOString().split('.')[0].replace('T', ' ');
		if (!addTime) result = result.split(' ')[0];
		return result;
	}

	static transform(value: any) {
		return value ? ('' + value).replace(' ', 'T') : null;
	}

	transform(value: any): any {
		return MySQLDateToISOPipe.transform(value);
	}

}
