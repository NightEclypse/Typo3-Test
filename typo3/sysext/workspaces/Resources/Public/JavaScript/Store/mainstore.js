Ext.ns('TYPO3.Workspaces.Configuration');

TYPO3.Workspaces.Configuration.StoreFieldArray = [
	{name : 'table'},
	{name : 'uid', type : 'int'},
	{name : 't3ver_oid', type : 'int'},
	{name : 'livepid', type : 'int'},
	{name : 'stage', type: 'int'},
	{name : 'change',type : 'int'},
	{name : 'label_Live'},
	{name : 'label_Workspace'},
	{name : 'label_Stage'},
	{name : 'label_nextStage'},
	{name : 'label_prevStage'},
	{name : 'workspace_Title'},
	{name : 'actions'},
	{name : 'icon_Workspace'},
	{name : 'icon_Live'},
	{name : 'path_Live'},
	{name : 'path_Workspace'},
	{name : 'state_Workspace'},
	{name : 'workspace_Tstamp'},
	{name : 'workspace_Formated_Tstamp'},
	{name : 'allowedAction_nextStage'},
	{name : 'allowedAction_prevStage'},
	{name : 'allowedAction_swap'},
	{name : 'allowedAction_delete'},
	{name : 'allowedAction_edit'},
	{name : 'allowedAction_editVersionedPage'},
	{name : 'allowedAction_view'}

];

TYPO3.Workspaces.MainStore = new Ext.data.GroupingStore({
	storeId : 'workspacesMainStore',
	reader : new Ext.data.JsonReader({
		idProperty : 'id',
		root : 'data',
		totalProperty : 'total'
	}, TYPO3.Workspaces.Configuration.StoreFieldArray),
	groupField: 'path_Workspace',
	paramsAsHash : true,
	sortInfo : {
		field : 'label_Live',
		direction : "ASC"
	},
	remoteSort : true,
	baseParams: {
		depth : 990,
		id: TYPO3.settings.Workspaces.id,
		query: '',
		start: 0,
		limit: 30
	},

	showAction : false,
	listeners : {
		beforeload : function() {
		},
		load : function(store, records) {
		},
		datachanged : function(store) {
		},
		scope : this
	}
});