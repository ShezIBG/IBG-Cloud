import { Router } from '@angular/router';
import { AppService } from './app.service';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../environments/environment';

declare var Mangler: any;

export class PermissionLevels {
	static readonly ETICOM = 'E';
	static readonly SERVICE_PROVIDER = 'SP';
	static readonly SYSTEM_INTEGRATOR = 'SI';
	static readonly HOLDING_GROUP = 'HG';
	static readonly CLIENT = 'C';
	static readonly BUILDING = 'B';
	static readonly AREA = 'A';

	static getDescription(level) {
		switch (level) {
			case this.ETICOM: return 'Eticom';
			case this.SERVICE_PROVIDER: return 'Service Provider';
			case this.SYSTEM_INTEGRATOR: return 'System Integrator';
			case this.HOLDING_GROUP: return 'Holding Group';
			case this.CLIENT: return 'Client';
			case this.BUILDING: return 'Site';
			case this.AREA: return 'Area';
		}
		return '';
	}

	static getPluralDescription(level) {
		switch (level) {
			case this.ETICOM: return 'Eticom';
			case this.SERVICE_PROVIDER: return 'Service Providers';
			case this.SYSTEM_INTEGRATOR: return 'System Integrators';
			case this.HOLDING_GROUP: return 'Holding Groups';
			case this.CLIENT: return 'Clients';
			case this.BUILDING: return 'Sites';
			case this.AREA: return 'Areas';
		}
		return '';
	}
}

@Injectable()
export class ApiService {

	serverURL = '../api/v3';

	public = {
		login: (email, password, rememberme, done, fail = null) => this.post('public/cloud_login', { email, password, rememberme }, done, fail),
		logout: (done, fail = null) => this.get('public/logout', done, fail),
		resetPassword: (email, done, fail = null) => this.post('public/reset_password', { email }, done, fail),
		checkResetToken: (token, done, fail = null) => this.get('public/check_reset_token?token=' + (token || ''), done, fail),
		updatePassword: (data, done, fail = null) => this.post('public/update_password', data, done, fail),
		getCustomerSignup: (data, done, fail = null) => this.post('public/get_customer_signup', data, done, fail),
		submitCustomerSignup: (data, done, fail = null) => this.post('public/submit_customer_signup', data, done, fail)
	};

	auth = {
		getBillingAccount: (done, fail = null) => this.get('auth/get_billing_account', done, fail),
		getCustomerMandateUrl: (done, fail = null) => this.get('auth/get_customer_mandate_url', done, fail)
	};

	general = {
		ping: (done = null, fail = null) => this.get('general/ping', done, fail),
		uploadUserContent: (formData, done, fail = null) => this.post('general/upload_user_content', formData, done, fail),
		uploadSmoothPowerUpdate: (formData, done, fail = null) => this.post('general/upload_smoothpower_update', formData, done, fail),
		uploadImage: (formData, maxWidth, maxHeight, done, fail = null) => this.post('general/upload_user_content?w=' + maxWidth + '&h=' + maxHeight, formData, done, fail),
		uploadImageURL: (url, maxWidth, maxHeight, done, fail = null) => this.post('general/upload_user_content_url?w=' + maxWidth + '&h=' + maxHeight, { url }, done, fail),

		getUIElementData: (name, done, fail = null) => this.get('general/get_ui_element_data?name=' + name, done, fail),
		saveUIElementData: (data, done, fail = null) => this.post('general/save_ui_element_data', data, done, fail),
		deleteUIElementData: (presetId, done, fail = null) => this.get('general/delete_ui_element_data?preset_id=' + presetId, done, fail)
	};

	emergency = {
		getOverview: (done, fail = null) => this.get('emergency/get_overview', done, fail),
		getBuilding: (id, done, fail = null) => this.get('emergency/get_building?id=' + id, done, fail),
		getBuildingGroups: (id, done, fail = null) => this.get('emergency/get_building_groups?id=' + id, done, fail),
		saveBuildingGroup: (data, done, fail = null) => this.post('emergency/save_building_group', data, done, fail),
		deleteBuildingGroup: (data, done, fail = null) => this.post('emergency/delete_building_group', data, done, fail),
		getBuildingLights: (id, done, fail = null) => this.get('emergency/get_building_lights?id=' + id, done, fail),
		getLight: (id, done, fail = null) => this.get('emergency/get_light?id=' + id, done, fail),
		saveLightSchedule: (data, done, fail = null) => this.post('emergency/save_light_schedule', data, done, fail),
		getBuildingFaults: (id, done, fail = null) => this.get('emergency/get_building_faults?id=' + id, done, fail)
	};

