import { DragDropModule } from '@angular/cdk/drag-drop';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgModule, ModuleWithProviders } from '@angular/core';
import { RouterModule } from '@angular/router';

import { AgmCoreModule } from '@agm/core';

import { AutofocusDirective } from './autofocus.directive';
import { ChartjsComponent } from './chartjs/chartjs.component';
import { CountPipe } from './count.pipe';
import { DecimalPipe } from './decimal.pipe';
import { DesktopfocusDirective } from './desktopfocus.directive';
import { GoogleMapComponent } from './google-map/google-map.component';
import { GravatarComponent } from './gravatar/gravatar.component';
import { HeaderComponent } from './header/header.component';
import { IconWithBadgeComponent } from './icon-with-badge/icon-with-badge.component';
import { KeywordsPipe } from './keywords.pipe';
import { ModalComponent } from './modal/modal.component';
import { ModalLoaderComponent } from './modal/modal-loader.component';
import { ModalService } from './modal/modal.service';
import { ModuleComponent } from './module/module.component';
import { MySQLDateToISOPipe } from './mysql-date-to-iso.pipe';
import { NotificationsComponent } from './notifications/notifications.component';
import { SafeStylePipe } from './safe-style.pipe';
import { SidebarComponent } from './sidebar/sidebar.component';
import { SortcodePipe } from './sortcode.pipe';
import { SparklineChartComponent } from './sparkline-chart/sparkline-chart.component';
import { PaginationPipe } from './pagination.pipe';
import { PaginationComponent } from './pagination/pagination.component';
import { ItemListPipe } from './itemlist.pipe';
import { UIElementModalComponent } from './ui-element-modal/ui-element-modal.component';
import { HtmlContentComponent } from './html-content';

@NgModule({
	imports: [
		CommonModule,
		RouterModule,
		FormsModule,
		ReactiveFormsModule,
		AgmCoreModule.forRoot({
			apiKey: 'AIzaSyDPV1ZO_vmoOWoiBjCrG3V16YFSCV9o9uk',
			libraries: ['places']
		}),
		DragDropModule
	],
	exports: [
		ChartjsComponent,
		GravatarComponent,
		IconWithBadgeComponent,
		KeywordsPipe,
		ModalComponent,
		ModalLoaderComponent,
		MySQLDateToISOPipe,
		SafeStylePipe,
		SidebarComponent,
		SparklineChartComponent,
		NotificationsComponent,
		CountPipe,
		AutofocusDirective,
		DesktopfocusDirective,
		HeaderComponent,
		DecimalPipe,
		GoogleMapComponent,
		SortcodePipe,
		PaginationPipe,
		PaginationComponent,
		ItemListPipe,
		HtmlContentComponent
	],
	declarations: [
		ChartjsComponent,
		GravatarComponent,
		IconWithBadgeComponent,
		KeywordsPipe,
		ModalComponent,
		ModalLoaderComponent,
		MySQLDateToISOPipe,
		SafeStylePipe,
		SidebarComponent,
		SparklineChartComponent,
		NotificationsComponent,
		CountPipe,
		AutofocusDirective,
		DesktopfocusDirective,
		HeaderComponent,
		ModuleComponent,
		DecimalPipe,
		GoogleMapComponent,
		SortcodePipe,
		PaginationPipe,
		PaginationComponent,
		ItemListPipe,
		HtmlContentComponent,
		UIElementModalComponent
	],
	entryComponents: [
		UIElementModalComponent
	]
})
export class SharedModule {
	static forRoot(): ModuleWithProviders {
		return {
			ngModule: SharedModule,
			providers: [ModalService]
		};
	}
}
