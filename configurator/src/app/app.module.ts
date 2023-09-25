import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { CalendarModule } from 'primeng/primeng';
import { ApiService } from './api.service';
import { SafeStylePipe } from './safe-style.pipe';
import { BrowserModule } from '@angular/platform-browser';
import { NgModule, LOCALE_ID } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpModule } from '@angular/http';
import { AppComponent } from './app.component';
import { ToolboxComponent } from './toolbox/toolbox.component';
import { TreeAssignComponent } from './tree-assign/tree-assign.component';
import { TreeDeviceComponent } from './tree-device/tree-device.component';
import { FloorDetailsComponent } from './entity/floor-details/floor-details.component';
import { AreaItemsComponent } from './entity/area-items/area-items.component';
import { AreaDetailsComponent } from './entity/area-details/area-details.component';
import { DistboardDetailsComponent } from './entity/distboard-details/distboard-details.component';
import { EntitySortPipe } from './entity/entity-sort.pipe';
import { MeterDetailsComponent } from './entity/meter-details/meter-details.component';
import { ModalComponent } from './modal/modal.component';
import { ModalLoaderComponent } from './modal/modal-loader.component';
import { BreakerNewComponent } from './entity/breaker-new/breaker-new.component';
import { AutofocusDirective } from './autofocus.directive';
import { DesktopfocusDirective } from './desktopfocus.directive';
import { EntityDeleteComponent } from './entity/entity-delete/entity-delete.component';
import { ScreenComponent } from './screen/screen.component';
import { TagPipe } from './entity/tag.pipe';
import { DistboardVirtualNewComponent } from './entity/distboard-virtual-new/distboard-virtual-new.component';
import { RouterDetailsComponent } from './entity/router-details/router-details.component';
import { GatewayDetailsComponent } from './entity/gateway-details/gateway-details.component';
import { PM12DetailsComponent } from './entity/pm12-details/pm12-details.component';
import { ABBMeterDetailsComponent } from './entity/abb-meter-details/abb-meter-details.component';
import { AssignFilterPipe } from './entity/assign-filter.pipe';
import { AssignablesTreeComponent } from './entity/assignables-tree/assignables-tree.component';
import { AssignablesPipe } from './entity/assignables.pipe';
import { RouterAssignComponent } from './entity/router-assign/router-assign.component';
import { GatewayAssignComponent } from './entity/gateway-assign/gateway-assign.component';
import { PM12AssignComponent } from './entity/pm12-assign/pm12-assign.component';
import { ABBMeterAssignComponent } from './entity/abb-meter-assign/abb-meter-assign.component';
import { EntityAssignmentsComponent } from './entity/entity-assignments/entity-assignments.component';
import { EntityAssignmentsPipe } from './entity/entity-assignments.pipe';
import { CTNewComponent } from './entity/ct-new/ct-new.component';
import { BreakerEditComponent } from './entity/breaker-edit/breaker-edit.component';
import { CTEditComponent } from './entity/ct-edit/ct-edit.component';
import { CategoryTreeComponent } from './entity/category-tree/category-tree.component';
import { CommitChangesComponent } from './commit-changes/commit-changes.component';
import { CommitResultComponent } from './commit-result/commit-result.component';
import { MBusMasterDetailsComponent } from './entity/mbus-master-details/mbus-master-details.component';
import { MBusMasterAssignComponent } from './entity/mbus-master-assign/mbus-master-assign.component';
import { MBusSortPipe } from './entity/mbus-sort.pipe';
import { OverviewComponent } from './overview/overview.component';
import { WidgetSummaryAreasComponent } from './widget/widget-summary-areas/widget-summary-areas.component';
import { WidgetSummaryRoutersComponent } from './widget/widget-summary-routers/widget-summary-routers.component';
import { WidgetSummaryPointsComponent } from './widget/widget-summary-points/widget-summary-points.component';
import { WidgetAssignmentsComponent } from './widget/widget-assignments/widget-assignments.component';
import { WidgetDeviceTypesComponent } from './widget/widget-device-types/widget-device-types.component';
import { WidgetUserHistoryComponent } from './widget/widget-user-history/widget-user-history.component';
import { WidgetWeatherComponent } from './widget/widget-weather/widget-weather.component';
import { WidgetBuildingInfoComponent } from './widget/widget-building-info/widget-building-info.component';
import { SparklineChartComponent } from './sparkline-chart/sparkline-chart.component';
import { IconWithBadgeComponent } from './icon-with-badge/icon-with-badge.component';
import { WeatherIconComponent } from './weather-icon/weather-icon.component';
import { GravatarComponent } from './gravatar/gravatar.component';
import { WidgetIssuesComponent } from './widget/widget-issues/widget-issues.component';
import { MySQLDateToISOPipe } from './mysql-date-to-iso.pipe';
import { EmLightDetailsComponent } from './entity/em-light-details/em-light-details.component';
import { EntityCloneComponent } from './entity/entity-clone/entity-clone.component';
import { EntityCloneModalComponent } from './entity/entity-clone-modal/entity-clone-modal.component';
import { TreeFloorplanComponent } from './tree-floorplan/tree-floorplan.component';
import { FloorplanComponent } from './floorplan/floorplan.component';
import { FloorplanInfoComponent } from './floorplan-info/floorplan-info.component';
import { FloorplanDeviceListComponent } from './floorplan-device-list/floorplan-device-list.component';
import { FloorplanDeviceInfoComponent } from './floorplan-device-info/floorplan-device-info.component';
import { EntityMoveComponent } from './entity/entity-move/entity-move.component';

