var _HANDLER_FORMS = {
	addRun : 'addpage_options',
	deleteRun : 'deletepage_options'
};

if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.loadPage_url = 'admin/linkcheck/getitem';
SiteTreeHandlers.controller_url = 'admin/linkcheck';

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

deleteRun = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			$('deletepage_options').style.display = 'block';
 
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
		} else {
			$('deletepage_options').style.display = 'none';
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
			if(deleteRun.selectedNodes[idx]) csvIDs += (csvIDs ? "," : "") + idx;
		}
		if(csvIDs) {
			if(confirm("Do you really want to delete these links?")) {
				$('deletepage_options').elements.csvIDs.value = csvIDs;
 
				Ajax.SubmitForm('deletepage_options', null, {
					onSuccess : function(response) {
						Ajax.Evaluator(response);
						var sel;
						if((sel = $('sitetree').firstSelected()) && sel.parentNode) sel.addNodeClass('current');
						else $('Form_EditForm').innerHTML = "";
						treeactions.closeSelection($('deletepage'));
					},
					onFailure : function(response) {
						errorMessage('Error deleting items', response);
					}
				});
 
				$('deletepage').getElementsByTagName('button')[0].onclick();
			}
		} else {
			alert("Please select at least one item.");
		}
		return false;

	},
	
	submit_success: function(response) {
		Ajax.Evaluator(response);
		treeactions.closeSelection($('deletepage'));
	}
}

appendLoader(function () {
	Observable.applyTo($('deletepage_options'));
	if($('deletepage')) {
		$('deletepage').onclick = deleteRun.button_onclick;
		$('deletepage').getElementsByTagName('button')[0].onclick = function() { return false; };
		$('deletepage_options').onsubmit = deleteRun.form_submit;
	}
	
	new CheckBoxRange($('Form_EditForm'), 'Files[]');
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