	settings = {
		getNavigation: (done, fail = null) => this.get('settings/get_navigation', done, fail),
		getEticom: (done, fail = null) => this.get('settings/get_eticom', done, fail),
		getServiceProvider: (id, done, fail = null) => this.get('settings/get_service_provider?id=' + id, done, fail),
		getSystemIntegrator: (id, done, fail = null) => this.get('settings/get_system_integrator?id=' + id, done, fail),
		getHoldingGroup: (id, done, fail = null) => this.get('settings/get_holding_group?id=' + id, done, fail),
		getClient: (id, done, fail = null) => this.get('settings/get_client?id=' + id, done, fail),
		getBuilding: (id, done, fail = null) => this.get('settings/get_building?id=' + id, done, fail),
		getUserRole: (id, done, fail = null) => this.get('settings/get_user_role?id=' + id, done, fail),
		getUserRoleDefaults: (done, fail = null) => this.get('settings/get_user_role_defaults', done, fail),
		getUser: (id, level, levelId, done, fail = null) => this.get('settings/get_user?id=' + id + '&level=' + (level || '') + '&level_id=' + (levelId || ''), done, fail),

		getSelectLevels: (done, fail = null) => this.get('settings/get_select_levels', done, fail),
		getPermissionLevelDetails: (level, id, done, fail = null) => this.get('settings/get_permission_level_details?level=' + level + '&id=' + id, done, fail),
		getUserIdByEmail: (email, done, fail = null) => this.post('settings/get_user_id_by_email', { email }, done, fail),
		getCurrentUserId: (done, fail = null) => this.get('settings/get_current_user_id', done, fail),
		getNewUserCrumbs: (level, id, done, fail = null) => this.get('settings/get_new_user_crumbs?level=' + level + '&id=' + id, done, fail),

		newServiceProvider: (done, fail = null) => this.get('settings/new_service_provider', done, fail),
		newSystemIntegrator: (level, id, done, fail = null) => this.get('settings/new_system_integrator?level=' + level + '&id=' + id, done, fail),
		newHoldingGroup: (level, id, done, fail = null) => this.get('settings/new_holding_group?level=' + level + '&id=' + id, done, fail),
		newClient: (level, id, done, fail = null) => this.get('settings/new_client?level=' + level + '&id=' + id, done, fail),
		newBuilding: (level, id, done, fail = null) => this.get('settings/new_building?level=' + level + '&id=' + id, done, fail),
		newUserRole: (level, id, done, fail = null) => this.get('settings/new_user_role?level=' + level + '&id=' + id, done, fail),
		newUser: (email, level, id, done, fail = null) => this.post('settings/new_user', { email, level, id }, done, fail),

		listAllServiceProviders: (done, fail = null) => this.get('settings/list_service_providers', done, fail),
		listAllSystemIntegrators: (done, fail = null) => this.get('settings/list_system_integrators', done, fail),
		listAllHoldingGroups: (done, fail = null) => this.get('settings/list_holding_groups', done, fail),
		listAllClients: (done, fail = null) => this.get('settings/list_clients', done, fail),
		listAllBuildings: (done, fail = null) => this.get('settings/list_buildings', done, fail),

		listSystemIntegrators: (filter, id, done, fail = null) => this.get('settings/list_system_integrators?filter=' + filter + '&id=' + id, done, fail),
		listHoldingGroups: (filter, id, done, fail = null) => this.get('settings/list_holding_groups?filter=' + filter + '&id=' + id, done, fail),
		listClients: (filter, id, done, fail = null) => this.get('settings/list_clients?filter=' + filter + '&id=' + id, done, fail),
		listBuildings: (filter, id, done, fail = null) => this.get('settings/list_buildings?filter=' + filter + '&id=' + id, done, fail),
		listAreas: (filter, id, done, fail = null) => this.get('settings/list_areas?filter=' + filter + '&id=' + id, done, fail),
		listUsers: (filter, id, showNoAccess, done, fail = null) => this.get('settings/list_users?filter=' + filter + '&id=' + id + '&no_access=' + (showNoAccess ? 1 : 0), done, fail),
		listAllUsers: (showNoAccess, done, fail = null) => this.get('settings/list_all_users?no_access=' + (showNoAccess ? 1 : 0), done, fail),
		listUserRoles: (filter, id, done, fail = null) => this.get('settings/list_user_roles?filter=' + filter + '&id=' + id, done, fail),
		listBuildingFloors: (id, done, fail = null) => this.get('settings/list_building_floors?id=' + id, done, fail),
		listFloorAreas: (id, done, fail = null) => this.get('settings/list_floor_areas?id=' + id, done, fail),

		saveServiceProvider: (data, done, fail = null) => this.post('settings/save_service_provider', data, done, fail),
		saveSystemIntegrator: (data, done, fail = null) => this.post('settings/save_system_integrator', data, done, fail),
		saveHoldingGroup: (data, done, fail = null) => this.post('settings/save_holding_group', data, done, fail),
		saveClient: (data, done, fail = null) => this.post('settings/save_client', data, done, fail),
		saveBuilding: (data, done, fail = null) => this.post('settings/save_building', data, done, fail),
		saveUserRole: (data, done, fail = null) => this.post('settings/save_user_role', data, done, fail),
		saveUserRoleDefaults: (data, done, fail = null) => this.post('settings/save_user_role_defaults', data, done, fail),
		saveUser: (data, done, fail = null) => this.post('settings/save_user', data, done, fail),

		listPaymentGateways: (owner_type, owner_id, done, fail = null) => this.get('settings/list_payment_gateways?owner_type=' + owner_type + '&owner_id=' + owner_id, done, fail),
		newPaymentGateway: (owner_type, owner_id, type, done, fail = null) => this.get('settings/new_payment_gateway?owner_type=' + owner_type + '&owner_id=' + owner_id + '&type=' + type, done, fail),
		getPaymentGateway: (id, done, fail = null) => this.get('settings/get_payment_gateway?id=' + id, done, fail),
		savePaymentGateway: (id, data, done, fail = null) => this.post('settings/save_payment_gateway?id=' + id, data, done, fail),
		authorisePaymentGateway: (id, done, fail = null) => this.get('settings/authorise_payment_gateway?id=' + id, done, fail),

		listEmailTemplates: (owner_type, owner_id, done, fail = null) => this.get('settings/list_email_templates?owner_type=' + owner_type + '&owner_id=' + owner_id, done, fail),
		updateSMTP: (data, done, fail = null) => this.post('settings/update_smtp', data, done, fail),
		getEmailTemplate: (owner_type, owner_id, template, done, fail = null) => this.get('settings/get_email_template?owner_type=' + owner_type + '&owner_id=' + owner_id + '&template=' + template, done, fail),
		saveEmailTemplate: (data, done, fail = null) => this.post('settings/save_email_template', data, done, fail),
		deleteEmailTemplate: (owner_type, owner_id, template, done, fail = null) => this.get('settings/delete_email_template?owner_type=' + owner_type + '&owner_id=' + owner_id + '&template=' + template, done, fail),

		listContractTemplates: (owner_type, owner_id, done, fail = null) => this.get('settings/list_contract_templates?owner_type=' + owner_type + '&owner_id=' + owner_id, done, fail),
		newContractTemplate: (owner_type, owner_id, done, fail = null) => this.get('settings/new_contract_template?owner_type=' + owner_type + '&owner_id=' + owner_id, done, fail),
		getContractTemplate: (owner_type, owner_id, id, done, fail = null) => this.get('settings/get_contract_template?owner_type=' + owner_type + '&owner_id=' + owner_id + '&id=' + id, done, fail),
		saveContractTemplate: (data, done, fail = null) => this.post('settings/save_contract_template', data, done, fail),
		deleteContractTemplate: (owner_type, owner_id, id, done, fail = null) => this.get('settings/delete_contract_template?owner_type=' + owner_type + '&owner_id=' + owner_id + '&id=' + id, done, fail),

		listSmoothPowerUpdates: (done, fail = null) => this.get('settings/list_smoothpower_updates', done, fail),
		setSmoothPowerRollback: (id, value, done, fail = null) => this.get('settings/set_smoothpower_rollback?id=' + id + '&value=' + value, done, fail),
		setSmoothPowerChannel: (id, value, done, fail = null) => this.get('settings/set_smoothpower_channel?id=' + id + '&value=' + value, done, fail),
		deleteSmoothPowerUpdate: (id, done, fail = null) => this.get('settings/delete_smoothpower_update?id=' + id, done, fail)
	};

