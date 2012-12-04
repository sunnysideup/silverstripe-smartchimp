<% if SmartChimpNewslettersShow %>
<div id="PreviousSmartChimpNewsletters">
	<h2>Previous Editions</h2>
	<% control SmartChimpNewslettersShow.GroupedBy(YearMonth) %>
	<h3>$YearMonth</h3>		
	<ul id="SmartChimpNewsletters">
		<% control Children %>
		<li>
			<h5><a href="$PermaLink" class="popup">$Title</a></h5>
			<p class="smartChimpNewslettersDate">Sent on: $Date.Long</p>
			<p class="smartChimpNewslettersSubject">Subject: $Subject</p>
			<p class="smartChimpNewslettersContent">$TextContent.LimitWordCountXML</p>
		</li>
		<% end_control %>
	</ul>
	<% end_control %>	
</div>
<% end_if %>
