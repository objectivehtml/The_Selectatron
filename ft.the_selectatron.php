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
			'version'	=> '0.2'
		);
		
		// if you want to move the selectatron_assets folder, you can do so here
		var $asset_path = 'expressionengine/third_party/the_selectatron/selectatron_assets/';

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

			// are we displaying this already?
			if (! isset($this->cache['the_selectatron_displayed']))
			{
				$this->EE->cp->add_to_head('<link type="text/css" href="'.$this->asset_path.'css/selectatron.css" rel="stylesheet" />');
				// this plugin for sorting to order saved, otherwise we lose it
				$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->asset_path.'js/jquery.bsmselect.js"></script>');
				$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->asset_path.'js/selectatron.js"></script>');
				// lets not repeat ourselves
				$this->cache['the_selectatron_displayed'] = TRUE;
			}
			
			// activate the bsmSelect!
			$this->EE->javascript->output("
			$(document).ready(function() {

				$('#field_id_".$this->field_id."').bsmSelect({
			        addItemTarget: 'bottom',
			        title: 'Please select an entry',
			        animate: true,
			        removeLabel: 'x',
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
			return $data;
		}
		
		public function replace_tag($data, $params = FALSE, $tagdata = FALSE)
		{
			return $data;
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
			
			// we need to find the old values, and delete any rels that exist
			// if its a new entry, we don't need to do this
			// I was a bit rough at first and simply deleted all the rels that this entry was a parent of..
			if($this->EE->input->post('entry_id'))
			{
				$query = $this->EE->db->get_where('channel_data', array('entry_id' => $this->EE->input->post('entry_id')), 1);
				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						
						if($row['field_id_'.$this->field_id] != '')
						{
							$existing_entries = explode("|", $row['field_id_'.$this->field_id]);							
							// @todo compare existing entries and only 
							// delete unselected instead of all, when its not 1am.
							foreach ($existing_entries as $row => $entry)
							{
								$this->EE->db->where('rel_parent_id', $this->EE->input->post('entry_id'));
								$this->EE->db->where('rel_child_id', $entry);
								$this->EE->db->delete('relationships');
							}
						}
											
					}
				}
			}
			return implode('|', $data);
			// the post save function below will save the rel data again.

		}
		
		// Using post save because we can get the 
		// entry_id easily for new entries.
		function post_save($data)
		{
		
			// print_r($data);
			// check if the user has made some selections,
			// and the preference setting for storing relationships has been set
			if($data != '' && ($this->settings['store_ee_relationships'] == TRUE))
			{
				
				$selected_entries = array();
				$selected_entries = explode('|', $data);
				
				// we need to get the channel id now...
				$query = $this->EE->db->get_where('channel_titles', array('entry_id' => $this->settings['entry_id']), 1);
	
				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$channel_id = $row['channel_id'];
					}
				}
				else
				{
					return NULL;
				}
	
				// we loop though each of the users selections,
				// and compile the relationships
				foreach($selected_entries as $key => $val)
				{
					$reldata = array(
						'type'       => 'channel',
						'parent_id'  => $this->settings['entry_id'],
						'child_id'   => $val,
						'related_id' => $channel_id
					);
					
					$this->EE->functions->compile_relationship($reldata, TRUE);
				}
			}
			else
			{
				return NULL;
			}
			
			// now we pray to jahova.
						
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
 		

 		
	 	public function save_settings($data)
		{

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