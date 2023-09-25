import { DragDropModule } from '@angular/cdk/drag-drop';
import { EditorModule } from '@tinymce/tinymce-angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule } from 'primeng/primeng';
import { StockProductResellerModalComponent } from './stock-product-reseller-modal/stock-product-reseller-modal.component';
import { StockProductResellersComponent } from './stock-product-resellers/stock-product-resellers.component';
import { StockProductCloneModalComponent } from './stock-product-clone-modal/stock-product-clone-modal.component';
import { StockProductSubscriptionCategoryModalComponent } from './stock-product-subscription-category-modal/stock-product-subscription-category-modal.component';
import { StockProductSubscriptionEditComponent } from './stock-product-subscription-edit/stock-product-subscription-edit.component';
import { StockProductSubscriptionComponent } from './stock-product-subscription/stock-product-subscription.component';
import { StockProductLabourCategoryModalComponent } from './stock-product-labour-category-modal/stock-product-labour-category-modal.component';
import { StockProductSelectModalComponent } from './stock-product-select-modal/stock-product-select-modal.component';
import { StockProductEditComponent } from './stock-product-edit/stock-product-edit.component';
import { StockProductsComponent } from './stock-products/stock-products.component';
import { StockProductPricingEditComponent } from './stock-product-pricing-edit/stock-product-pricing-edit.component';
import { StockProductPricingComponent } from './stock-product-pricing/stock-product-pricing.component';
import { StockProductLabourEditComponent } from './stock-product-labour-edit/stock-product-labour-edit.component';
import { StockProductUnitEditComponent } from './stock-product-unit-edit/stock-product-unit-edit.component';
import { StockProductTagGroupEditComponent } from './stock-product-tag-group-edit/stock-product-tag-group-edit.component';
import { StockProductCategoryEditComponent } from './stock-product-category-edit/stock-product-category-edit.component';
import { StockProductEntityEditComponent } from './stock-product-entity-edit/stock-product-entity-edit.component';
import { StockProductLabourComponent } from './stock-product-labour/stock-product-labour.component';
import { StockProductUnitsComponent } from './stock-product-units/stock-product-units.component';
import { StockProductTagGroupsComponent } from './stock-product-tag-groups/stock-product-tag-groups.component';
import { StockProductCategoriesComponent } from './stock-product-categories/stock-product-categories.component';
import { StockProductEntitiesComponent } from './stock-product-entities/stock-product-entities.component';
import { StockProductConfigComponent } from './stock-product-config/stock-product-config.component';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { StockRoutingModule } from './stock-routing.module';
import { StockComponent } from './stock/stock.component';
import { StockService } from './stock.service';
import { StockWarehousesComponent } from './stock-warehouses/stock-warehouses.component';
import { StockWarehouseEditComponent } from './stock-warehouse-edit/stock-warehouse-edit.component';
import { StockLocationsComponent } from './stock-locations/stock-locations.component';
import { StockGoodsInComponent } from './stock-goods-in/stock-goods-in.component';
import { StockGoodsOutComponent } from './stock-goods-out/stock-goods-out.component';
import { StockViewProductsComponent } from './stock-view-products/stock-view-products.component';
import { StockViewLocationsComponent } from './stock-view-locations/stock-view-locations.component';
import { StockSmoothpowerComponent } from './stock-smoothpower/stock-smoothpower.component';
import { StockSmoothpowerEditComponent } from './stock-smoothpower-edit/stock-smoothpower-edit.component';
import { StockSmoothpowerInstallComponent } from './stock-smoothpower-install/stock-smoothpower-install.component';
import { StockEntitySelectModalComponent } from './stock-entity-select-modal/stock-entity-select-modal.component';
import { StockBundleCounterEditModalComponent } from './stock-bundle-counter-edit-modal/stock-bundle-counter-edit-modal.component';
import { StockBundleQuestionEditModalComponent } from './stock-bundle-question-edit-modal/stock-bundle-question-edit-modal.component';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		StockRoutingModule,
		FormsModule,
		EditorModule,
		DragDropModule
	],
	declarations: [
		StockComponent,
		StockProductConfigComponent,
		StockProductEntitiesComponent,
		StockProductCategoriesComponent,
		StockProductTagGroupsComponent,
		StockProductUnitsComponent,
		StockProductLabourComponent,
		StockProductEntityEditComponent,
		StockProductCategoryEditComponent,
		StockProductTagGroupEditComponent,
		StockProductUnitEditComponent,
		StockProductLabourEditComponent,
		StockProductPricingComponent,
		StockProductPricingEditComponent,
		StockProductsComponent,
		StockProductEditComponent,
		StockProductSelectModalComponent,
		StockProductLabourCategoryModalComponent,
		StockProductSubscriptionComponent,
		StockProductSubscriptionEditComponent,
		StockProductSubscriptionCategoryModalComponent,
		StockProductCloneModalComponent,
		StockProductResellersComponent,
		StockProductResellerModalComponent,
		StockWarehousesComponent,
		StockWarehouseEditComponent,
		StockLocationsComponent,
		StockGoodsInComponent,
		StockGoodsOutComponent,
		StockViewProductsComponent,
		StockViewLocationsComponent,
		StockSmoothpowerComponent,
		StockSmoothpowerEditComponent,
		StockSmoothpowerInstallComponent,
		StockEntitySelectModalComponent,
		StockBundleCounterEditModalComponent,
		StockBundleQuestionEditModalComponent
	],
	entryComponents: [
		StockProductSelectModalComponent,
		StockProductLabourCategoryModalComponent,
		StockProductSubscriptionCategoryModalComponent,
		StockProductCloneModalComponent,
		StockProductResellerModalComponent,
		StockEntitySelectModalComponent,
		StockBundleCounterEditModalComponent,
		StockBundleQuestionEditModalComponent
	],
	providers: [
		StockService
	]
})
export class StockModule { }
