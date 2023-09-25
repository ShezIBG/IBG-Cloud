import { StockGoodsOutComponent } from './stock-goods-out/stock-goods-out.component';
import { StockGoodsInComponent } from './stock-goods-in/stock-goods-in.component';
import { StockWarehouseEditComponent } from './stock-warehouse-edit/stock-warehouse-edit.component';
import { StockProductEditComponent } from './stock-product-edit/stock-product-edit.component';
import { StockProductsComponent } from './stock-products/stock-products.component';
import { StockProductPricingEditComponent } from './stock-product-pricing-edit/stock-product-pricing-edit.component';
import { StockProductSubscriptionEditComponent } from './stock-product-subscription-edit/stock-product-subscription-edit.component';
import { StockProductLabourEditComponent } from './stock-product-labour-edit/stock-product-labour-edit.component';
import { StockProductUnitEditComponent } from './stock-product-unit-edit/stock-product-unit-edit.component';
import { StockProductTagGroupEditComponent } from './stock-product-tag-group-edit/stock-product-tag-group-edit.component';
import { StockProductCategoryEditComponent } from './stock-product-category-edit/stock-product-category-edit.component';
import { StockProductEntityEditComponent } from './stock-product-entity-edit/stock-product-entity-edit.component';
import { StockProductConfigComponent } from './stock-product-config/stock-product-config.component';
import { StockComponent } from './stock/stock.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { StockWarehousesComponent } from './stock-warehouses/stock-warehouses.component';
import { StockLocationsComponent } from './stock-locations/stock-locations.component';
import { StockViewProductsComponent } from './stock-view-products/stock-view-products.component';
import { StockViewLocationsComponent } from './stock-view-locations/stock-view-locations.component';
import { StockSmoothpowerComponent } from './stock-smoothpower/stock-smoothpower.component';
import { StockSmoothpowerEditComponent } from './stock-smoothpower-edit/stock-smoothpower-edit.component';
import { StockSmoothpowerInstallComponent } from './stock-smoothpower-install/stock-smoothpower-install.component';

const routes: Routes = [
	{
		path: '', component: StockComponent, data: { showOwner: true }, children: [
			{ path: '', pathMatch: 'full', redirectTo: 'view-products' },
			{ path: 'view-products', component: StockViewProductsComponent, data: { changeOwner: true } },
			{ path: 'view-locations', component: StockViewLocationsComponent, data: { changeOwner: true } },
			{ path: 'goods-in', component: StockGoodsInComponent, data: { changeOwner: true } },
			{ path: 'goods-out', component: StockGoodsOutComponent, data: { changeOwner: true } },
			{ path: 'product-config', component: StockProductConfigComponent, data: { changeOwner: true } },
			{ path: 'product-config/:tab', component: StockProductConfigComponent, data: { changeOwner: true } },
			{ path: 'product-config/entity/:id', component: StockProductEntityEditComponent },
			{ path: 'product-config/entity/:id/:owner', component: StockProductEntityEditComponent },
			{ path: 'product-config/category/:id', component: StockProductCategoryEditComponent },
			{ path: 'product-config/category/:id/:owner', component: StockProductCategoryEditComponent },
			{ path: 'product-config/tag-group/:id', component: StockProductTagGroupEditComponent },
			{ path: 'product-config/tag-group/:id/:owner', component: StockProductTagGroupEditComponent },
			{ path: 'product-config/unit/:id', component: StockProductUnitEditComponent },
			{ path: 'product-config/unit/:id/:owner', component: StockProductUnitEditComponent },
			{ path: 'product-config/labour/:id', component: StockProductLabourEditComponent },
			{ path: 'product-config/labour/:id/:categoryId/:owner', component: StockProductLabourEditComponent },
			{ path: 'product-config/subscription/:id/:owner', component: StockProductSubscriptionEditComponent },
			{ path: 'product-config/subscription/:id/:categoryId/:owner', component: StockProductSubscriptionEditComponent },
			{ path: 'product-config/pricing/:id', component: StockProductPricingEditComponent },
			{ path: 'product-config/pricing/:id/:owner', component: StockProductPricingEditComponent },
			{ path: 'product', component: StockProductsComponent, data: { changeOwner: true } },
			{ path: 'product/:id/:owner', component: StockProductEditComponent },
			{ path: 'warehouse', component: StockWarehousesComponent, data: { changeOwner: true } },
			{ path: 'warehouse/:warehouse/locations', component: StockLocationsComponent },
			{ path: 'warehouse/:id/:owner', component: StockWarehouseEditComponent },
			{ path: 'smoothpower', component: StockSmoothpowerComponent, data: { changeOwner: true } },
			{ path: 'smoothpower/:id', component: StockSmoothpowerEditComponent },
			{ path: 'smoothpower/:id/install', component: StockSmoothpowerInstallComponent }
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class StockRoutingModule { }
