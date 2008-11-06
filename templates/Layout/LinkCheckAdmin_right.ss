<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/linkcheck?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h2><% _t('LINKCHECKHEADER', 'SilverStripe Link Checker') %></h2>
		<p><% _t('WELCOME1','Welcome to the',50,'Followed by application name') %> $ApplicationName <% _t('WELCOME2','link checker section.',50) %></p>
		<p><% _t('WELCOME4','To check links now, choose "Start link checker" on the left') %></p>
		<p><% _t('WELCOME3','Please choose a date on the left to view broken links for that date.') %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>