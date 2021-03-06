<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	// Ajax
	function showCalloutAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();

		$callouts = CerberusApplication::getTourCallouts();
		
	    $callout = array();
	    if(isset($callouts[$id]))
	        $callout = $callouts[$id];
		
	    $tpl->assign('callout',$callout);
		
		$tpl->display('devblocks:cerberusweb.core::internal/tour/callout.tpl');
	}

	// Post
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
	}
	
	// Contexts
	
	function showTabContextLinksAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(!empty($point))
			$visit->set($point, 'links');
		
		// Options
		$options = array();

		@$filter_open = DevblocksPlatform::importGPC($_REQUEST['filter_open'],'integer', 0);
		if(!empty($filter_open))
			$options['filter_open'] = true;
		
		// Contexts
		
		$context_extensions = Extension_DevblocksContext::getAll();
		$tpl->assign('context_extensions', $context_extensions);
		
		// Context Links
		
		$views = array();
		$contexts = DAO_ContextLink::getDistinctContexts($context, $context_id);
		
		foreach($contexts as $ctx) {
			if(null == ($ext_context = DevblocksPlatform::getExtension($ctx, true)))
				continue;
				
			if(!$ext_context instanceof Extension_DevblocksContext)
				continue;
				
			$view = $ext_context->getView($context, $context_id, $options);
			
			if(!empty($view))
				$views[$view->id] = $view;
		}
		
		ksort($views);
		
		$tpl->assign('views', $views);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/tab.tpl');
	}	
	
	function chooserOpenAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		
		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			$tpl->assign('view', $context_extension->getChooserView());
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
		}
	}
	
	function chooserOpenFileAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('layer', $layer);
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}
	
	function chooserOpenFileUploadAction() {
		@$file = $_FILES['file_data'];
		
		// [TODO] Return false in JSON if file is empty, etc.
		
		// Create a record w/ timestamp + ID
		$fields = array(
			DAO_Attachment::DISPLAY_NAME => $file['name'],
			DAO_Attachment::MIME_TYPE => $file['type'],
		);
		$file_id = DAO_Attachment::create($fields);
		
		// Save the file
		if(null !== ($fp = fopen($file['tmp_name'], 'rb'))) {
            Storage_Attachments::put($file_id, $fp);
			fclose($fp);
            unlink($file['tmp_name']);
		}
		
		// [TODO] Unlinked records should expire
		
		echo json_encode(array(
			'name' => $file['name'],
			'size' => $file['size'],
			'id' => $file_id,
		));
	}
	
	function contextAddLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());
		
		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);
	}
	
	function contextDeleteLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());
		
		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
	}
	
	// Autocomplete
	
	function autocompleteAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		$list = array();
		
		// [TODO] This should be handled by the context extension
		switch($context) {
			case CerberusContexts::CONTEXT_ADDRESS:
				list($results, $null) = DAO_Address::search(
					array(),
					array(
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Address::LAST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::FIRST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
						),
					),
					25,
					0,
					SearchFields_Address::NUM_NONSPAM,
					false,
					false
				);
				
				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = trim($row[SearchFields_Address::FIRST_NAME] . ' ' . $row[SearchFields_Address::LAST_NAME] . ' <' .$row[SearchFields_Address::EMAIL] . '>');
					$entry->value = $row[SearchFields_Address::ID]; 
					$list[] = $entry;
				}
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				list($results, $null) = DAO_Group::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_Group::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					DAO_Group::TEAM_NAME,
					true,
					false
				);
				
				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_Group::NAME];
					$entry->value = $row[SearchFields_Group::ID]; 
					$list[] = $entry;
				}
				break;
				
			case CerberusContexts::CONTEXT_ORG:
				list($results, $null) = DAO_ContactOrg::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					SearchFields_ContactOrg::NAME,
					true,
					false
				);
				
				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_ContactOrg::NAME];
					$entry->value = $row[SearchFields_ContactOrg::ID]; 
					$list[] = $entry;
				}
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				list($results, $null) = DAO_Worker::search(
					array(),
					array(
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Worker::LAST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Worker::FIRST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
						),
					),
					25,
					0,
					SearchFields_Worker::FIRST_NAME,
					true,
					false
				);
				
				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_Worker::FIRST_NAME] . ' ' . $row[SearchFields_Worker::LAST_NAME];
					$entry->value = $row[SearchFields_Worker::ID]; 
					$list[] = $entry;
				}
				break;
		}
		
		echo json_encode($list);
		exit;
	}
	
	// Snippets
	
	function snippetPasteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// [TODO] Make sure the worker is allowed to view this context+ID
		
		if(null != ($snippet = DAO_Snippet::get($id))) {
			switch($snippet->context) {
				case 'cerberusweb.contexts.ticket':
					CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $context_id, $token_labels, $token_values);
					break;
				case 'cerberusweb.contexts.worker':
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $context_id, $token_labels, $token_values);
					break;
				case 'cerberusweb.contexts.plaintext':
					$token_values = array();
					break;
			}
			
			$snippet->incrementUse($active_worker->id);
		}
		
		if(!empty($context_id)) {
			$output = $tpl_builder->build($snippet->content, $token_values);
		} else {
			$output = $snippet->content;
		}
		
		if(!empty($output))
			echo rtrim($output,"\r\n"),"\n\n";		
	}
	
	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		//@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$tpl = DevblocksPlatform::getTemplateService();

		$token_labels = array();
		$token_value = array();
		
		switch($snippet_context) {
			case 'cerberusweb.contexts.plaintext':
				break;
				
			case 'cerberusweb.contexts.ticket':
				// [TODO] Randomize
				list($result, $count) = DAO_Ticket::search(
					array(),
					array(
					),
					10,
					0,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
					false,
					false
				);
				
				shuffle($result);
				
				CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, array_shift($result), $token_labels, $token_values);
				break;
				
			case 'cerberusweb.contexts.worker':
				$active_worker = CerberusApplication::getActiveWorker();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
				break;
		}

		$success = false;
		$output = '';
		
		if(!empty($token_values)) {
			// Try to build the template
			if(false === ($out = $tpl_builder->build($content, $token_values))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success= false;
				$output = @array_shift($errors);
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
			}
		}
		
		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}

	// Views
	
	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = C4_AbstractViewLoader::getView($id);
		$view->render();
	}
	
	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doSortBy($sortBy);
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doPage($page);
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->renderCriteria($field);
	}
	
	private function _viewRenderInlineFilters($view) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}
	
	// Ajax
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());
		
		$view = C4_AbstractViewLoader::getView($id);

		// Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewResetFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$view = C4_AbstractViewLoader::getView($id);

		$view->doResetCriteria();
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);		
	}
	
	function viewLoadPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_id = DevblocksPlatform::importGPC($_REQUEST['_preset'],'integer',0);

		$view = C4_AbstractViewLoader::getView($id);

		$view->removeAllParams();
		
		if(null != ($preset = DAO_ViewFiltersPreset::get($preset_id))) {
			$view->addParams($preset->params);
			if(!is_null($preset->sort_by)) {
				$view->renderSortBy = $preset->sort_by;
				$view->renderSortAsc = !empty($preset->sort_asc);
			}
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewAddPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_name = DevblocksPlatform::importGPC($_REQUEST['_preset_name'],'string','');
		@$preset_replace_id = DevblocksPlatform::importGPC($_REQUEST['_preset_replace'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$view = C4_AbstractViewLoader::getView($id);
		$params_json = json_encode($view->getEditableParams());

		if(!empty($preset_replace_id)) {
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);
			
			DAO_ViewFiltersPreset::update($preset_replace_id, $fields);
			
		} else { // new
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::VIEW_CLASS => get_class($view),
				DAO_ViewFiltersPreset::WORKER_ID => $active_worker->id,
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);
			
			DAO_ViewFiltersPreset::create($fields);
		}
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewEditPresetsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_dels = DevblocksPlatform::importGPC($_REQUEST['_preset_del'],'array',array());

		$view = C4_AbstractViewLoader::getView($id);

		DAO_ViewFiltersPreset::delete($preset_dels);
		
		$this->_viewRenderInlineFilters($view);		
	}
	
	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

		$view = C4_AbstractViewLoader::getView($id);
		$tpl->assign('view', $view);

		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}
	
	function viewShowCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
        
        $view = C4_AbstractViewLoader::getView($view_id);

		$workspaces = DAO_Workspace::getByWorker($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
        
        $tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

        $tpl->display('devblocks:cerberusweb.core::internal/views/copy.tpl');
	}
	
	// Ajax
	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
	    
		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace_id = DevblocksPlatform::importGPC($_POST['workspace_id'],'integer', 0);
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
		
		if(empty($workspace_id)) {
			$fields = array(
				DAO_Workspace::NAME => (!empty($new_workspace) ? $new_workspace : $translate->_('mail.workspaces.new')),
				DAO_Workspace::WORKER_ID => $active_worker->id,
			);
			$workspace_id = DAO_Workspace::create($fields);
		}
		
		if(null == ($workspace = DAO_Workspace::get($workspace_id)))
			return;

		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');
			
		// Find the context
		$contexts = Extension_DevblocksContext::getAll();
		$workspace_context = '';
		$view_class = get_class($view);
		foreach($contexts as $context_id => $context) {
			if(0 == strcasecmp($context->params['view_class'], $view_class))
				$workspace_context = $context_id;
		}
		
		if(empty($workspace_context))
			return;
		
		// View params inside the list for quick render overload
		$list_view = new Model_WorkspaceListView();
		$list_view->title = $list_title;
		$list_view->num_rows = $view->renderLimit;
		$list_view->columns = $view->view_columns;
		$list_view->params = $view->getEditableParams();
		$list_view->sort_by = $view->renderSortBy;
		$list_view->sort_asc = $view->renderSortAsc;
		
		// Save the new worklist
		$fields = array(
			DAO_WorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
			DAO_WorkspaceList::LIST_POS => 99,
		);
		$list_id = DAO_WorkspaceList::create($fields);
		
		$view->render();
	}

	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		$view = C4_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);
		
		$model_columns = $view->getColumnsAvailable();
		$tpl->assign('model_columns', $model_columns);
		
		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}
	
	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$column_manifests = $view->getColumnsAvailable();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}
			
		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}
		
			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();			
		}
		
		exit;
	}
	
	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$num_rows = max($num_rows, 1); // make 1 the minimum
		
		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(substr($column, 0, 3)=="cf_") {
				$field_id = intval(substr($column, 3));
				@$field = $custom_fields[$field_id]; /* @var $field Model_CustomField */
				
				// Is this a valid custom field?
				if(empty($field)) {
					unset($columns[$idx]);
					continue;
				}
				
				// Do we have permission to see it?
				if(!empty($field->group_id) 
					&& !$active_worker->isTeamMember($field->group_id)) {
						unset($columns[$idx]);
						continue;	
				}
			}
		}
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doCustomize($columns, $num_rows);
		
		// Handle worklists specially
		if(substr($id,0,5)=="cust_") { // custom workspace
			$list_view_id = intval(substr($id,5));
			
			// Special custom view fields
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			
			$view->name = $title;

			// Persist Object
			// [TODO] The list view can auto-persist in the 'worker_view_model' table
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = $view->getEditableParams();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;
			
			DAO_WorkspaceList::update($list_view_id, array(
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view)
			));
		}
		
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewSubtotalAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$category = DevblocksPlatform::importGPC($_REQUEST['category'],'string','');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
			
		if(!method_exists($view, 'getCounts'))
			return;
		
		$view->renderSubtotals = $category;

		C4_AbstractViewLoader::setView($view->id, $view);
		
		if(empty($view->renderSubtotals))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
			
		$counts = $view->getCounts($view->renderSubtotals);
		$tpl->assign('counts', $counts);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/view_sidebar.tpl');
	}
	
	// Workspace
	
	function showAddTabAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Endpoint
		$tpl->assign('point', $point);
		$tpl->assign('request', $request);

		// Workspaces
		$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id);
		$workspaces = $enabled_workspaces + array_diff_key(DAO_Workspace::getByWorker($active_worker->id), $enabled_workspaces);
		
		$tpl->assign('enabled_workspaces', $enabled_workspaces);
		$tpl->assign('workspaces', $workspaces);
				
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tab.tpl');
	}
	
	function doAddTabAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$workspace_ids = DevblocksPlatform::importGPC($_REQUEST['workspace_ids'],'array', array());
		@$new_workspace = DevblocksPlatform::importGPC($_REQUEST['new_workspace'],'string', '');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$is_focused_tab = false;

		// Are we adding any new workspaces?
		foreach($workspace_ids as $idx => $workspace_id) {
			// Insert and replace the $id
			if(!is_numeric($workspace_id)) {
				$fields = array(
					DAO_Workspace::NAME => $workspace_id,
					DAO_Workspace::WORKER_ID => $active_worker->id,
				);
				$workspace_id = DAO_Workspace::create($fields);
				$workspace_ids[$idx] = $workspace_id;
			}
			
			// Only focus the first new tab we add
			if(!empty($point) && !$is_focused_tab) {
				$visit->set($point, 'w_' . $workspace_id);
				$is_focused_tab = true;
			}
		}
		
		// Replace links for this endpoint
		DAO_Workspace::setEndpointWorkspaces($point, $active_worker->id, $workspace_ids);
		
		if(empty($request))
			$request = 'mail';
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/',$request)));
	}
	
	function showWorkspaceTabAction() {
		@$workspace_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit = CerberusApplication::getVisit();
		$visit->set($point, 'w_'.$workspace_id);
		
		if(null == ($workspace = DAO_Workspace::get($workspace_id)) 
			|| $workspace->worker_id != $active_worker->id
			)
			return;
		
		$views = array();
		
		$lists = $workspace->getWorklists();
		
        // Loop through list schemas
		if(is_array($lists) && !empty($lists))
		foreach($lists as $list) { /* @var $list Model_WorkspaceList */
			$view_id = 'cust_'.$list->id;
			if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
				$list_view = $list->list_view; /* @var $list_view Model_WorkspaceListView */
				
				// Make sure our workspace source has a valid renderer class
				if(null == ($ext = DevblocksPlatform::getExtension($list->context, true))) { /* @var $ext Extension_DevblocksContext */
					continue;
				}
					
				$view_class = $ext->getViewClass();
				if(!class_exists($view_class))
					continue;
					
				$view = new $view_class;
				$view->id = $view_id;
				$view->name = $list_view->title;
				$view->renderLimit = $list_view->num_rows;
				$view->renderPage = 0;
				$view->view_columns = $list_view->columns;
				$view->addParams($list_view->params, true);
				$view->renderSortBy = $list_view->sort_by;
				$view->renderSortAsc = $list_view->sort_asc;
				C4_AbstractViewLoader::setView($view_id, $view);
			}
			
			if(!empty($view))
				$views[] = $view;
		}
	
		$tpl->assign('workspace', $workspace);
		$tpl->assign('request', $request);
		$tpl->assign('views', $views);
		
		// Log activity
		DAO_Worker::logActivity(
			new Model_Activity(
				'activity.mail.workspaces',
				array(
					'<i>'.$workspace->name.'</i>'
				)
			)
		);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/index.tpl');
	}	
	
	function showEditWorkspacePanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Workspace
		if(null == ($workspace = DAO_Workspace::get($id)))
			return;
		
		$tpl->assign('workspace', $workspace);
		$tpl->assign('request', $request);

		// Worklist
		$worklists = $workspace->getWorklists();
		$tpl->assign('worklists', $worklists);
		
		// Contexts
		$contexts = Extension_DevblocksContext::getAll();
		$tpl->assign('contexts', $contexts);		
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/edit_workspace_panel.tpl');
	}
	
	function doEditWorkspaceAction() {
		@$workspace_id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$rename_workspace = DevblocksPlatform::importGPC($_POST['rename_workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer', '0');
		
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($workspace = DAO_Workspace::get($workspace_id)) || $workspace->worker_id != $active_worker->id)
			return;
		
		if($do_delete) { // Delete
			DAO_Workspace::delete($workspace_id);
			
		} else { // Edit
			// Rename workspace
			if(0 != strcmp($workspace->name, $rename_workspace)) {
				$fields = array(
					DAO_Workspace::NAME => $rename_workspace
				);
				DAO_Workspace::update($workspace->id, $fields);
			}
			
			// Create any new worklists
			if(is_array($ids) && !empty($ids))
			foreach($ids as $idx => $id) {
				if(!is_numeric($id)) { // Create
					if(null == ($context_ext = DevblocksPlatform::getExtension($id, true))) /* @var $context_ext Extension_DevblocksContext */
						continue;
					if(null == (@$class = $context_ext->getViewClass()))
						continue;
					if(!class_exists($class, true) || null == ($view = new $class))
						continue;
					
					// Build the list model
					$list = new Model_WorkspaceListView();
					$list->title = $names[$idx];
					$list->columns = $view->view_columns;
					$list->params = $view->getEditableParams();
					$list->num_rows = 5;
					$list->sort_by = $view->renderSortBy;
					$list->sort_asc = $view->renderSortAsc;
					
					// Add the worklist
					$fields = array(
						DAO_WorkspaceList::WORKER_ID => $active_worker->id,
						DAO_WorkspaceList::LIST_POS => $idx,
						DAO_WorkspaceList::LIST_VIEW => serialize($list),
						DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
						DAO_WorkspaceList::CONTEXT => $id,
					);
					$ids[$idx] = DAO_WorkspaceList::create($fields);
				}
			}
			
			$worklists = $workspace->getWorklists();
			
			// Deletes
			$delete_ids = array_diff(array_keys($worklists), $ids);
			if(is_array($delete_ids) && !empty($delete_ids))
				DAO_WorkspaceList::delete($delete_ids);
			
			// Reorder worklists, rename lists, on workspace
			if(is_array($ids) && !empty($ids))
			foreach($ids as $idx => $id) {
				if(null == ($worklist = DAO_WorkspaceList::get($id)))
					continue;
				
				$list_view = $worklists[$id]->list_view; /* @var $list_view Model_WorkspaceListView */
				
				// If the name changed
				if(isset($names[$idx]) && 0 != strcmp($list_view->title,$names[$idx])) {
					$list_view->title = $names[$idx];
				
					// Save the view in the session
					$view = C4_AbstractViewLoader::getView('cust_'.$id);
					$view->name = $list_view->title;
					C4_AbstractViewLoader::setView('cust_'.$id, $view);
				}
					
				DAO_WorkspaceList::update($id,array(
					DAO_WorkspaceList::LIST_POS => intval($idx),
					DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				));
			}
		}	
		
		if(empty($request))
			$request = 'mail';
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $request)));
		return;
	}	
	
	// Utils
	
	function startAutoRefreshAction() {
		$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string', '');
		$secs = DevblocksPlatform::importGPC($_REQUEST['secs'],'integer', 300);
		
		$_SESSION['autorefresh'] = array(
			'url' => $url,
			'started' => time(),
			'secs' => $secs,
		);
	}
	
	function stopAutoRefreshAction() {
		unset($_SESSION['autorefresh']);
	}
	
	function transformMarkupToHTMLAction() {
		$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');
		
		switch($format) {
			case 'markdown':
				echo DevblocksPlatform::parseMarkdown($data);
				break;
			case 'html':
			default:
				echo $data;
				break;
		}
	}

	// Comments
	
	function showTabContextCommentsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		if(!empty($point))
			$visit->set($point, 'comments');
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$comments = DAO_Comment::getByContext($context, $context_id);
		$tpl->assign('comments', $comments);
		
		$tpl->display('devblocks:cerberusweb.core::internal/comments/tab.tpl');
	}
	
	function commentShowPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Automatically tell anybody associated with this context object
		$workers = CerberusContexts::getWorkers($context, $context_id);
		if(isset($workers[$active_worker->id]))
			unset($workers[$active_worker->id]);
		$tpl->assign('notify_workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::internal/comments/peek.tpl');
	}
	
	function commentSavePopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'],'array',array());
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		// [TODO] Validate context + ID
		// [TODO] Validate ACL
		
		// Form was filled in
		if(empty($context) || empty($context_id) || empty($comment))
			return;
			
		$fields = array(
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
			DAO_Comment::COMMENT => $comment,
			DAO_Comment::CREATED => time(),
		);
		$comment_id = DAO_Comment::create($fields);
		
		// Attachments
		if(!empty($file_ids))
		foreach($file_ids as $file_id) {
			DAO_AttachmentLink::create(intval($file_id), CerberusContexts::CONTEXT_COMMENT, $comment_id);
		}
		
		// Notifications
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		DAO_Comment::triggerCommentNotifications(
			$context,
			$context_id,
			$active_worker,
			$notify_worker_ids
		);
	}
	
	function commentDeleteAction() {
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		DAO_Comment::delete($comment_id);
	}
};