	monitor = {
		getCollectorOverview: (done, fail = null) => this.get('monitor/get_collector_overview', done, fail),
		getCollectors: (filter, done, fail = null) => this.get('monitor/get_collectors?filter=' + filter, done, fail),
		getCollectorHistory: (id, done, fail = null) => this.get('monitor/get_collector_history?id=' + id, done, fail),
		setCollectorIgnoreFlag: (id, flag, done, fail = null) => this.get('monitor/set_collector_ignore_flag?id=' + id + '&flag=' + flag, done, fail)
	};

	products = {
		listEntities: (productOwner, filters, done, fail = null) => this.post('products/list_entities?product_owner=' + (productOwner || ''), filters, done, fail),
		listCategories: (productOwner, done, fail = null) => this.get('products/list_categories?product_owner=' + (productOwner || ''), done, fail),
		listTagGroups: (productOwner, done, fail = null) => this.get('products/list_tag_groups?product_owner=' + (productOwner || ''), done, fail),
		listBaseUnits: (productOwner, done, fail = null) => this.get('products/list_base_units?product_owner=' + (productOwner || ''), done, fail),
		listLabourTypes: (productOwner, done, fail = null) => this.get('products/list_labour_types?product_owner=' + (productOwner || ''), done, fail),
		listSubscriptionTypes: (productOwner, done, fail = null) => this.get('products/list_subscription_types?product_owner=' + (productOwner || ''), done, fail),
		listPricingStructures: (productOwner, done, fail = null) => this.get('products/list_pricing_structures?product_owner=' + (productOwner || ''), done, fail),
		listProducts: (options, done, fail = null) => this.get('products/list_products?' + this.objectToQueryString(options), done, fail),
		listResellers: (productOwner, done, fail = null) => this.get('products/list_resellers?product_owner=' + (productOwner || ''), done, fail),

		getEntity: (id, done, fail = null) => this.get('products/get_entity?id=' + id, done, fail),
		getCategory: (id, done, fail = null) => this.get('products/get_category?id=' + id, done, fail),
		getTagGroup: (id, done, fail = null) => this.get('products/get_tag_group?id=' + id, done, fail),
		getBaseUnit: (id, done, fail = null) => this.get('products/get_base_unit?id=' + id, done, fail),
		getLabourType: (id, done, fail = null) => this.get('products/get_labour_type?id=' + id, done, fail),
		getLabourCategory: (id, done, fail = null) => this.get('products/get_labour_category?id=' + id, done, fail),
		getSubscriptionType: (id, owner, done, fail = null) => this.get('products/get_subscription_type?id=' + id + '&product_owner=' + owner, done, fail),
		getSubscriptionCategory: (id, done, fail = null) => this.get('products/get_subscription_category?id=' + id, done, fail),
		getPricingStructure: (id, done, fail = null) => this.get('products/get_pricing_structure?id=' + id, done, fail),
		getProduct: (id, owner, done, fail = null) => this.get('products/get_product?id=' + id + '&product_owner=' + owner, done, fail),
		getBomProduct: (id, owner, productId, done, fail = null) => this.get('products/get_bom_product?id=' + id + '&product_id=' + productId + '&product_owner=' + owner, done, fail),
		getReseller: (owner, reseller, done, fail = null) => this.get('products/get_reseller?owner=' + owner + '&reseller=' + reseller, done, fail),

		newEntity: (productOwner, done, fail = null) => this.get('products/new_entity?product_owner=' + (productOwner || ''), done, fail),
		newCategory: (productOwner, done, fail = null) => this.get('products/new_category?product_owner=' + (productOwner || ''), done, fail),
		newTagGroup: (productOwner, done, fail = null) => this.get('products/new_tag_group?product_owner=' + (productOwner || ''), done, fail),
		newBaseUnit: (productOwner, done, fail = null) => this.get('products/new_base_unit?product_owner=' + (productOwner || ''), done, fail),
		newLabourType: (productOwner, done, fail = null) => this.get('products/new_labour_type?product_owner=' + (productOwner || ''), done, fail),
		newSubscriptionType: (productOwner, done, fail = null) => this.get('products/new_subscription_type?product_owner=' + (productOwner || ''), done, fail),
		newPricingStructure: (productOwner, done, fail = null) => this.get('products/new_pricing_structure?product_owner=' + (productOwner || ''), done, fail),
		newProduct: (productOwner, done, fail = null) => this.get('products/new_product?product_owner=' + (productOwner || ''), done, fail),
		newReseller: (productOwner, done, fail = null) => this.get('products/new_reseller?product_owner=' + (productOwner || ''), done, fail),

		saveEntity: (data, done, fail = null) => this.post('products/save_entity', data, done, fail),
		saveCategory: (data, done, fail = null) => this.post('products/save_category', data, done, fail),
		saveTagGroup: (data, done, fail = null) => this.post('products/save_tag_group', data, done, fail),
		saveBaseUnit: (data, done, fail = null) => this.post('products/save_base_unit', data, done, fail),
		saveLabourType: (data, done, fail = null) => this.post('products/save_labour_type', data, done, fail),
		saveLabourCategory: (data, done, fail = null) => this.post('products/save_labour_category', data, done, fail),
		saveSubscriptionType: (owner, data, done, fail = null) => this.post('products/save_subscription_type?product_owner=' + owner, data, done, fail),
		saveSubscriptionCategory: (data, done, fail = null) => this.post('products/save_subscription_category', data, done, fail),
		savePricingStructure: (data, done, fail = null) => this.post('products/save_pricing_structure', data, done, fail),
		saveProduct: (owner, data, done, fail = null) => this.post('products/save_product?product_owner=' + owner, data, done, fail),
		saveReseller: (data, done, fail = null) => this.post('products/save_reseller', data, done, fail),

		archiveEntity: (id, done, fail = null) => this.get('products/archive_entity?id=' + id, done, fail),
		unarchiveEntity: (id, done, fail = null) => this.get('products/unarchive_entity?id=' + id, done, fail),

		deleteCategory: (id, done, fail = null) => this.get('products/delete_category?id=' + id, done, fail),
		deleteTagGroup: (id, done, fail = null) => this.get('products/delete_tag_group?id=' + id, done, fail),
		deletePricingStructure: (id, done, fail = null) => this.get('products/delete_pricing_structure?id=' + id, done, fail),
		deleteReseller: (owner, reseller, done, fail = null) => this.get('products/delete_reseller?owner=' + owner + '&reseller=' + reseller, done, fail),
		deleteLabourCategory: (id, done, fail = null) => this.get('products/delete_labour_category?id=' + id, done, fail),
		deleteSubscriptionCategory: (id, done, fail = null) => this.get('products/delete_subscription_category?id=' + id, done, fail),

		cloneProduct: (data, done, fail = null) => this.post('products/clone_product', data, done, fail),
		setDefaultUnit: (productOwner, unitId, done, fail = null) => this.get('products/set_default_unit?product_owner=' + (productOwner || '') + '&unit_id=' + (unitId || ''), done, fail),
	};

