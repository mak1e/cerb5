<form action="{devblocks_url}{/devblocks_url}" method="POST">

<iframe src="{$smarty.const.DEVBLOCKS_WEBPATH}ajax.php?c=kb.ajax&a=getArticleContent&id={$article->id}" style="margin:5px 0px 5px 5px;height:400px;width:98%;border:1px solid rgb(200,200,200);" frameborder="0"></iframe>
<br>
{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context={CerberusContexts::CONTEXT_KB_ARTICLE} context_id={$article->id}}

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id={$article->id}&view_id={$view_id}&return_uri={$return_uri}',null,false,'725');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{$article->title}');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
</script>
