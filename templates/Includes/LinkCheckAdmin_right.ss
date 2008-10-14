<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/linkcheck?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h2><% _t('LINKCHECKHEADER', 'SilverStripe Link Checker') %></h2>
		
		<p><% _t('WELCOME1','Welcome to the',50,'Followed by application name') %> $ApplicationName <% _t('WELCOME2','link checker section.',50) %></p>
		
		<p><a href="{$BaseHref}LinkCheckTask"><% _t('LINKCHECK','Check links on all pages now') %></a></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