	sales = {
		getNavigation: (done, fail = null) => this.get('sales/get_navigation', done, fail),
		getOverview: (productOwner, done, fail = null) => this.get('sales/get_overview?product_owner=' + (productOwner || ''), done, fail),
		getCustomer: (id, done, fail = null) => this.get('sales/get_customer?id=' + id, done, fail),
		getProject: (id, done, fail = null) => this.get('sales/get_project?id=' + id, done, fail),
		getStructure: (id, done, fail = null) => this.get('sales/get_structure?id=' + id, done, fail),
		getSystem: (id, si, done, fail = null) => this.get('sales/get_system?id=' + id + '&si=' + si, done, fail),
		getProjectLine: (id, done, fail = null) => this.get('sales/get_project_line?id=' + id, done, fail),
		getProjectPriceAdjustments: (id, done, fail = null) => this.get('sales/get_project_price_adjustments?id=' + id, done, fail),
		getProjectSystem: (id, done, fail = null) => this.get('sales/get_project_system?id=' + id, done, fail),
		getProjectModule: (id, done, fail = null) => this.get('sales/get_project_module?id=' + id, done, fail),

		listCustomers: (productOwner, done, fail = null) => this.get('sales/list_customers?product_owner=' + (productOwner || ''), done, fail),
		listProjects: (productOwner, filters, done, fail = null) => this.post('sales/list_projects?product_owner=' + (productOwner || ''), filters, done, fail),
		listProjectSystems: (productOwner, done, fail = null) => this.get('sales/list_project_systems?product_owner=' + (productOwner || ''), done, fail),

		newCustomer: (productOwner, done, fail = null) => this.get('sales/new_customer?product_owner=' + (productOwner || ''), done, fail),
		newCustomerLists: (siId, done, fail = null) => this.get('sales/new_customer_lists?si=' + siId, done, fail),
		newProject: (productOwner, done, fail = null) => this.get('sales/new_project?product_owner=' + (productOwner || ''), done, fail),
		newProjectLists: (siId, done, fail = null) => this.get('sales/new_project_lists?si=' + siId, done, fail),
		newStructure: (data, done, fail = null) => this.post('sales/new_structure', data, done, fail),
		newProjectSystem: (productOwner, done, fail = null) => this.get('sales/new_project_system?product_owner=' + (productOwner || ''), done, fail),
		newProjectModule: (productOwner, done, fail = null) => this.get('sales/new_project_module?product_owner=' + (productOwner || ''), done, fail),

		saveCustomer: (data, done, fail = null) => this.post('sales/save_customer', data, done, fail),
		saveProject: (data, done, fail = null) => this.post('sales/save_project', data, done, fail),
		saveStructure: (data, done, fail = null) => this.post('sales/save_structure', data, done, fail),
		saveSystem: (data, done, fail = null) => this.post('sales/save_system', data, done, fail),
		saveProjectLine: (data, done, fail = null) => this.post('sales/save_project_line', data, done, fail),
		saveProjectProposal: (data, done, fail = null) => this.post('sales/save_project_proposal', data, done, fail),
		saveProjectPriceAdjustments: (id, data, done, fail = null) => this.post('sales/save_project_price_adjustments?id=' + id, data, done, fail),
		saveProjectSystem: (data, done, fail = null) => this.post('sales/save_project_system', data, done, fail),
		saveProjectModule: (data, done, fail = null) => this.post('sales/save_project_module', data, done, fail),

		deleteStructure: (id, done, fail = null) => this.get('sales/delete_structure?id=' + id, done, fail),
		deleteProjectLine: (id, done, fail = null) => this.get('sales/delete_project_line?id=' + id, done, fail),

		getProjectLines: (data, done, fail = null) => this.post('sales/get_project_lines', data, done, fail),
		getProjectSystems: (id, done, fail = null) => this.get('sales/get_project_systems?id=' + id, done, fail),
		updateProjectSystems: (id, add, remove, done, fail = null) => this.post('sales/update_project_systems', { id, add, remove }, done, fail),
		addProjectLine: (data, done, fail = null) => this.post('sales/add_project_line', data, done, fail),
		newProjectLine: (data, done, fail = null) => this.post('sales/new_project_line', data, done, fail),
		increaseProjectLine: (data, done, fail = null) => this.post('sales/increase_project_line', data, done, fail),
		copyProjectLine: (id, done, fail = null) => this.get('sales/copy_project_line?id=' + id, done, fail),
		cloneProject: (data, done, fail = null) => this.post('sales/clone_project', data, done, fail),

		getProjectSummary: (id, done, fail = null) => this.get('sales/get_project_summary?id=' + id, done, fail),
		getProjectCostSummary: (id, done, fail = null) => this.get('sales/get_project_cost_summary?id=' + id, done, fail),
		getProjectPORequest: (id, done, fail = null) => this.get('sales/get_project_po_request?id=' + id, done, fail),
		getProjectItemisedQuotation: (id, done, fail = null) => this.get('sales/get_project_itemised_quotation?id=' + id, done, fail),
		getProjectProposal: (id, done, fail = null) => this.get('sales/get_project_proposal?id=' + id, done, fail),
		getProjectStageHistory: (id, done, fail = null) => this.get('sales/get_project_stage_history?id=' + id, done, fail),

		moveProjectModuleUp: (id, done, fail = null) => this.get('sales/move_project_module_up?id=' + id, done, fail),
		moveProjectModuleDown: (id, done, fail = null) => this.get('sales/move_project_module_down?id=' + id, done, fail),

		getUnsyncedProjectLines: (id, done, fail = null) => this.get('sales/get_unsynced_project_lines?id=' + id, done, fail),
		syncProjectLines: (id, done, fail = null) => this.get('sales/sync_project_lines?id=' + id, done, fail)
	};