// Import Angular locale data
import { registerLocaleData } from '@angular/common';
import localeENGB from '@angular/common/locales/en-GB';
import { FiberHeadendServerDetailsComponent } from './entity/fiber-headend-server-details/fiber-headend-server-details.component';
import { FiberOLTDetailsComponent } from './entity/fiber-olt-details/fiber-olt-details.component';
import { FiberONUDetailsComponent } from './entity/fiber-onu-details/fiber-onu-details.component';
import { FiberHeadendServerAssignComponent } from './entity/fiber-headend-server-assign/fiber-headend-server-assign.component';
import { FiberOLTAssignComponent } from './entity/fiber-olt-assign/fiber-olt-assign.component';
import { BuildingServerDetailsComponent } from './entity/building-server-details/building-server-details.component';
import { CoolHubDetailsComponent } from './entity/coolhub-details/coolhub-details.component';
import { CoolPlugDetailsComponent } from './entity/coolplug-details/coolplug-details.component';
import { BuildingServerAssignComponent } from './entity/building-server-assign/building-server-assign.component';
import { CoolHubAssignComponent } from './entity/coolhub-assign/coolhub-assign.component';
import { RelayDeviceDetailsComponent } from './entity/relay-device-details/relay-device-details.component';
import { RelayEndDeviceDetailsComponent } from './entity/relay-end-device-details/relay-end-device-details.component';
import { SmoothPowerDetailsComponent } from './entity/smoothpower-details/smoothpower-details.component';
import { SmoothPowerNewComponent } from './entity/smoothpower-new/smoothpower-new.component';
import { DaliLightDetailsComponent } from './entity/dali-light-details/dali-light-details.component';
registerLocaleData(localeENGB);

// TODO: Replace HttpModule with HttpClientModule

