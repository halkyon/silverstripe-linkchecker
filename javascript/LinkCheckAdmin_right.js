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