	isp = {
		newCustomer: (isp, done, fail = null) => this.get('isp/new_customer?isp=' + isp, done, fail),
		newContract: (isp, customer, template, done, fail = null) => this.get('isp/new_contract?isp=' + isp + '&customer=' + customer + '&template=' + template, done, fail),

		getFirstIspId: (done, fail = null) => this.get('isp/get_first_isp_id', done, fail),
		getOverview: (isp, done, fail = null) => this.get('isp/get_overview?isp=' + isp, done, fail),
		getNavigation: (isp, done, fail = null) => this.get('isp/get_navigation?isp=' + isp, done, fail),
		getClient: (id, done, fail = null) => this.get('isp/get_client?id=' + id, done, fail),
		getBuilding: (id, done, fail = null) => this.get('isp/get_building?id=' + id, done, fail),
		getCustomer: (id, done, fail = null) => this.get('isp/get_customer?id=' + id, done, fail),
		getPackage: (id, done, fail = null) => this.get('isp/get_package?id=' + id, done, fail),
		getContract: (id, done, fail = null) => this.get('isp/get_contract?id=' + id, done, fail),
		getArea: (id, done, fail = null) => this.get('isp/get_area?id=' + id, done, fail),
		getInvoice: (id, done, fail = null) => this.get('isp/get_invoice?id=' + id, done, fail),
		getInvoiceCounter: (ownerType, ownerId, done, fail = null) => this.get('isp/get_invoice_counter?owner_type=' + ownerType + '&owner_id=' + ownerId, done, fail),

		setOnuPackage: (onu, pkg, done, fail = null) => this.get('isp/set_onu_package?onu=' + onu + '&pkg=' + (pkg || ''), done, fail),
		setWiFiSettings: (data, done, fail = null) => this.post('isp/set_wifi_settings', data, done, fail),
		pendWiFiSettings: (data, done, fail = null) => this.post('isp/pending_wifi_settings',data,done,fail),
		todoWiFiSettings: (data, done, fail = null) => this.post('isp/todo_wifi_settings', data, done, fail),
		cancelWiFiSettings: (data, done, fail = null) => this.post('isp/cancel_wifi_settings', data, done, fail),
		rebootOnu: (onu, done, fail = null) => this.get('isp/reboot_onu?onu=' + onu, done, fail),

		saveCustomer: (data, done, fail = null) => this.post('isp/save_customer', data, done, fail),
		saveContract: (data, done, fail = null) => this.post('isp/save_contract', data, done, fail),
		savePackage: (data, done, fail = null) => this.post('isp/save_package', data, done, fail),
		saveInvoiceCounter: (data, done, fail = null) => this.post('isp/save_invoice_counter', data, done, fail),

		listClients: (data, done, fail = null) => this.post('isp/list_clients', data, done, fail),
		listBuildings: (data, done, fail = null) => this.post('isp/list_buildings', data, done, fail),
		listCustomers: (data, done, fail = null) => this.post('isp/list_customers', data, done, fail),
		listPackages: (data, done, fail = null) => this.post('isp/list_packages', data, done, fail),
		listAreas: (data, done, fail = null) => this.post('isp/list_areas', data, done, fail),
		listContracts: (data, done, fail = null) => this.post('isp/list_contracts', data, done, fail),
		listContractTemplates: (data, done, fail = null) => this.post('isp/list_contract_templates', data, done, fail),
		listInvoices: (data, done, fail = null) => this.post('isp/list_invoices', data, done, fail),

		approveInvoice: (id, done, fail = null) => this.get('isp/approve_invoice?id=' + id, done, fail),
		resendInvoiceEmail: (id, done, fail = null) => this.get('isp/resend_invoice_email?id=' + id, done, fail),
		setInvoiceStatus: (id, status, done, fail = null) => this.get('isp/set_invoice_status?id=' + id + '&status=' + status, done, fail),
		setInvoiceNo: (id, invoiceNo, done, fail = null) => this.get('isp/set_invoice_no?id=' + id + '&invoice_no=' + invoiceNo, done, fail),
		newTransaction: (data, done, fail = null) => this.post('isp/new_transaction', data, done, fail),
		cancelTransaction: (id, done, fail = null) => this.get('isp/cancel_transaction?id=' + id, done, fail),
		deleteCard: (paymentGatewayId, customerType, customerId, done, fail = null) => this.get('isp/delete_card?payment_gateway_id=' + paymentGatewayId + '&customer_type=' + customerType + '&customer_id=' + customerId, done, fail),
		cancelMandate: (paymentGatewayId, customerType, customerId, done, fail = null) => this.get('isp/cancel_mandate?payment_gateway_id=' + paymentGatewayId + '&customer_type=' + customerType + '&customer_id=' + customerId, done, fail),
		sendCustomerEmail: (data, done, fail = null) => this.post('isp/send_customer_email', data, done, fail),

		updateAreaNote: (data, done, fail = null) => this.post('isp/update_area_note', data, done, fail),

		listOnuTypes: (buildingId, done, fail = null) => this.get('isp/list_onu_types?id=' + buildingId, done, fail),
		copyOnuTypes: (buildingId, targetId, done, fail = null) => this.get('isp/copy_onu_types?id=' + buildingId + '&target_id=' + targetId, done, fail),
		newOnuType: (buildingId, done, fail = null) => this.get('isp/new_onu_type?id=' + buildingId, done, fail),
		getOnuType: (id, done, fail = null) => this.get('isp/get_onu_type?id=' + id, done, fail),
		saveOnuType: (data, done, fail = null) => this.post('isp/save_onu_type', data, done, fail),
		deleteOnuType: (id, done, fail = null) => this.get('isp/delete_onu_type?id=' + id, done, fail)
	};

