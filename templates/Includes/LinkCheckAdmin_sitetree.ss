<div id="treepanes">
	<div id="sitetree_holder">
		<% if LinkCheckRuns %>
			<ul id="sitetree" class="tree unformatted">
			<li id="$ID" class="root"><a><% _t('LINKCHECKRUNS','Link check runs') %></a>
				<ul>
				<% control LinkCheckRuns %>
					<li id="$ID">
						<a href="{$BaseHref}admin/linkcheck/show/$ID">$Created.Nice</a>
					</li>
				<% end_control %>
				</ul>
			</li>
			</ul>
		<% end_if %>
	</div>
</div>