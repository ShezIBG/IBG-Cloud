import { DaliLight } from './dali-light';
import { RelayPin } from './relay-pin';
import { RelayEndDevice } from './relay-end-device';
import { AirconModel } from './aircon-model';
import { AirconManufacturer } from './aircon-manufacturer';
import { CoolPlug } from './coolplug';
import { CoolHub } from './coolhub';
import { CalculatedMeter } from './calculated-meter';
import { Entity } from './entity';
import { ABBMeter } from './abb-meter';
import { Area } from './area';
import { Breaker } from './breaker';
import { Building } from './building';
import { BuildingServer } from './building-server';
import { Category } from './category';
import { ConfiguratorHistory } from './configurator-history';
import { CTCategory } from './ct-category';
import { CT } from './ct';
import { DistBoard } from './distboard';
import { EmLightType } from './em-light-type';
import { EmLight } from './em-light';
import { FiberHeadendServer } from './fiber-headend-server';
import { FiberOLT } from './fiber-olt';
import { FiberONU } from './fiber-onu';
import { FiberONUType } from './fiber-onu-type';
import { Floor } from './floor';
import { FloorPlanAssignment } from './floorplan-assignment';
import { FloorPlanItem } from './floorplan-item';
import { FloorPlan } from './floorplan';
import { Gateway } from './gateway';
import { MBusCatalogue } from './mbus-catalogue';
import { MBusDevice } from './mbus-device';
import { MBusMaster } from './mbus-master';
import { Meter } from './meter';
import { PM12 } from './pm12';
import { Router } from './router';
import { RS485Catalogue } from './rs485-catalogue';
import { RS485Device } from './rs485-device';
import { Tenant } from './tenant';
import { TenantedArea } from './tenanted-area';
import { UserContent } from './user-content';
import { User } from './user';
import { Weather } from './weather';
import { RelayDevice } from './relay-device';
import { SmoothPower } from './smoothpower';

// tslint:disable:variable-name

export class EntityTypes {
	static Entity = 'entity';

	static ABBMeter = 'abb_meter';
	static AirconManufacturer = 'ac_manufacturer';
	static AirconModel = 'ac_model_series';
	static Area = 'area';
	static Breaker = 'breaker';
	static Building = 'building';
	static BuildingServer = 'building_server';
	static CalculatedMeter = 'calculated_meter';
	static Category = 'category';
	static ConfiguratorHistory = 'configurator_history';
	static CoolHub = 'coolhub';
	static CoolPlug = 'coolplug';
	static CTCategory = 'ct_category';
	static CT = 'ct';
	static DaliLight = 'dali_light';
	static DistBoard = 'dist_board';
	static EmLightType = 'em_light_type';
	static EmLight = 'em_light';
	static FiberHeadendServer = 'hes';
	static FiberOLT = 'olt';
	static FiberONU = 'onu';
	static FiberONUType = 'onu_type';
	static Floor = 'floor';
	static FloorPlanAssignment = 'floorplan_assignment';
	static FloorPlanItem = 'floorplan_item';
	static FloorPlan = 'floorplan';
	static Gateway = 'gateway';
	static MBusCatalogue = 'mbus_catalogue';
	static MBusDevice = 'mbus_device';
	static MBusMaster = 'mbus_master';
	static Meter = 'meter';
	static PM12 = 'pm12';
	static RelayDevice = 'relay_device';
	static RelayEndDevice = 'relay_end_device';
	static RelayPin = 'relay_pin';
	static Router = 'router';
	static RS485Catalogue = 'rs485_catalogue';
	static RS485Device = 'rs485';
	static SmoothPower = 'smoothpower';
	static Tenant = 'tenant';
	static TenantedArea = 'tenanted_area';
	static UserContent = 'user_content';
	static User = 'userdb';
	static Weather = 'weather';

