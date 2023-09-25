import { Component } from '@angular/core';

declare var Mangler: any;

export class NotificationItem {

	static STYLE_PRIMARY = 'primary';
	static STYLE_DANGER = 'danger';
	static STYLE_WARNING = 'warning';
	static STYLE_SUCCESS = 'success';

	static LIFETIME_SHORT = 2000;
	static LIFETIME_LONG = 5000;
	static LIFETIME_INFINITE = 0;

	private timer;
	public shown = false;

	constructor(
		private component: NotificationsComponent,
		public style,
		public title,
		public message,
		public lifetime = NotificationItem.LIFETIME_SHORT
	) {
		this.timer = setTimeout(() => {
			this.shown = true;
			if (lifetime > 0) {
				this.timer = setTimeout(() => {
					this.remove();
				}, lifetime);
			}
		}, 100);
	};

	public remove() {
		clearTimeout(this.timer);
		this.shown = false;

		setTimeout(() => {
			const i = this.component.list.indexOf(this);
			if (i !== -1) {
				this.component.list.splice(i, 1);
				this.component.list = this.component.list.slice();
			}
		}, 1000);
	}
}

@Component({
	selector: 'app-notifications',
	templateUrl: './notifications.component.html',
	styleUrls: ['./notifications.component.less']
})
export class NotificationsComponent {

	list: NotificationItem[] = [];

	constructor() {
		Mangler.registerType(NotificationItem, { get: 'array' });
	}

	show(style, title, message = '', lifetime = NotificationItem.LIFETIME_SHORT) {
		this.list = this.list.concat([new NotificationItem(this, style, title, message, lifetime)]);

		const shownItems = Mangler.find(this.list, { shown: true });
		if (shownItems.length > 8) shownItems[0].remove();
	}

	showDanger(title, message = '', lifetime = NotificationItem.LIFETIME_LONG) { this.show(NotificationItem.STYLE_DANGER, title, message, lifetime); }
	showWarning(title, message = '', lifetime = NotificationItem.LIFETIME_LONG) { this.show(NotificationItem.STYLE_WARNING, title, message, lifetime); }
	showSuccess(title, message = '', lifetime = NotificationItem.LIFETIME_SHORT) { this.show(NotificationItem.STYLE_SUCCESS, title, message, lifetime); }
	showPrimary(title, message = '', lifetime = NotificationItem.LIFETIME_LONG) { this.show(NotificationItem.STYLE_PRIMARY, title, message, lifetime); }

}
