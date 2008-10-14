<style>
	ul.tree a {
		background-image: url(cms/images/treeicons/reports-file.png);
	}
</style>

<h2><% _t('LINKCHECK','Link check runs') %></h2>

<div id="treepanes">
	<div id="sitetree_holder">
		<% if LinkCheckRuns %>
			<ul id="sitetree" class="tree unformatted">
			<li id="$ID" class="root"><a><% _t('LINKCHECK','Link check runs') %></a>
				<ul>
				<% control LinkCheckRuns %>
					<li id="$ID">
						<a href="$baseURL/admin/linkcheck/$ID">$Created.Nice</a>
					</li>
				<% end_control %>
				</ul>
			</li>
			</ul>
		<% end_if %>
	</div>
</div>
