import { Component, OnInit, OnChanges, AfterContentInit, ViewChild, Input } from '@angular/core';

declare var $: any;
declare var Chart: any;

@Component({
	selector: 'app-chartjs',
	template: `
		<div class="text-center fill chart-container" #container>
			<canvas #canvas></canvas>
		</div>
	`
})
export class ChartjsComponent implements OnInit, OnChanges, AfterContentInit {

	public static colors = [
		'#39a57a', '#62c9a0', '#727be5', '#a9e3ea', '#909eb2', '#2b7b5b', '#9ee2c7',
		'#39a57a', '#62c9a0', '#727be5', '#a9e3ea', '#909eb2', '#2b7b5b', '#9ee2c7'
	];

	@ViewChild('container') container;
	@ViewChild('canvas') canvas;
	@Input() data = [];
	@Input() options: any = {};
	@Input() names = null;
	@Input() custom = null;

	canvasElement: any;
	chart: any;

	public static getColor(n) {
		return this.colors[n % this.colors.length];
	}

	ngOnInit() {
		Chart.pluginService.register({
			beforeDraw: function (chart) {
				if (chart.config.options.elements && chart.config.options.elements.center) {
					// Get ctx from string
					const ctx = chart.chart.ctx;

					// Get options from the center object in options
					const centerConfig = chart.config.options.elements.center;
					const fontStyle = centerConfig.fontStyle || 'Arial';
					const txt = centerConfig.text;
					const txtTop = centerConfig.textTop;
					const color = centerConfig.color || '#000';
					const sidePadding = centerConfig.sidePadding || 20;
					const sidePaddingCalculated = (sidePadding / 100) * (chart.innerRadius * 2)
					// Start with a base font of 30px
					ctx.font = '30px ' + fontStyle;

					// Get the width of the string and also the width of the element minus 10 to give it 5px side padding
					const stringWidth = ctx.measureText(txt).width;
					const elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;

					// Find out how much the font can grow in width.
					const widthRatio = elementWidth / stringWidth;
					const newFontSize = Math.floor(30 * widthRatio);
					const elementHeight = (chart.innerRadius * 2);

					// Pick a new font size so it will not be larger than the height of label.
					const fontSizeToUse = Math.min(newFontSize, elementHeight, centerConfig.maxFontSize || 100);

					// Set font settings to draw it correctly.
					ctx.textAlign = 'center';
					ctx.textBaseline = 'middle';
					const centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
					const centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
					ctx.font = fontSizeToUse + 'px ' + fontStyle;
					ctx.fillStyle = color;

					// Draw text in center
					// ctx.textBaseline = 'top';
					ctx.fillText(txt, centerX, centerY + fontSizeToUse * 0.6);

					// ctx.textBaseline = 'bottom';
					ctx.font = (fontSizeToUse * 1.5) + 'px ' + fontStyle;
					ctx.fillText(txtTop, centerX, centerY - fontSizeToUse);
				}
			}
		});

		this.canvasElement = $(this.canvas.nativeElement);
	}

	ngOnChanges() {
		this.redraw();
	}

	ngAfterContentInit() {
		setTimeout(() => this.redraw(), 0);
	}

	redraw() {
		// Check if element has been initialised
		if (!this.canvasElement) return;

		if (this.chart) this.chart.destroy();

		let sum = 0;
		this.data.forEach(item => sum += parseInt(item, 10));

		if (this.custom) {
			this.chart = new Chart(this.canvasElement, this.custom);
		} else {
			const chartOptions: any = {
				type: this.options.type || 'doughnut',
				data: {
					labels: this.names,
					datasets: [{
						label: '# of Votes',
						data: this.data,
						backgroundColor: this.options.colors || ChartjsComponent.colors,
						borderColor: this.options.colors || ChartjsComponent.colors,
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					cutoutPercentage: this.options.cutoutPercentage || 80,
					elements: this.options.elements || {}
				}
			};

			if (this.options.scales) {
				chartOptions.options.scales = this.options.scales;
			}

			this.chart = new Chart(this.canvasElement, chartOptions);
		}
	}

}