	static isABBMeter(entity: Entity): entity is ABBMeter { return entity && entity.type === this.ABBMeter; }
	static isAirconManufacturer(entity: Entity): entity is AirconManufacturer { return entity && entity.type === this.AirconManufacturer; }
	static isAirconModel(entity: Entity): entity is AirconModel { return entity && entity.type === this.AirconModel; }
	static isArea(entity: Entity): entity is Area { return entity && entity.type === this.Area; }
	static isBreaker(entity: Entity): entity is Breaker { return entity && entity.type === this.Breaker; }
	static isBuilding(entity: Entity): entity is Building { return entity && entity.type === this.Building; }
	static isBuildingServer(entity: Entity): entity is BuildingServer { return entity && entity.type === this.BuildingServer; }
	static isCategory(entity: Entity): entity is Category { return entity && entity.type === this.Category; }
	static isConfiguratorHistory(entity: Entity): entity is ConfiguratorHistory { return entity && entity.type === this.ConfiguratorHistory; }
	static isCoolHub(entity: Entity): entity is CoolHub { return entity && entity.type === this.CoolHub; }
	static isCoolPlug(entity: Entity): entity is CoolPlug { return entity && entity.type === this.CoolPlug; }
	static isCTCategory(entity: Entity): entity is CTCategory { return entity && entity.type === this.CTCategory; }
	static isCT(entity: Entity): entity is CT { return entity && entity.type === this.CT; }
	static isDaliLight(entity: Entity): entity is DaliLight { return entity && entity.type === this.DaliLight; }
	static isDistBoard(entity: Entity): entity is DistBoard { return entity && entity.type === this.DistBoard; }
	static isEmLightType(entity: Entity): entity is EmLightType { return entity && entity.type === this.EmLightType; }
	static isEmLight(entity: Entity): entity is EmLight { return entity && entity.type === this.EmLight; }
	static isFiberHeadendServer(entity: Entity): entity is FiberHeadendServer { return entity && entity.type === this.FiberHeadendServer; }
	static isFiberOLT(entity: Entity): entity is FiberOLT { return entity && entity.type === this.FiberOLT; }
	static isFiberONU(entity: Entity): entity is FiberONU { return entity && entity.type === this.FiberONU; }
	static isFiberONUType(entity: Entity): entity is FiberONUType { return entity && entity.type === this.FiberONUType; }
	static isFloor(entity: Entity): entity is Floor { return entity && entity.type === this.Floor; }
	static isFloorPlanAssignment(entity: Entity): entity is FloorPlanAssignment { return entity && entity.type === this.FloorPlanAssignment; }
	static isFloorPlanItem(entity: Entity): entity is FloorPlanItem { return entity && entity.type === this.FloorPlanItem; }
	static isFloorPlan(entity: Entity): entity is FloorPlan { return entity && entity.type === this.FloorPlan; }
	static isGateway(entity: Entity): entity is Gateway { return entity && entity.type === this.Gateway; }
	static isMBusCatalogue(entity: Entity): entity is MBusCatalogue { return entity && entity.type === this.MBusCatalogue; }
	static isMBusDevice(entity: Entity): entity is MBusDevice { return entity && entity.type === this.MBusDevice; }
	static isMBusMaster(entity: Entity): entity is MBusMaster { return entity && entity.type === this.MBusMaster; }
	static isMeter(entity: Entity): entity is Meter { return entity && entity.type === this.Meter; }
	static isCalculatedMeter(entity: Entity): entity is CalculatedMeter { return entity && entity.type === this.CalculatedMeter; }
	static isPM12(entity: Entity): entity is PM12 { return entity && entity.type === this.PM12; }
	static isRelayDevice(entity: Entity): entity is RelayDevice { return entity && entity.type === this.RelayDevice; }
	static isRelayEndDevice(entity: Entity): entity is RelayEndDevice { return entity && entity.type === this.RelayEndDevice; }
	static isRelayPin(entity: Entity): entity is RelayPin { return entity && entity.type === this.RelayPin; }
	static isRouter(entity: Entity): entity is Router { return entity && entity.type === this.Router; }
	static isRS485Catalogue(entity: Entity): entity is RS485Catalogue { return entity && entity.type === this.RS485Catalogue; }
	static isRS485Device(entity: Entity): entity is RS485Device { return entity && entity.type === this.RS485Device; }
	static isSmoothPower(entity: Entity): entity is SmoothPower { return entity && entity.type === this.SmoothPower; }
	static isTenant(entity: Entity): entity is Tenant { return entity && entity.type === this.Tenant; }
	static isTenantedArea(entity: Entity): entity is TenantedArea { return entity && entity.type === this.TenantedArea; }
	static isUserContent(entity: Entity): entity is UserContent { return entity && entity.type === this.UserContent; }
	static isUser(entity: Entity): entity is User { return entity && entity.type === this.User; }
	static isWeather(entity: Entity): entity is Weather { return entity && entity.type === this.Weather; }
}
