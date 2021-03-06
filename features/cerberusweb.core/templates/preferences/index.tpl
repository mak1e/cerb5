<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{if is_array($pref_errors) && !empty($pref_errors)}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin:0.2em; ">
		<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('.ui-widget').fadeOut();">dismiss</a>)</span>
		{foreach from=$pref_errors item=error} 
			<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> {$error}<br>
		{/foreach}
		</div>
	</div>
{elseif is_array($pref_success) && !empty($pref_success)}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin:0.2em; ">
		<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('.ui-widget').fadeOut();">dismiss</a>)</span>
		{foreach from=$pref_success item=success}
			<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> {$success}<br>
		{/foreach}
		</div>
	</div>
{/if}

<div id="prefTabs">
	<ul>
		{$tabs = [events,links,general,rss]}
		{$point = Extension_PreferenceTab::POINT}
		
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showMyEvents{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.worker&point={$point}&id={$active_worker->id}&filter_open=1{/devblocks_url}">{'Assignments'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showGeneral{/devblocks_url}">General</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showRss{/devblocks_url}">RSS Notifications</a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#prefTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>
