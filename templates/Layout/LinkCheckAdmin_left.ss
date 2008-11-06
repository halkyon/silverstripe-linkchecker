<style>
	ul.tree a {
		background-image: url(cms/images/treeicons/reports-file.png);
	}
</style>

<h2><% _t('LINKCHECK','Link check runs') %></h2>

<div id="treepanes" style="overflow-y: auto;">
	<ul id="TreeActions">
		<li class="action" id="addpage"><button><% _t('START','Start link checker') %></button></li>
		<li class="action" id="deletepage"><button><% _t('DELETE','Delete') %></button></li>
	</ul>
	<div style="clear:both;"></div>
	<form class="actionparams" id="addpage_options" style="display: none" action="admin/linkcheck/startrun">
		<div>
		<input type="hidden" name="ParentID" />
		<input class="action" type="submit" value="<% _t('GO','Go') %>" />
		</div>
	</form>

	<form class="actionparams" id="deletepage_options" style="display: none" action="admin/linkcheck/deleterun">
		<p><% _t('SELECTTODEL','Select the items that you want to delete and then click the button below') %></p>
		<div>		
		<input type="hidden" name="csvIDs" />
		<input type="submit" value="<% _t('DELFOLDERS','Delete the selected items') %>" />
		</div>
	</form>
	
	$SiteTreeAsUL
</div>