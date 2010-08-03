<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Introvert: The Selectatron
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
			'version'	=> '1.0'
		);

		public function The_selectatron_ft()
		{
			parent::EE_Fieldtype();
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
			$asset_path = '/system/expressionengine/third_party/the_selectatron/';
			
			// are we displaying this already?
			if (! isset($this->cache['the_selectatron_displayed']))
			{
				$this->EE->cp->add_to_head('<link type="text/css" href="'.$asset_path.'css/selectatron.css" rel="stylesheet" />');
				// this plugin for sorting to order saved, otherwise we lose it
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
			        title: 'Please select an option',
			        animate: true,
			        listClass: 'selectatron".$this->field_id."',
			        // highlight: true,
			        sortable: true
			      });

				sort('.selectatron".$this->field_id.">li', 'i');

				
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
					$r .= "<optgroup label='" . $entry['channel_title'] ."'>";
				}
				
				$key = array_search($entry['entry_id'], $data);
				
				$selected = (in_array($entry['entry_id'], $data)) ? " selected='selected' rel='".$key."' " : "";
				$r .= "<option value='" . $entry['entry_id'] . "'" . $selected . " > " . $entry['entry_title'] . "</option>";
			}
			$r .= "</select>";

			return $r;

		}
		
		function pre_process($data)
		{
			// nada
		}
		
		public function replace_tag($data, $params = FALSE, $tagdata = FALSE)
		{
			// nada	
		}
		
	 	public function save($data){
	 	
			if ( ! is_array($data))
			{
				$data = array($data);
			}

			foreach($data as $key => $val)
			{
				$data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), $val);
			}

			return implode('|', $data);

		}
		
		function post_save($data)
		{
			// nada
		}
		
		public function validate($data)
		{
			return TRUE;
		}
		
		// settings for the field,
		// we're just setting which channels should be shown
		public function display_settings($data)
		{
			
			$this->EE->lang->loadfile('the_selectatron');
			
			// get a little help from our old friend mr channel_model
			$this->EE->load->model('channel_model');
			
			// get our channels groups
			$channels_query = $this->EE->channel_model->get_channels();

			// loop through the channels groups
			foreach ($channels_query->result_array() as $channel)
			{
				$channel_id = $channel['channel_id'];
				$channel_title = $channel['channel_title'];
				$channel_options[$channel_id] = $channel_title;
			}

			// grab the selected channels if they've been set
			if(isset($data['channel_preferences']))
			{
				$selected_channels = explode(',', $data['channel_preferences']) ;
			}
									
			// add the table row for selecting channels, and the multiselect
			// @todo let users build a list of manual options, not just entries
			$this->EE->table->add_row(
				$this->EE->lang->line('select_channels'),
				form_multiselect('channel_preferences[]', $channel_options, $selected_channels)
			);

 		}
 		

 		
	 	public function save_settings($data)
		{

			$channel_preferences = $this->EE->input->post('channel_preferences');
			if(is_array($channel_preferences))
			{
				$channel_preferences = implode(',', $channel_preferences);
			}
						
			return array(
				'channel_preferences'	=> $channel_preferences
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