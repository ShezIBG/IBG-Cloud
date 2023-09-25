import { SettingsMonitorCollectorModalComponent } from './../settings-monitor-collector-modal/settings-monitor-collector-modal.component';
import { AppService } from './../../app.service';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-settings-monitor-collectors',
	templateUrl: './settings-monitor-collectors.component.html'
})
export class SettingsMonitorCollectorsComponent implements OnInit, OnDestroy {

	private sub;

	data;
	list;
	count = { list: 0 };
	search = '';

	customChart;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const tab = params['tab'];
			const baseRoute = '/settings/monitor-collectors';

			this.api.monitor.getCollectorOverview(response => {
				this.data = response.data;
				this.refreshNavigationBadge();
				this.refreshChart();

				this.app.header.clearAll();
				this.app.header.addCrumb({ description: 'Collector Monitoring' });
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				this.app.header.addTab({ id: 'all', title: 'All collectors', route: baseRoute + '/all' });
				this.app.header.addTab({ id: 'monitored', title: 'Monitored', route: baseRoute + '/monitored' });
				this.app.header.addTab({ id: 'ok', title: 'OK', route: baseRoute + '/ok' });
				this.app.header.addTab({ id: 'errors', title: 'Errors', route: baseRoute + '/errors' });
				this.app.header.addTab({ id: 'ignored', title: 'Ignored', route: baseRoute + '/ignored' });
				this.app.header.setTab(tab);

				if (this.app.header.activeTab !== 'overview') {
					this.reloadList();
				}
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	reloadList() {
		if (this.app.header.activeTab === 'overview') {
			this.api.monitor.getCollectorOverview(response => {
				this.data = response.data;
				this.refreshNavigationBadge();
				this.refreshChart();
			}, response => {
				this.data = null;
				this.app.notifications.showDanger(response.message);
			});
		} else {
			this.api.monitor.getCollectors(this.app.header.activeTab, response => {
				this.list = response.data;
			}, response => {
				this.list = [];
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	refreshChart() {
		this.customChart = null;

		if (this.data.reliability.length) {
			this.customChart = {
				type: 'bar',

				data: {
					labels: [],
					datasets: [
						{
							label: 'Reliability',
							backgroundColor: [],
							data: []
						}
					]
				},

				options: {
					title: {
						display: true,
						text: '7-day collector reliability'
					},
					responsive: true,
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					scales: {
						xAxes: [{
							gridLines: {
								display: false
							},
							ticks: {
								display: false
							}
						}],
						yAxes: [{
							ticks: {
								min: 0,
								max: 100,
							}
						}]
					}
				}
			};

			this.data.reliability.forEach(item => {
				const r = item.reliability || 0;
				let c = '#45B170';
				if (r < 100) c = '#F7BC3B';
				if (r < 50) c = '#E84F32';

				this.customChart.data.labels.push(item.description);
				this.customChart.data.datasets[0].data.push(r);
				this.customChart.data.datasets[0].backgroundColor.push(c);
			});
		}
	}

	refreshNavigationBadge() {
		this.app.sidebar.menu.forEach(menuItem => {
			if (menuItem.route === '/settings/monitor-collectors') {
				menuItem.badgeIcon = (this.data.last_check === null || this.data.last_check > 900) ? 'md md-warning' : '';
				menuItem.badge = this.data.error ? '' + this.data.error : '';
			}
		});
	}

	openCollectorDetails(id) {
		this.app.modal.open(SettingsMonitorCollectorModalComponent, this.moduleRef, { id });

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadList();
		});
	}

}