	billing = {
		newCustomer: (owner, done, fail = null) => this.get('billing/new_customer?owner=' + owner, done, fail),
		newInvoiceEntity: (owner, done, fail = null) => this.get('billing/new_invoice_entity?owner=' + owner, done, fail),
		newContract: (owner, customerType, customerId, template, done, fail = null) => this.get('billing/new_contract?owner=' + owner + '&customer_type=' + customerType + '&customer_id=' + customerId + '&template=' + template, done, fail),

		getFirstOwner: (done, fail = null) => this.get('billing/get_first_owner', done, fail),
		getOverview: (owner, done, fail = null) => this.get('billing/get_overview?owner=' + owner, done, fail),
		getNavigation: (owner, done, fail = null) => this.get('billing/get_navigation?owner=' + owner, done, fail),
		getSystemIntegrator: (owner, id, done, fail = null) => this.get('billing/get_system_integrator?owner=' + owner + '&id=' + id, done, fail),
		getClient: (owner, id, done, fail = null) => this.get('billing/get_client?owner=' + owner + '&id=' + id, done, fail),
		getBuilding: (owner, id, done, fail = null) => this.get('billing/get_building?owner=' + owner + '&id=' + id, done, fail),
		getCustomer: (owner, id, done, fail = null) => this.get('billing/get_customer?owner=' + owner + '&id=' + id, done, fail),
		getInvoiceEntity: (owner, id, done, fail = null) => this.get('billing/get_invoice_entity?owner=' + owner + '&id=' + id, done, fail),
		getContract: (id, done, fail = null) => this.get('billing/get_contract?id=' + id, done, fail),
		getArea: (owner, id, done, fail = null) => this.get('billing/get_area?owner=' + owner + '&id=' + id, done, fail),
		getInvoice: (id, done, fail = null) => this.get('billing/get_invoice?id=' + id, done, fail),
		getInvoiceCounter: (ownerType, ownerId, done, fail = null) => this.get('billing/get_invoice_counter?owner_type=' + ownerType + '&owner_id=' + ownerId, done, fail),

		saveCustomer: (data, done, fail = null) => this.post('billing/save_customer', data, done, fail),
		saveInvoiceEntity: (data, done, fail = null) => this.post('billing/save_invoice_entity', data, done, fail),
		saveContract: (data, done, fail = null) => this.post('billing/save_contract', data, done, fail),
		saveInvoiceCounter: (data, done, fail = null) => this.post('billing/save_invoice_counter', data, done, fail),

		listSystemIntegrators: (data, done, fail = null) => this.post('billing/list_system_integrators', data, done, fail),
		listClients: (data, done, fail = null) => this.post('billing/list_clients', data, done, fail),
		listBuildings: (data, done, fail = null) => this.post('billing/list_buildings', data, done, fail),
		listCustomers: (data, done, fail = null) => this.post('billing/list_customers', data, done, fail),
		listCustomersInArrears: (data, done, fail = null) => this.post('billing/list_customers_in_arrears', data, done, fail),
		listInvoiceEntities: (data, done, fail = null) => this.post('billing/list_invoice_entities', data, done, fail),
		listAreas: (data, done, fail = null) => this.post('billing/list_areas', data, done, fail),
		listContracts: (data, done, fail = null) => this.post('billing/list_contracts', data, done, fail),
		listContractTemplates: (data, done, fail = null) => this.post('billing/list_contract_templates', data, done, fail),
		listInvoices: (data, done, fail = null) => this.post('billing/list_invoices', data, done, fail),

		createSystemIntegratorAccount: (owner, id, done, fail = null) => this.get('billing/create_system_integrator_account?owner=' + owner + '&id=' + id, done, fail),
		createClientAccount: (owner, id, done, fail = null) => this.get('billing/create_client_account?owner=' + owner + '&id=' + id, done, fail),

		approveInvoice: (id, done, fail = null) => this.get('billing/approve_invoice?id=' + id, done, fail),
		resendInvoiceEmail: (id, done, fail = null) => this.get('billing/resend_invoice_email?id=' + id, done, fail),
		setInvoiceStatus: (id, status, done, fail = null) => this.get('billing/set_invoice_status?id=' + id + '&status=' + status, done, fail),
		setInvoiceNo: (id, invoiceNo, done, fail = null) => this.get('billing/set_invoice_no?id=' + id + '&invoice_no=' + invoiceNo, done, fail),
		newTransaction: (data, done, fail = null) => this.post('billing/new_transaction', data, done, fail),
		cancelTransaction: (id, done, fail = null) => this.get('billing/cancel_transaction?id=' + id, done, fail),
		deleteCard: (paymentGatewayId, customerType, customerId, done, fail = null) => this.get('billing/delete_card?payment_gateway_id=' + paymentGatewayId + '&customer_type=' + customerType + '&customer_id=' + customerId, done, fail),
		cancelMandate: (paymentGatewayId, customerType, customerId, done, fail = null) => this.get('billing/cancel_mandate?payment_gateway_id=' + paymentGatewayId + '&customer_type=' + customerType + '&customer_id=' + customerId, done, fail),
		sendCustomerEmail: (data, done, fail = null) => this.post('billing/send_customer_email', data, done, fail)
	};

	account = {
		getDetails: (id, token, done, fail = null) => this.get('account/get_details?id=' + id + '&token=' + token, done, fail),
		addCard: (id, token, gatewayId, amountPence, done, fail = null) => this.get('account/add_card?id=' + id + '&token=' + token + '&gateway=' + gatewayId + '&amount_pence=' + amountPence, done, fail),
		payBySavedCard: (id, token, gatewayId, amountPence, done, fail = null) => this.get('account/pay_by_saved_card?id=' + id + '&token=' + token + '&gateway=' + gatewayId + '&amount_pence=' + amountPence, done, fail),
		getCustomerMandateUrl: (id, token, gateway, done, fail = null) => this.get('account/get_customer_mandate_url?id=' + id + '&token=' + token + '&gateway=' + gateway, done, fail),
		signContract: (data, done, fail = null) => this.post('account/sign_contract', data, done, fail),
		checkCancelDate: (data, done, fail = null) => this.post('account/check_cancel_date', data, done, fail),
		cancelContract: (data, done, fail = null) => this.post('account/cancel_contract', data, done, fail),
		getUpgradeInfo: (data, done, fail = null) => this.post('account/get_upgrade_info', data, done, fail),
		upgradeBySavedCard: (id, token, gatewayId, contractId, packageId, done, fail = null) => this.get('account/upgrade_by_saved_card?id=' + id + '&token=' + token + '&gateway=' + gatewayId + '&contract=' + contractId + '&package=' + packageId, done, fail),
		upgradeByNewCard: (id, token, gatewayId, contractId, packageId, done, fail = null) => this.get('account/upgrade_by_new_card?id=' + id + '&token=' + token + '&gateway=' + gatewayId + '&contract=' + contractId + '&package=' + packageId, done, fail),
		getSupportInfo: (data, done, fail = null) => this.post('account/get_support_info', data, done, fail),
		fixMyInternet: (data, done, fail = null) => this.post('account/fix_my_internet', data, done, fail)
	};

