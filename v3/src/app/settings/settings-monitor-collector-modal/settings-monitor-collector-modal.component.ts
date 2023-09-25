import { AppService } from './../../app.service';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-settings-monitor-collector-modal',
	templateUrl: './settings-monitor-collector-modal.component.html'
})
export class SettingsMonitorCollectorModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	item;
	history;
	buttons: any[] = ['0|Close'];

	customChart;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.api.monitor.getCollectorHistory(this.modalService.data.id, response => {
			this.item = response.data.details;
			this.history = response.data.history;

			if (response.data.chart.length > 0) {
				this.customChart = {
					type: 'line',

					data: {
						datasets: [
							{
								label: 'Availability',
								backgroundColor: 'green',
								steppedLine: true,
								data: response.data.chart
							}
						]
					},

					options: {
						responsive: true,
						maintainAspectRatio: false,
						legend: {
							display: false
						},
						scales: {
							xAxes: [{
								type: 'time',
								time: {
									min: response.data.chart[0].x,
									max: response.data.chart[response.data.chart.length - 1].x
								}
							}],
							yAxes: [{
								ticks: {
									min: 0,
									max: 1,
									stepSize: 1
								}
							}]
						}
					}
				};
			}

			this.buttons = [this.item.ignored ? '1|<+Monitor this collector' : '2|<!Ignore this collector', '0|Close'];
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	modalHandler(event) {
		if (event.type === 'button' && event.data && (event.data.id === 1 || event.data.id === 2)) {
			this.api.monitor.setCollectorIgnoreFlag(this.item.id, event.data.id === 1 ? 0 : 1, () => {
				this.modal.close();
				this.app.notifications.showSuccess(event.data.id === 1 ? 'Collector will be monitored.' : 'Collector will be ignored.');
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		} else {
			this.modal.close();
		}
	}

}
