import { Component, OnInit, OnDestroy, OnChanges, AfterContentInit, ViewChild, Input } from '@angular/core';

declare var $: any;
declare var Mangler: any;

@Component({
	selector: 'sparkline-chart',
	templateUrl: './sparkline-chart.component.html'
})
export class SparklineChartComponent implements OnInit, OnDestroy, OnChanges, AfterContentInit {

	public static colors = [
		'#F9C02B', '#E84F32', '#88B4CB', '#A2B83A', '#ED7339', '#6DBFAA', '#BB5979',
		'#F9C02B', '#E84F32', '#88B4CB', '#A2B83A', '#ED7339', '#6DBFAA', '#BB5979'
	];

	@ViewChild('container') container;
	@Input() data = [];
	@Input() options = [];
	@Input() names = null;

	chart: any;
	timer: any;
	handler: any;

	ngOnInit() {
		this.chart = $(this.container.nativeElement);
		this.handler = () => {
			clearTimeout(this.timer);
			this.timer = setTimeout(() => this.redraw(), 300);
		};
		$(window).on('resize', this.handler);
	}

	ngOnDestroy() {
		$(window).off('resize', this.handler);
	}

	ngOnChanges() {
		this.redraw();
	}

	ngAfterContentInit() {
		setTimeout(() => this.redraw(), 0);
	}

	redraw() {
		// Check if element has been initialised
		if (!this.chart) return;

		clearTimeout(this.timer);

		let dataArray = this.data;
		let optionsArray = this.options;

		if (!Mangler.isArray(optionsArray)) {
			optionsArray = [optionsArray];
			dataArray = [dataArray];
		}

		const width = this.chart.width();
		const height = this.chart.height();

		Mangler.each(optionsArray, (i, options) => {
			const data = dataArray[i];
			if (!data) return;

			let defaults = null;
			switch (options.type) {
				case 'line':
					defaults = {
						width: width,
						chartRangeMax: 100,
						lineColor: '#3bafda',
						fillColor: 'rgba(59,175,218,0.3)',
						highlightLineColor: 'rgba(0,0,0,.1)',
						highlightSpotColor: 'rgba(0,0,0,.2)'
					};
					break;

				case 'bar':
					defaults = {
						width: width,
						barWidth: 10,
						barSpacing: 3,
						barColor: '#3bafda'
					};
					break;

				case 'pie':
					defaults = {
						height: Math.min(height, width),
						sliceColors: SparklineChartComponent.colors
					};
					break;

				default:
					return;
			}

			if (Mangler.isArray(this.names)) {
				defaults['tooltipFormatter'] = (a, b, item) => {
					return '<span style="color:' + item.color + '">&#9679;</span> ' + this.names[item.offset] + ': <b>' + this.data[item.offset] + ' (' + item.percent.toFixed(2) + '%)</b>';
				};
			}

			if (i > 0) defaults['composite'] = true;

			this.chart.sparkline(data, Mangler.merge({}, [defaults, options]));
		});
	}

}