@NgModule({
	declarations: [
		AppComponent,
		ToolboxComponent,
		TreeAssignComponent,
		TreeDeviceComponent,
		FloorDetailsComponent,
		AreaItemsComponent,
		AreaDetailsComponent,
		DistboardDetailsComponent,
		EntitySortPipe,
		MeterDetailsComponent,
		ModalComponent,
		ModalLoaderComponent,
		BreakerNewComponent,
		AutofocusDirective,
		DesktopfocusDirective,
		EntityDeleteComponent,
		ScreenComponent,
		TagPipe,
		DistboardVirtualNewComponent,
		RouterDetailsComponent,
		GatewayDetailsComponent,
		PM12DetailsComponent,
		ABBMeterDetailsComponent,
		AssignFilterPipe,
		AssignablesTreeComponent,
		AssignablesPipe,
		RouterAssignComponent,
		GatewayAssignComponent,
		PM12AssignComponent,
		ABBMeterAssignComponent,
		EntityAssignmentsComponent,
		EntityAssignmentsPipe,
		CTNewComponent,
		BreakerEditComponent,
		CTEditComponent,
		CategoryTreeComponent,
		CommitChangesComponent,
		CommitResultComponent,
		MBusMasterDetailsComponent,
		MBusMasterAssignComponent,
		MBusSortPipe,
		OverviewComponent,
		WidgetSummaryAreasComponent,
		WidgetSummaryRoutersComponent,
		WidgetSummaryPointsComponent,
		WidgetAssignmentsComponent,
		WidgetDeviceTypesComponent,
		WidgetUserHistoryComponent,
		WidgetWeatherComponent,
		WidgetBuildingInfoComponent,
		SparklineChartComponent,
		IconWithBadgeComponent,
		WeatherIconComponent,
		GravatarComponent,
		WidgetIssuesComponent,
		MySQLDateToISOPipe,
		EmLightDetailsComponent,
		EntityCloneComponent,
		EntityCloneModalComponent,
		TreeFloorplanComponent,
		FloorplanComponent,
		FloorplanInfoComponent,
		SafeStylePipe,
		FloorplanDeviceListComponent,
		FloorplanDeviceInfoComponent,
		EntityMoveComponent,
		FiberHeadendServerDetailsComponent,
		FiberOLTDetailsComponent,
		FiberONUDetailsComponent,
		FiberHeadendServerAssignComponent,
		FiberOLTAssignComponent,
		BuildingServerDetailsComponent,
		CoolHubDetailsComponent,
		CoolPlugDetailsComponent,
		BuildingServerAssignComponent,
		CoolHubAssignComponent,
		RelayDeviceDetailsComponent,
		RelayEndDeviceDetailsComponent,
		SmoothPowerDetailsComponent,
		SmoothPowerNewComponent,
		DaliLightDetailsComponent
	],
	imports: [
		BrowserModule,
		BrowserAnimationsModule,
		FormsModule,
		HttpModule,
		CalendarModule
	],
	providers: [
		ApiService,
		{ provide: LOCALE_ID, useValue: 'en-GB' }
	],
	bootstrap: [AppComponent],
	entryComponents: [
		ABBMeterAssignComponent,
		ABBMeterDetailsComponent,
		AreaDetailsComponent,
		AreaItemsComponent,
		BreakerEditComponent,
		BreakerNewComponent,
		BuildingServerAssignComponent,
		BuildingServerDetailsComponent,
		CoolHubAssignComponent,
		CoolHubDetailsComponent,
		CoolPlugDetailsComponent,
		CTEditComponent,
		CTNewComponent,
		DaliLightDetailsComponent,
		DistboardDetailsComponent,
		DistboardVirtualNewComponent,
		EmLightDetailsComponent,
		EntityCloneModalComponent,
		FiberHeadendServerDetailsComponent,
		FiberHeadendServerAssignComponent,
		FiberOLTDetailsComponent,
		FiberOLTAssignComponent,
		FiberONUDetailsComponent,
		FloorDetailsComponent,
		GatewayAssignComponent,
		GatewayDetailsComponent,
		MBusMasterAssignComponent,
		MBusMasterDetailsComponent,
		MeterDetailsComponent,
		PM12AssignComponent,
		PM12DetailsComponent,
		RelayDeviceDetailsComponent,
		RelayEndDeviceDetailsComponent,
		RouterAssignComponent,
		RouterDetailsComponent,
		SmoothPowerDetailsComponent,
		SmoothPowerNewComponent
	]
})
export class AppModule { }
