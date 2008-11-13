if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.loadPage_url = 'admin/linkcheck/getitem';
SiteTreeHandlers.controller_url = 'admin/linkcheck';

var _HANDLER_FORMS = {
	addRun : 'addRun_options',
	deleteRun : 'deleteRun_options'
};

addRun = Class.create();
addRun.applyTo('#addRun');
addRun.prototype = {
	initialize : function () {
		Observable.applyTo($(this.id + '_options'));
		this.getElementsByTagName('button')[0].onclick = returnFalse;
		$(this.id + '_options').onsubmit = this.form_submit;
	},
	
	onclick : function() {
		statusMessage('Starting new link check run...');
		$('startRunLoading').style.display = 'inline';
		this.form_submit();
		return false;
	},

	form_submit : function() {
		var st = $('sitetree');

		$('addRun_options').elements.ParentID.value = st.getIdxOf(st.firstSelected());		
		Ajax.SubmitForm('addRun_options', null, {
			onSuccess : this.onSuccess,
			onFailure : this.showAddRunError
		});
		return false;
	},

	onSuccess : function(response) {
		$('startRunLoading').style.display = 'none';
		Ajax.Evaluator(response);
	},

	showAddRunError : function(response) {
		errorMessage('Error starting link check', response);
	}
}

deleteRun = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			$('deleteRun_options').style.display = 'block';
 
			deleteRun.o1 = $('sitetree').observeMethod('SelectionChanged', deleteRun.treeSelectionChanged);
			deleteRun.o2 = $('deleteRun_options').observeMethod('Close', deleteRun.popupClosed);
			
			addClass($('sitetree'),'multiselect');

			deleteRun.selectedNodes = { };

			var sel = $('sitetree').firstSelected()
			if(sel) {
				var selIdx = $('sitetree').getIdxOf(sel);
				deleteRun.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
		} else {
			$('deleteRun_options').style.display = 'none';
		}
		return false;
	},

	treeSelectionChanged : function(selectedNode) {
		var idx = $('sitetree').getIdxOf(selectedNode);

		if(selectedNode.selected) {
			selectedNode.removeNodeClass('selected');
			selectedNode.selected = false;
			deleteRun.selectedNodes[idx] = false;

		} else {
			selectedNode.addNodeClass('selected');
			selectedNode.selected = true;
			deleteRun.selectedNodes[idx] = true;
		}
		
		return false;
	},
	
	popupClosed : function() {
		removeClass($('sitetree'),'multiselect');
		$('sitetree').stopObserving(deleteRun.o1);
		$('deleteRun_options').stopObserving(deleteRun.o2);

		for(var idx in deleteRun.selectedNodes) {
			if(deleteRun.selectedNodes[idx]) {
				node = $('sitetree').getTreeNodeByIdx(idx);
				if(node) {
					node.removeNodeClass('selected');
					node.selected = false;
				}
			}
		}
	},

	form_submit : function() {
		var csvIDs = "";
		for(var idx in deleteRun.selectedNodes) {
			if(deleteRun.selectedNodes[idx]) csvIDs += (csvIDs ? "," : "") + idx;
		}
		if(csvIDs) {
			if(confirm("Do you really want to delete these links?")) {
				$('deleteRun_options').elements.csvIDs.value = csvIDs;
 
				Ajax.SubmitForm('deleteRun_options', null, {
					onSuccess : function(response) {
						Ajax.Evaluator(response);
						var sel;
						if((sel = $('sitetree').firstSelected()) && sel.parentNode) sel.addNodeClass('current');
						else $('Form_EditForm').innerHTML = "";
						treeactions.closeSelection($('deleteRun'));
					},
					onFailure : function(response) {
						errorMessage('Error deleting items', response);
					}
				});
 
				$('deleteRun').getElementsByTagName('button')[0].onclick();
			}
		} else {
			alert("Please select at least one item.");
		}
		return false;

	},
	
	submit_success: function(response) {
		Ajax.Evaluator(response);
		treeactions.closeSelection($('deleteRun'));
	}
}

appendLoader(function () {
	Observable.applyTo($('deleteRun_options'));
	if($('deleteRun')) {
		$('deleteRun').onclick = deleteRun.button_onclick;
		$('deleteRun').getElementsByTagName('button')[0].onclick = function() { return false; };
		$('deleteRun_options').onsubmit = deleteRun.form_submit;
	}
});
