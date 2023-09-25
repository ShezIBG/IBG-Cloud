import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'mySQLDateToISO'
})
export class MySQLDateToISOPipe implements PipeTransform {

	static stringToDate(str: string) {
		return str ? new Date(MySQLDateToISOPipe.transform(str)) : null;
	}

	static dateToString(d: Date) {
		if (!d || !(d instanceof Date)) return null;
		d = new Date(d);
		d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
		return d.toISOString().split('.')[0].replace('T', ' ');
	}

	static transform(value: any) {
		return value ? ('' + value).replace(' ', 'T') : null;
	}

	transform(value: any): any {
		return MySQLDateToISOPipe.transform(value);
	}

}
