var _HANDLER_FORMS = {
	addpage : 'addpage_options',
	deletepage : 'deletepage_options'
};

if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};

SiteTree.prototype = {
	castAsTreeNode: function(li) {
		behaveAs(li, SiteTreeNode, this.options);
	},
	getIdxOf : function(treeNode) {
		if(treeNode && treeNode.id) return treeNode.id;
	},
	getTreeNodeByIdx : function(idx) {
		if(!idx) idx = "0";
		return document.getElementById(idx);
	},
	initialise: function() {
		this.observeMethod('SelectionChanged', this.changeCurrentTo);	
	}
};

SiteTreeNode.prototype.onselect = function() {
	$('sitetree').changeCurrentTo(this);
	if($('sitetree').notify('SelectionChanged', this)) {
		this.getPageFromServer();
	}
	return false; 
};

SiteTreeNode.prototype.getPageFromServer = function() {
	if(this.id) $('Form_EditForm').getPageFromServer(this.id);
};

addRun = Class.create();
addRun.applyTo('#addpage');
addRun.prototype = {
	initialize: function () {
		Observable.applyTo($(this.id + '_options'));
		this.getElementsByTagName('button')[0].onclick = returnFalse;
		$(this.id + '_options').onsubmit = this.form_submit;
	},
	
	onclick : function() {
		statusMessage('Starting new link check run...');
		this.form_submit();
		return false;
	},

	form_submit : function() {
		var st = $('sitetree');

		$('addpage_options').elements.ParentID.value = st.getIdxOf(st.firstSelected());		
		Ajax.SubmitForm('addpage_options', null, {
			onSuccess : this.onSuccess,
			onFailure : this.showAddPageError
		});
		return false;
	},

	onSuccess: function(response) {
		Ajax.Evaluator(response);
	},

	showAddPageError: function(response) {
		errorMessage('Error adding folder', response);
	}
}



/**
 * Delete folder action
 */
deleteRun = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			deleteRun.o1 = $('sitetree').observeMethod('SelectionChanged', deleteRun.treeSelectionChanged);
			deleteRun.o2 = $('deletepage_options').observeMethod('Close', deleteRun.popupClosed);
			
			addClass($('sitetree'),'multiselect');

			deleteRun.selectedNodes = { };

			var sel = $('sitetree').firstSelected()
			if(sel) {
				var selIdx = $('sitetree').getIdxOf(sel);
				deleteRun.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
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
		$('deletepage_options').stopObserving(deleteRun.o2);

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
			var selectedNode = $('sitetree').getTreeNodeByIdx(idx);
			var link = selectedNode.getElementsByTagName('a')[0];
			
			if(deleteRun.selectedNodes[idx] && ( !Element.hasClassName( link, 'contents' ) || confirm( "'" + link.firstChild.nodeValue + "' contains files. Would you like to delete the files and folder?" ) ) ) 
				csvIDs += (csvIDs ? "," : "") + idx;
		}
		
		if(csvIDs) {
			$('deletepage_options').elements.csvIDs.value = csvIDs;
			
			statusMessage('deleting pages');

			Ajax.SubmitForm('deletepage_options', null, {
				onSuccess : deleteRun.submit_success,
				onFailure : function(response) {
					errorMessage('Error deleting pages', response);
				}
			});

			$('deletepage').getElementsByTagName('button')[0].onclick();
			
		} else {
			alert("Please select at least 1 page.");
		}

		return false;
	},
	
	submit_success: function(response) {
		Ajax.Evaluator(response);
		treeactions.closeSelection($('deletepage'));
	}
}

/** 
 * Initialisation function to set everything up
 */
appendLoader(function () {
	// Set up delete page
	Observable.applyTo($('deletepage_options'));
	if($('deletepage')) {
		$('deletepage').onclick = deleteRun.button_onclick;
		$('deletepage').getElementsByTagName('button')[0].onclick = function() { return false; };
		$('deletepage_options').onsubmit = deleteRun.form_submit;
	}
	
	new CheckBoxRange($('Form_EditForm'), 'Files[]');
});

Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer : function(id) {
			if(id) {
				this.receivingID = id;

				// Treenode might not exist if that part of the tree is closed
				var treeNode = $('sitetree').getTreeNodeByIdx(id);
				
				if(treeNode) treeNode.addNodeClass('loading');
				
				statusMessage("loading...");

				var requestURL = 'admin/linkcheck/show/' + id;
				
				new Ajax.Request(requestURL, {
					asynchronous : true,
					method : 'post', 
					postBody : 'ajax=1',
					onSuccess : this.successfullyReceivedPage.bind(this),
					onFailure : function(response) { 
						errorMessage('error loading page', response);
					}
				});
			} else {
				throw("getPageFromServer: Bad ID: " + id);
			}
		},
		
		successfullyReceivedPage : function(response) {
			this.loadNewPage(response.responseText);
			
			// Treenode might not exist if that part of the tree is closed
			var treeNode = $('sitetree').getTreeNodeByIdx(this.receivingID);
			if(treeNode) {
				$('sitetree').changeCurrentTo(treeNode);
				treeNode.removeNodeClass('loading');
			}
	
			statusMessage('');
      
	      onload_init_tabstrip();
            
			if(this.openTab ) {
				openTab( this.openTab );
				this.openTab = null;    
			}
		},
		
		didntReceivePage : function(response) {
			errorMessage('error loading page', response); 
			$('sitetree').getTreeNodeByIdx(this.elements.ID.value).removeNodeClass('loading');
		}

	}
});

var CheckBoxRange = Class.create();

CheckBoxRange.prototype = {
	currentBox: null,
	form: null,
	field: null,

	initialize: function(form, field) {
		this.form = form;
		this.field = field;
		this.eventPossibleCheckHappened = this.possibleCheckHappened.bindAsEventListener(this);
		Event.observe(form, "click", this.eventPossibleCheckHappened);
		Event.observe(form, "keyup", this.eventPossibleCheckHappened);
	},
		
	possibleCheckHappened: function(event) {
		var target = Event.element(event);
			
		if ((event.button == 0 || event.keyCode == 32 || event.keyCode == 17) && 
			this.isCheckBox(target) && target.form == $(this.form) && target.name == this.field) {
			// If ctrl or shift is keys are pressed
			if ((event.shiftKey || event.ctrlKey  ) && this.currentBox)
				this.updateCheckBoxRange(this.currentBox, target);
		this.currentBox = target;
		}
	},

	isCheckBox: function(e) {
		return (e.tagName.toLowerCase() == "input" && e.type.toLowerCase() == "checkbox");
	},

	updateCheckBoxRange: function(start, end) {
		var last_clicked = end;
		var checkboxes = Form.getInputs(this.form, 'checkbox', this.field);
		var checkbox;
		var last;
		
		for (var i=0; (checkbox = checkboxes[i]); ++i) {
		if (checkbox == end) {
			last = start;
			break;
		}
		if (checkbox == start) {
			last = end;
			break;
		}
		}
		
		for (; (checkbox = checkboxes[i]); ++i) {
			if (checkbox != last_clicked && checkbox.checked != last_clicked.checked)
				checkbox.click();
			if (checkbox == last)
				break;
		}
	}
}
