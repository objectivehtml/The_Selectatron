<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * The Selectatron Fieldtype for ExpressionEngine 2.0
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author    	Iain Urquhart <shout@iain.co.nz>
 * @copyright 	Copyright (c) 2010 Iain Urquhart
 * @license   	All Rights Reserved.
*/

	class The_selectatron_ft extends EE_Fieldtype
	{
		var $info = array(
			'name'		=> 'The Selectatron',
			'version'	=> '1.2.1'
		);

		public function The_selectatron_ft()
		{
			parent::EE_Fieldtype();
			$this->EE->lang->loadfile('the_selectatron');
		}	

		// output the field on the publish page
		public function display_field($data)
		{
						
			$r = '';
			$selected_entries = array();
			
			if(!is_array($data))
			{
				$data = explode("|", $data);
			}
			
			// get our required js and css
			// @todo move this to the themes folder

			// are we displaying this already?
			if (! isset($this->cache['the_selectatron_displayed']))
			{
				$asset_path = $this->EE->config->item('theme_folder_url').'third_party/selectatron_assets/';
				$this->EE->cp->add_to_head('<link type="text/css" href="'.$asset_path.'css/selectatron.css" rel="stylesheet" />');
				$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$asset_path.'js/jquery.bsmselect.js"></script>');
				$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$asset_path.'js/selectatron.js"></script>');
				// lets not repeat ourselves
				$this->cache['the_selectatron_displayed'] = TRUE;
			}
			
			// activate the bsmSelect!
			$this->EE->javascript->output("
			$(document).ready(function() {
				$('#field_id_".$this->field_id."').bsmSelect({
			        addItemTarget: 'bottom',
			        title: '".lang('please_select_an_entry')."',
			        animate: true,
			        removeLabel: 'x',
			        listClass: 'selectatron".$this->field_id."',
			        // highlight: true,
			        sortable: true
			      });
			});
			");
			
			$selected_channels = $this->settings['channel_preferences'];
			
			// fetch our entries
			$entry_query = $this->EE->db->query("SELECT
			exp_channel_titles.entry_id AS entry_id,
			exp_channel_titles.title AS entry_title,
			exp_channels.channel_title AS channel_title, 
			exp_channels.channel_id AS channel_id
			FROM exp_channel_titles
			INNER JOIN exp_channels
			ON exp_channel_titles.channel_id = exp_channels.channel_id
			WHERE exp_channels.channel_id IN (".$selected_channels.")
			ORDER BY  exp_channels.channel_id ASC , exp_channel_titles.title ASC ");

			if($entry_query->num_rows == 0)
			{
				return lang('no_entries_found');
			}
			
			$r .= "<select id='field_id_".$this->field_id."' multiple='multiple' name='field_id_".$this->field_id."[]'>";
			
			$channel = null;
			$selected_options = '';
			
			foreach ($entry_query->result_array() as $entry)
			{
				if($channel != $entry['channel_title'])
				{
					// if this is not the first channel
					if($channel != null)
					{
						// close the optgroup
						$r .= "</optgroup>";
					}
					// set the current channel
					$channel = $entry['channel_title'];
					// open another opt channel
					$r .= "<optgroup label='" . $entry['channel_title'] ."'> \n";
				}
				
				$key = array_search($entry['entry_id'], $data);
				
				$selected = (in_array($entry['entry_id'], $data)) ? " selected='selected' rel='".$key."' " : "";
				
				if(!$selected)
				{
					// just output the option
					$r .= "<option value='" . $entry['entry_id'] . "'> " . $entry['entry_title'] . "</option> \n";
				}
				else
				{
					// store the option as an array for output at the end
					$selected_options[$key] = "<option value='" . $entry['entry_id'] . "'" . $selected . " > " . $entry['entry_title'] . "</option> \n";
				}

			}
			
			// add our selected on the end, sorted correctly
			// bugfix/patch for now, might revisit for a better solution
			if(is_array($selected_options))
			{
				$r .= "<optgroup label='" . lang('selected_entries') ."'>\n";
				ksort($selected_options);
				foreach($selected_options as $option)
				{
					$r .= $option;
				}
			}
			$r .= "</select>";
			
			return $r;

		}
		
		function pre_process($data)
		{
			return $data;
		}
		
		public function replace_tag($data, $params = FALSE, $tagdata = FALSE)
		{
			return $data;
		}
		
	 	public function save($data){
	 	
	 		// print_r($this->settings);
	 	
			if ( ! is_array($data))
			{
				$data = array($data);
			}

			foreach($data as $key => $val)
			{
				$data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), $val);
			}
			
			// if we're storing EE relationships, then cache the data for post_save()
			if($this->settings['store_ee_relationships'] == 1)
			{
				$field_id = $this->settings['field_id'];
				$this->EE->session->cache['selectatron_'.$field_id]['data'] = implode('|', $data);
			}
			
			return implode('|', $data);
			// the post save function below will save the rel data again.

		}
		
		// Using post save because we can get the 
		// entry_id easily for new entries.
		function post_save($data)
		{
			
			$field_id = $this->settings['field_id'];
			
			$newdata = FALSE;
			
			if($this->settings['store_ee_relationships'] == 1 && isset($this->EE->session->cache['selectatron_'.$field_id]['data']))
			{
				$newdata = $this->EE->session->cache['selectatron_'.$field_id]['data'];
				// remove all rel data where this is a parent
				// might be some implications for folks using other relationship fields on same publish page...
				$this->EE->db->delete('relationships', array('rel_parent_id' => $this->settings['entry_id']));
			}

			if($newdata)
			{
		
				$selected_entries = array();
				$selected_entries = explode('|', $newdata);

				foreach($selected_entries as $key => $val)
				{

					$reldata = array(
						'type'       => 'channel',
						'parent_id'  => $this->settings['entry_id'],
						'child_id'   => $val
					);
					
					$this->EE->functions->compile_relationship($reldata, TRUE);
				}
			}
			else
			{
				return NULL;
			}
						
		}
		
		public function validate($data)
		{
			return TRUE;
		}
		
		// settings for the field,
		// we're just setting which channels should be shown
		public function display_settings($data)
		{
						
			// get mr channel_model
			$this->EE->load->model('channel_model');
			
			// get our channels
			$channels_query = $this->EE->channel_model->get_channels();

			// loop through the channels
			foreach ($channels_query->result_array() as $channel)
			{
				$channel_id = $channel['channel_id'];
				$channel_title = $channel['channel_title'];
				$channel_options[$channel_id] = $channel_title;
			}
			
			$selected_channels = NULL;
			
			// grab the selected channels if they've been set
			if(isset($data['channel_preferences']))
			{
				$selected_channels = explode(',', $data['channel_preferences']) ;
			}
									
			// add the table row for selecting channels, and the multiselect
			// @todo let users build a list of manual options, not just entries
			
			if(isset($channel_options))
			{
				
				$this->EE->table->add_row(
					$this->EE->lang->line('select_channels'),
					form_multiselect('channel_preferences[]', $channel_options, $selected_channels)
				);
				
				$store_ee_relationships = NULL;
				
				if(isset($data['store_ee_relationships']))
				{
					$store_ee_relationships = $data['store_ee_relationships'];
				}
	
				$this->EE->table->add_row(
					$this->EE->lang->line('store_ee_relationships'),
					form_checkbox('store_ee_relationships', 1, $store_ee_relationships)
				);
				
			}
 		}

	 	public function save_settings($data)
		{
			
			$channel_preferences = '';
			
			$channel_preferences = $this->EE->input->post('channel_preferences');			
			
			if(is_array($channel_preferences))
			{
				$channel_preferences = implode(',', $channel_preferences);
			}
			

			$store_ee_relationships = $this->EE->input->post('store_ee_relationships');
						
			return array(
				'channel_preferences'	=> $channel_preferences,
				'store_ee_relationships' => $store_ee_relationships
			);
		}		

		function install()
		{
			// zip
		}

		function unsinstall()
		{
			// move along, nothing to see
		}
	}
	//END CLASS
	
/* End of file ft.the_selectatron.php */