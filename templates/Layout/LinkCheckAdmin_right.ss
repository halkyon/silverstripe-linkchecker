<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/linkcheck?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h2><% _t('LINKCHECKHEADER', 'SilverStripe Link Checker') %></h2>
		<% include WelcomeText %>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>