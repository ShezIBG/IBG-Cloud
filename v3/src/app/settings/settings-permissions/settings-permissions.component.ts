import { Component, Input } from '@angular/core';

@Component({
	selector: 'app-settings-permissions',
	templateUrl: './settings-permissions.component.html',
	styleUrls: ['./settings-permissions.component.less']
})
export class SettingsPermissionsComponent {

	@Input() ui = {};
	@Input() record = {};

	isAllowed(perm) {
		return !!(this.record[perm.field] & perm.flag);
	}

	toggle(perm, m = null) {
		this.record[perm.field] ^= perm.flag;
		const state = !!(this.record[perm.field] & perm.flag);

		if (m && 0 + perm.flag === 1) {
			// Switch whole module on/off
			this.record[m.toggle.field] = 0;
			m.options.forEach(op => {
				this.record[op.field] = 0;
			});

			if (state) {
				this.record[m.toggle.field] |= m.toggle.flag;
				m.options.forEach(op => {
					this.record[op.field] |= op.flag;
				});
			}
		}
	}

}
