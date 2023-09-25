import { AppService } from './../../app.service';
import { ActivatedRoute } from '@angular/router';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-config',
	templateUrl: './stock-product-config.component.html'
})
export class StockProductConfigComponent implements OnInit, OnDestroy {

	private sub: any;

	constructor(
		public app: AppService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const tab = params['tab'];
			const baseRoute = '/stock/product-config'

			setTimeout(() => {
				this.app.header.clearAll();
				this.app.header.addCrumb({ description: 'Product Catalogue Configuration' });
				this.app.header.addTab({ id: 'entity', title: 'Manufacturers and Suppliers', route: baseRoute + '/entity' });
				this.app.header.addTab({ id: 'category', title: 'Categories', route: baseRoute + '/category' });
				this.app.header.addTab({ id: 'tag-group', title: 'Tags', route: baseRoute + '/tag-group' });
				this.app.header.addTab({ id: 'unit', title: 'Units', route: baseRoute + '/unit' });
				this.app.header.addTab({ id: 'labour', title: 'Labour types', route: baseRoute + '/labour' });
				this.app.header.addTab({ id: 'subscription', title: 'Subscription types', route: baseRoute + '/subscription' });
				this.app.header.addTab({ id: 'pricing', title: 'Pricing structures', route: baseRoute + '/pricing' });
				this.app.header.addTab({ id: 'resellers', title: 'Resellers', route: baseRoute + '/resellers' });
				this.app.header.setTab(tab);
			}, 0);
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