	climate = {
		getOverview: (done, fail = null) => this.get('climate/get_overview', done, fail),
		getBuilding: (id, done, fail = null) => this.get('climate/get_building?id=' + id, done, fail),
		getBuildingSchedules: (id, done, fail = null) => this.get('climate/get_building_schedules?id=' + id, done, fail),

		getDevice: (id, done, fail = null) => this.get('climate/get_device?id=' + id, done, fail),
		getDeviceDetails: (id, done, fail = null) => this.get('climate/get_device_details?id=' + id, done, fail),
		listDevices: (buildingId, done, fail = null) => this.get('climate/list_devices?building_id=' + buildingId, done, fail),
		setHold: (data, done, fail = null) => this.post('climate/set_hold', data, done, fail),
		removeHold: (plugId, done, fail = null) => this.get('climate/remove_hold?id=' + plugId, done, fail),
		createHold: (data, done, fail = null) => this.post('climate/create_hold', data, done, fail),
		deleteHold: (id, done, fail = null) => this.get('climate/delete_hold?id=' + id, done, fail),

		getSchedule: (id, done, fail = null) => this.get('climate/get_schedule?id=' + id, done, fail),
		addSchedule: (data, done, fail = null) => this.post('climate/add_schedule', data, done, fail),
		saveSchedule: (data, done, fail = null) => this.post('climate/save_schedule', data, done, fail),
		deleteSchedule: (id, done, fail = null) => this.get('climate/delete_schedule?id=' + id, done, fail),
		updateDeviceSchedule: (id, scheduleId, done, fail = null) => this.get('climate/update_device_schedule?id=' + id + '&schedule=' + (scheduleId || 0), done, fail)
	};

	relay = {
		getOverview: (done, fail = null) => this.get('relay/get_overview', done, fail),
		getBuilding: (id, done, fail = null) => this.get('relay/get_building?id=' + id, done, fail),
		getBuildingSchedules: (id, done, fail = null) => this.get('relay/get_building_schedules?id=' + id, done, fail),

		getDevice: (id, done, fail = null) => this.get('relay/get_device?id=' + id, done, fail),
		getDeviceDetails: (id, done, fail = null) => this.get('relay/get_device_details?id=' + id, done, fail),
		listDevices: (buildingId, done, fail = null) => this.get('relay/list_devices?building_id=' + buildingId, done, fail),

		getSchedule: (id, done, fail = null) => this.get('relay/get_schedule?id=' + id, done, fail),
		addSchedule: (data, done, fail = null) => this.post('relay/add_schedule', data, done, fail),
		saveSchedule: (data, done, fail = null) => this.post('relay/save_schedule', data, done, fail),
		deleteSchedule: (id, done, fail = null) => this.get('relay/delete_schedule?id=' + id, done, fail),
		updateDeviceSchedule: (id, scheduleId, done, fail = null) => this.get('relay/update_device_schedule?id=' + id + '&schedule=' + (scheduleId || 0), done, fail),

		// Unused
		setHold: (data, done, fail = null) => this.post('climate/set_hold', data, done, fail),
		removeHold: (plugId, done, fail = null) => this.get('climate/remove_hold?id=' + plugId, done, fail),
		createHold: (data, done, fail = null) => this.post('climate/create_hold', data, done, fail),
		deleteHold: (id, done, fail = null) => this.get('climate/delete_hold?id=' + id, done, fail)
	};

	stock = {
		getNavigation: (done, fail = null) => this.get('stock/get_navigation', done, fail),

		listWarehouses: (productOwner, done, fail = null) => this.get('stock/list_warehouses?product_owner=' + (productOwner || ''), done, fail),
		getWarehouse: (id, done, fail = null) => this.get('stock/get_warehouse?id=' + id, done, fail),
		newWarehouse: (productOwner, done, fail = null) => this.get('stock/new_warehouse?product_owner=' + (productOwner || ''), done, fail),
		saveWarehouse: (data, done, fail = null) => this.post('stock/save_warehouse', data, done, fail),
		deleteWarehouse: (id, done, fail = null) => this.get('stock/delete_warehouse?id=' + id, done, fail),

		listStockLocations: (id, done, fail = null) => this.get('stock/list_stock_locations?id=' + id, done, fail),
		createStockLocation: (data, done, fail = null) => this.post('stock/create_stock_location', data, done, fail),
		deleteStockLocation: (id, done, fail = null) => this.get('stock/delete_stock_location?id=' + id, done, fail),
		setLocationLabel: (warehouse_id, rack, bay, level, label, done, fail = null) => this.post('stock/set_location_label', { warehouse_id, rack, bay, level, label }, done, fail),

		getStockProductInfo: (product_id, warehouse_id, done, fail = null) => this.get('stock/get_stock_product_info?id=' + product_id + '&warehouse=' + warehouse_id, done, fail),
		submitGoodsIn: (data, done, fail = null) => this.post('stock/submit_goods_in', data, done, fail),
		submitGoodsOut: (data, done, fail = null) => this.post('stock/submit_goods_out', data, done, fail),
		listStockByProduct: (warehouse_id, done, fail = null) => this.get('stock/list_stock_by_product?warehouse_id=' + warehouse_id, done, fail),
		listStockByLocation: (warehouse_id, done, fail = null) => this.get('stock/list_stock_by_location?warehouse_id=' + warehouse_id, done, fail),

		listSmoothPowerUnits: (productOwner, done, fail = null) => this.get('stock/list_smoothpower_units?product_owner=' + (productOwner || ''), done, fail)
	};

	smoothpower = {
		getUnitStatus: (id, done, fail = null) => this.get('smoothpower/get_unit_status?id=' + id, done, fail),
		uninstallUnit: (id, done, fail = null) => this.get('smoothpower/uninstall_unit?id=' + id, done, fail),
		getSmoothPowerUnit: (id, done, fail = null) => this.get('smoothpower/get_smoothpower_unit?id=' + id, done, fail),
		saveSmoothPowerUnit: (data, done, fail = null) => this.post('smoothpower/save_smoothpower_unit', data, done, fail),
		installUnit: (data, done, fail = null) => this.post('/smoothpower/install_unit', data, done, fail)
	}

