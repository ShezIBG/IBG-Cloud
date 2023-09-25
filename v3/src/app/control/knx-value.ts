/**
 * Supported KNX types and subtypes:
 *
 * 1 - Boolean 0/1
 *     1 - Off / On
 *     2 - False / True
 *     3 - Disable / Enable
 *     4 - No ramp / Ramp
 *     5 - No alarm / Alarm
 *     6 - Low / High
 *     7 - Decrease / Increase
 *     8 - Up / Down
 *     9 - Open / Close
 *     10 - Start / Stop
 *     11 - Inactive / Active
 *     12 - Not inverted / Inverted
 *     15 - No Action / Reset
 *     16 - No Action / Acknowledge
 *     17 - Trigger / Trigger
 *     18 - Not occupied / Occupied
 *     19 - Closed / Open (window/door)
 *     21 - OR / AND
 *     22 - Scene A / Scene B
 *
 * 5 - Unsigned Byte
 *     1 - 0-100% => (0-255)
 *
 * 8 - Signed Word
 *     0 - Number
 *     1 - pulses
 *
 * 9 - Float
 *     Sub-types change only the unit, see textValue() function
 *
 * 20 - HVAC Mode
 *     105 - HVAC Mode
 *
 */

export class KnxValue {

	id = 0;
	dataType = 1;
	subType = 1;
	value: any = '';
	readOnly = false;

	typedValue: any = null;

	constructor(id, dataType, subType, value, readOnly) {
		this.id = id;
		this.dataType = dataType;
		this.subType = subType;
		this.value = value;
		this.readOnly = !!readOnly;
		this.toTyped();
	}

	toTyped() {
		let temp;

		if (this.value === 'NULL' && this.dataType !== 1) {
			this.typedValue = null;
			return;
		}

		switch (this.dataType) {

			case 1: // Boolean
				this.typedValue = (this.value === 1 || this.value === '1');
				break;

			case 5: // Unsigned byte
				temp = parseInt(this.value, 10) || 0;

				switch (this.subType) {
					case 1:
						this.typedValue = Math.round((temp / 255) * 100);
						break;

					default:
						this.typedValue = temp;
						break;
				}
				break;

			case 8: // Signed word
				temp = parseInt(this.value, 10) || 0;
				this.typedValue = temp;
				break;

			case 9: // Float
				this.typedValue = parseFloat(this.value) || 0;
				break;

			case 20: // HVAC Mode
			default:
				this.typedValue = this.value;
				break;
		}
	}

	fromTyped() {
		let temp;

		if (this.typedValue === null) {
			this.value = 'NULL';
			return;
		}

		switch (this.dataType) {
			case 1: // Boolean
				this.value = this.typedValue ? '1' : '0';
				break;

			case 5: // Unsigned byte
				temp = parseInt(this.typedValue, 10) || 0;

				switch (this.subType) {
					case 1: // 0-100% (0-255)
						this.value = '' + (Math.round((temp / 100) * 255));
						break;

					default: // 0-255
						this.value = '' + temp;
						break;
				}
				break;

			case 8: // Signed word
				temp = parseInt(this.typedValue, 10) || 0;
				this.value = '' + temp;
				break;

			case 9:
				this.value = '' + (parseFloat(this.typedValue) || 0);
				break;

			case 20: // HVAC Mode
			default:
				this.value = '' + this.typedValue;
				break;
		}
	}

	textValue(value = this.typedValue) {
		if (value === null) return 'None';

		switch (this.dataType) {
			case 1: // Boolean
				switch (this.subType) {
					case 1: return !value ? 'Off' : 'On';
					case 2: return !value ? 'False' : 'True';
					case 3: return !value ? 'Disable' : 'Enable';
					case 4: return !value ? 'No Ramp' : 'Ramp';
					case 5: return !value ? 'No Alarm' : 'Alarm';
					case 6: return !value ? 'Low' : 'High';
					case 7: return !value ? 'Decrease' : 'Increase';
					case 8: return !value ? 'Up' : 'Down';
					case 9: return !value ? 'Open' : 'Close';
					case 10: return !value ? 'Start' : 'Stop';
					case 11: return !value ? 'Inactive' : 'Active';
					case 12: return !value ? 'Not Inverted' : 'Inverted';
					case 15: return !value ? 'No Action' : 'Reset';
					case 16: return !value ? 'No Action' : 'Acknowledge';
					case 17: return !value ? 'Trigger' : 'Trigger';
					case 18: return !value ? 'Not Occupied' : 'Occupied';
					case 19: return !value ? 'Closed' : 'Open';
					case 21: return !value ? 'OR' : 'AND';
					case 22: return !value ? 'Scene A' : 'Scene B';
					default: return value ? 'On' : 'Off';
				}

			case 5: // Unsigned byte
				switch (this.subType) {
					case 1: return '' + value + '%';
					default: return '' + value;
				}

			case 8: // Signed word
				switch (this.subType) {
					case 1: return '' + value + (Math.abs(value) === 1 ? ' pulse' : ' pulses');
					default: return '' + value;
				}

			case 9: // Float
				switch (this.subType) {
					case 1: return '' + value.toFixed(1) + ' °C';
					case 2: return '' + value.toFixed(1) + ' K';
					case 3: return '' + value.toFixed(1) + ' K/h';
					case 4: return '' + value.toFixed(1) + ' Lux';
					case 5: return '' + value.toFixed(1) + ' m/s';
					case 6: return '' + value.toFixed(1) + ' Pa';
					case 7: return '' + value.toFixed(1) + ' %';
					case 8: return '' + value.toFixed(1) + ' ppm';
					case 10: return '' + value.toFixed(1) + ' s';
					case 11: return '' + value.toFixed(1) + ' ms';
					case 20: return '' + value.toFixed(1) + ' mV';
					case 21: return '' + value.toFixed(1) + ' mA';
					case 22: return '' + value.toFixed(1) + ' W/m2';
					case 23: return '' + value.toFixed(1) + ' K/%';
					case 24: return '' + value.toFixed(1) + ' kW';
					case 25: return '' + value.toFixed(1) + ' l/h';
					default: return '' + value.toFixed(1);
				}

			case 20: // HVAC Mode
				switch (value) {
					case '0': return 'Auto';
					case '1': return 'Heat';
					case '2': return 'Morning Warmup';
					case '3': return 'Cool';
					case '4': return 'Night Purge';
					case '5': return 'Precool';
					case '6': return 'Off';
					case '7': return 'Test';
					case '8': return 'Emergency Heat';
					case '9': return 'Fan Only';
					case '10': return 'Free Cool';
					case '11': return 'Ice';
					case '14': return 'Dry';
					default: return 'Reserved';
				}

			default:
				return '';
		}
	}

}
