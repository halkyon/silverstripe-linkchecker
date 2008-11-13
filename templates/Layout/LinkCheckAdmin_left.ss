<h2><% _t('LINKCHECK','Link check runs') %></h2>

<div id="treepanes" style="overflow-y: auto;">
	<span id="startRunLoading">
		<img src="linkchecker/images/network-save.gif">
	</span>

	<ul id="TreeActions">
		<li class="action" id="addRun"><button><% _t('START','Start link checker') %></button></li>
		<li class="action" id="deleteRun"><button><% _t('DELETE','Delete') %></button></li>
	</ul>
	<div style="clear:both;"></div>
	<form class="actionparams" id="addRun_options" style="display: none" action="admin/linkcheck/startrun">
		<div>
		<input type="hidden" name="ParentID" />
		<input class="action" type="submit" value="<% _t('GO','Go') %>" />
		</div>
	</form>

	<form class="actionparams" id="deleteRun_options" style="display: none" action="admin/linkcheck/deleterun">
		<p><% _t('SELECTTODEL','Select the items that you want to delete and then click the button below') %></p>
		<div>		
		<input type="hidden" name="csvIDs" />
		<input type="submit" value="<% _t('DELFOLDERS','Delete the selected items') %>" />
		</div>
	</form>
	
	<ul id="sitetree" class="tree unformatted">
		<li id="record-0" class="Root"><a href="admin/linkcheck/show/0"><strong>Run dates</strong></a>
		<% control LinkCheckRuns %>
			<li id="record-$ID" class="$class">
				<a href="admin/linkcheck/show/$ID"<% if NoBrokenLinks %> class="nobroken"<% end_if %>>$TreeTitle</a>
			</li>
		<% end_control %>
		</li>
	</ul>
	
</div>