	lighting = {
		getOverview: (done, fail = null) => this.get('lighting/get_overview', done, fail),
		getBuilding: (buildingId, done, fail = null) => this.get('lighting/get_building?id=' + buildingId, done, fail),
		getBuildingSchedules: (buildingId, done, fail = null) => this.get('lighting/get_building_schedules?id=' + buildingId, done, fail),

		getDeviceDetails: (buildingId, id, done, fail = null) => this.get('lighting/get_device_details?building_id=' + buildingId + '&id=' + id, done, fail),
		listDevices: (buildingId, done, fail = null) => this.get('lighting/list_devices?building_id=' + buildingId, done, fail),

		getSchedule: (buildingId, id, done, fail = null) => this.get('lighting/get_schedule?building_id=' + buildingId + '&id=' + id, done, fail),
		addSchedule: (buildingId, data, done, fail = null) => this.post('lighting/add_schedule?building_id=' + buildingId, data, done, fail),
		saveSchedule: (buildingId, data, done, fail = null) => this.post('lighting/save_schedule?building_id=' + buildingId, data, done, fail),
		deleteSchedule: (buildingId, id, done, fail = null) => this.get('lighting/delete_schedule?building_id=' + buildingId + '&id=' + id, done, fail),
		updateDeviceDetails: (buildingId, id, scheduleId, lightCount, done, fail = null) => this.get('lighting/update_device_details?building_id=' + buildingId + '&id=' + id + '&schedule=' + (scheduleId || 0) + '&no_of_lights=' + (lightCount || 1), done, fail),
		changeLightState: (buildingId, id, state, done, fail = null) => this.get('lighting/change_light_state?building_id=' + buildingId + '&id=' + id + '&state=' + (state ? 1 : 0), done, fail),
		changeGroupState: (buildingId, scheduleId, state, done, fail = null) => this.get('lighting/change_group_state?building_id=' + buildingId + '&id=' + scheduleId + '&state=' + (state ? 1 : 0), done, fail),
		changeAreaState: (buildingId, id, state, done, fail = null) => this.get('lighting/change_area_state?building_id=' + buildingId + '&id=' + id + '&state=' + (state ? 1 : 0), done, fail),
		isScheduleSynced: (buildingId, done, fail = null) => this.get('lighting/is_schedule_synced?building_id=' + buildingId, done, fail)
	}

	control = {
		getOverview: (done, fail = null) => this.get('control/get_overview', done, fail),
		getBuilding: (buildingId, done, fail = null) => this.get('control/get_building?id=' + buildingId, done, fail),
		getBuildingSchedules: (buildingId, done, fail = null) => this.get('control/get_building_schedules?id=' + buildingId, done, fail),

		getDeviceDetails: (buildingId, id, done, fail = null) => this.get('control/get_device_details?building_id=' + buildingId + '&id=' + id, done, fail),
		listDevices: (buildingId, done, fail = null) => this.get('control/list_devices?building_id=' + buildingId, done, fail),

		getSchedule: (buildingId, id, done, fail = null) => this.get('control/get_schedule?building_id=' + buildingId + '&id=' + id, done, fail),
		addSchedule: (buildingId, itemTypeId, data, done, fail = null) => this.post('control/add_schedule?building_id=' + buildingId + '&item_type_id=' + itemTypeId, data, done, fail),
		saveSchedule: (buildingId, data, done, fail = null) => this.post('control/save_schedule?building_id=' + buildingId, data, done, fail),
		deleteSchedule: (buildingId, id, done, fail = null) => this.get('control/delete_schedule?building_id=' + buildingId + '&id=' + id, done, fail),
		updateDeviceDetails: (buildingId, id, scheduleId, done, fail = null) => this.get('control/update_device_details?building_id=' + buildingId + '&id=' + id + '&schedule=' + (scheduleId || 0), done, fail),

		sendKnxValues: (data, done, fail = null) => this.post('control/send_knx_values', data, done, fail)
	}

	mobile = {
		getDefaultBuilding: (done, fail = null) => this.get('mobile/get_default_building', done, fail),
		getBuilding: (buildingId, done, fail = null) => this.get('mobile/get_building?id=' + buildingId, done, fail),
		listBuildings: (done, fail = null) => this.get('mobile/list_buildings', done, fail),
		listModules: (buildingId, done, fail = null) => this.get('mobile/list_modules?id=' + buildingId, done, fail),
		electricityInfo: (buildingId, timePeriod, done, fail = null) => this.get('mobile/electricity_info?building_id=' + buildingId + '&time_period=' + timePeriod, done, fail)
	}

	constructor(
		private http: HttpClient,
		private app: AppService,
		private router: Router
	) {
		if (!environment.production) {
			this.serverURL = 'http://192.168.10.18/eticom/api/v3';
			console.log('DEV_MODE');
		}
	}

	private getJsonError(error) {
		try {
			if (error && error.status === 401) {
				if (this.app.routeData.auth) this.logout();
				return {
					status: 'FAIL',
					message: error.error.message,
					data: null
				};
			} else if (error.error instanceof ErrorEvent) {
				return {
					status: 'FAIL',
					message: error.error.message,
					data: null
				};
			} else if (Mangler.isObject(error.error)) {
				return error.error;
			} else {
				if (error.status) {
					return {
						status: 'FAIL',
						message: 'Internal server error.',
						data: null
					};
				} else {
					return {
						status: 'FAIL',
						message: 'Connection error.',
						data: null
					};
				}
			}
		} catch (ex) {
			return {
				status: 'FAIL',
				message: 'Internal server error.',
				data: null
			};
		}
	}

	private get(action, done = null, fail = null) {
		this.http.get(this.serverURL + '/' + action, { withCredentials: true }).subscribe(json => {
			if (done) done(json);
		}, error => {
			const json = this.getJsonError(error);
			if (json && fail) fail(json);
		});
	}

	private post(action, data = {}, done = null, fail = null) {
		this.http.post(this.serverURL + '/' + action, data, { withCredentials: true }).subscribe(json => {
			if (done) done(json);
		}, error => {
			const json = this.getJsonError(error);
			if (json && fail) fail(json);
		});
	}

	public objectToQueryString(obj) {
		if (!Mangler.isObject(obj)) return '';

		const qs = [];
		Mangler.each(obj, (k, v) => {
			qs.push(k + '=' + encodeURIComponent(v));
		});
		return qs.join('&');
	}

	logout() {
		const finished = () => {
			// TODO: Redirect root window to old login screen, which will take care of redirects
			// Once the old app is purged, needs updating.
			// this.router.navigate(['/auth/login']);
			this.app.redirect(this.app.getAppURL());
		};

		this.public.logout(finished, finished);
	}

}
