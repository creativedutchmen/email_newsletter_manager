<?php

// ini_set('display_errors', 'On');
// error_reporting(E_ALL | E_STRICT);

	if(!defined('ETMDIR')) define('ETMDIR', EXTENSIONS . "/email_template_manager");
	if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
	require_once(ETMDIR . '/lib/class.emailtemplatemanager.php');
	require_once(ENMDIR . '/lib/class.sendermanager.php');
	require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
	require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');

	/**
	 * Field: Email Newsletter Manager
	 *
	 * @package Email Newsletter Manager
	 **/
	class fieldEmail_Newsletter_Manager extends Field{

		protected $_field_id;
		protected $_entry_id;

		/**
		 * Initialize as unrequired field
		 */
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Email Newsletter Manager');
			$this->_required = false;
			$this->set('location', 'sidebar');
		}

/*-------------------------------------------------------------------------
	Section editor - set up field
-------------------------------------------------------------------------*/
		/**
		 * Displays settings panel in section editor
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
		public function displaySettingsPanel(&$wrapper, $errors=NULL){

			// initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			// build selector for email templates
			$all_templates = EmailTemplateManager::listAll();

			$options = array();
			if(!empty($all_templates) && is_array($all_templates)){
				$templates = $this->get('templates');
				if(is_array($templates)){
					$templates = implode(',',$templates);
				}
				foreach($all_templates as $template){
					$about = $template->about;
					$handle = $template->getHandle();
					$options[] = array(
						$handle,
						in_array($handle, explode(',', $templates)),
						$about['name']
					);
				}
			}
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label(__('Email Templates'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][templates][]', $options, array('multiple'=>'multiple')));

			if(isset($errors['templates'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['templates']));
			}
			else{
				$group->appendChild($label);
			}
			$wrapper->appendChild($group);

			// build selector for senders
			$all_senders = SenderManager::listAll();

			$options = array();
			if(!empty($all_senders) && is_array($all_senders)){
				$senders = $this->get('senders');
				if(is_array($senders)){
					$senders = implode(',',$senders);
				}
				foreach($all_senders as $sender){
					$options[] = array(
						$sender['handle'],
						in_array($sender['handle'], explode(',', $senders)),
						$sender['name']
					);
				}
			}
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label(__('Newsletter Senders'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][senders][]', $options, array('multiple'=>'multiple')));
			if(isset($errors['senders'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['senders']));
			}
			else{
				$group->appendChild($label);
			}

			// build selector for recipient groups
			$recipient_group_manager = new RecipientgroupManager(Symphony::Engine());
			$all_recipient_groups = $recipient_group_manager->listAll();

			$options = array();
			if(!empty($all_recipient_groups) && is_array($all_recipient_groups)){
				$recipient_groups = $this->get('recipient_groups');
				if(is_array($recipient_groups)){
					$recipient_groups = implode(',',$recipient_groups);
				}
				foreach($all_recipient_groups as $recipient_group){
					$options[] = array(
						$recipient_group['handle'],
						in_array($recipient_group['handle'], explode(',', $recipient_groups)),
						$recipient_group['name']
					);
				}
			}
			$label = Widget::Label(__('Newsletter Recipient Groups'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][recipient_groups][]', $options, array('multiple'=>'multiple')));
			if(isset($errors['recipient_groups'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['recipient_groups']));
			}
			else{
				$group->appendChild($label);
			}
			$wrapper->appendChild($group);

			// append 'show column' checkbox
			$this->appendShowColumnCheckbox($wrapper);
		}

		/**
		 * Checks fields for errors in section editor.
		 *
		 * @param array $errors
		 * @param boolean $checkForDuplicates
		 */
		public function checkFields(&$errors, $checkForDuplicates=true){
			if(!is_array($errors)) $errors = array();
			$templates = $this->get('templates');
			if(empty($templates)){
				$errors['templates'] = __('This is a required field.');
			}
			$senders = $this->get('senders');
			if(empty($senders)){
				$errors['senders'] = __('This is a required field.');
			}
			$recipient_groups = $this->get('recipient_groups');
			if(empty($recipient_groups)){
				$errors['recipient_groups'] = __('This is a required field.');
			}
			parent::checkFields($errors, $checkForDuplicates);
		}

		/**
		* Save fields settings in section editor
		*/
		public function commit(){
			// prepare commit
			if(!parent::commit()) return false;
			$id = $this->get('id');
			if($id === false) return false;

			// set up fields
			$fields = array();
			$fields['field_id'] = $id;
			if($this->get('templates')){
				$fields['templates'] = implode(',', $this->get('templates'));
			}
			if($this->get('senders')){
				$fields['senders'] = implode(',', $this->get('senders'));
			}
			if($this->get('recipient_groups')){
				$fields['recipient_groups'] = implode(',', $this->get('recipient_groups'));
			}

			// delete old field settings
			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			// save new field settings
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		/**
		 * Create database table for entries
		 */
		public function createTable(){
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_".$this->get('id')."` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `author_id` int(11) unsigned NOT NULL,
				  `newsletter_id` int(11) unsigned NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
			return true;
		}

/*-------------------------------------------------------------------------
	Publish: entries table
-------------------------------------------------------------------------*/
		/**
		 * Append newsletter status to entry table
		 */
		public function prepareTableValue($data, XMLElement $link=NULL){
			if(!is_array($data) || empty($data)) return;
			$value = null;
			if(isset($data['status'])){
				$value = $data['status'];
			}
			switch ($value){
				case 'processing':
					$value = __('Processing...');
					break;
				case 'cancel':
					$value = __('Cancelling...');
					break;
				case 'error':
					$value = __('ERROR');
					break;
				case 'sent':
					$value = __('Sent');
					break;
				default:
					$value = NULL;
			}
			return parent::prepareTableValue(array('value' => $value), $link);
		}

		/**
		 * Is the table column sortable?
		 *
		 * @return boolean
		 */
		public function isSortable(){
			return true;
		}

/*-------------------------------------------------------------------------
	Publish: edit
-------------------------------------------------------------------------*/
		/**
		 * Displays publish panel in content area.
		 *
		 * @param XMLElement $wrapper
		 * @param $data
		 * @param $flagWithError
		 * @param $fieldnamePrefix
		 * @param $fieldnamePostfix
		 */
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/email_newsletter_manager/assets/email_newsletter_manager.publish.js', 1001);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/email_newsletter_manager/assets/email_newsletter_manager.publish.css', 'screen', 1000);

			$this->_field_id = $this->get('id');
			$this->_entry_id = Administration::instance()->Page->_context['entry_id'];


			$status = NULL;

			// get newsletter properties
			$newsletter_properties = array();
			if($data['newsletter_id']){
				$newsletter = EmailNewsletterManager::get($data['newsletter_id']);
				$newsletter_properties = $newsletter->getProperties();
			}

			// get configured templates
			$all_templates = EmailTemplateManager::listAll();

			$templates_options = array();
			if(!empty($all_templates) && is_array($all_templates)){
				$templates = explode(',', $this->get('templates'));
				foreach($all_templates as $template){
					$about = $template->about;
					$handle = $template->getHandle();
					if(in_array($handle, $templates)){
						$templates_options[] = array(
							$handle,
							$about['name'],
						);
					}
				}
			}

			// get configured senders
			$all_senders = SenderManager::listAll();

			$senders_options = array();
			if(!empty($all_senders) && is_array($all_senders)){
				$senders = explode(',', $this->get('senders'));
				foreach($all_senders as $sender){
					if(in_array($sender['handle'], $senders)){
						$senders_options[] = array(
							$sender['handle'],
							$sender['name'],
						);
					}
				}
			}

			// get configured recipient groups
			$all_recipient_groups = RecipientgroupManager::listAll();

			$recipient_groups_options = array();
			if(!empty($all_recipient_groups) && is_array($all_recipient_groups)){
				$recipient_groups = explode(',', $this->get('recipient_groups'));
				foreach($all_recipient_groups as $recipient_group){
					if(in_array($recipient_group['handle'], $recipient_groups)){
						$recipient_groups_options[] = array(
							$recipient_group['handle'],
							$recipient_group['name'],
						);
					}
				}
			}

			// build header
			$header = new XMLElement('h3', $this->get('label'));
			$wrapper->appendChild($header);

			// build GUI element
			$gui = new XMLElement('div');
			$gui->setAttribute('class', 'email-newsletters-gui');

			// switch status
			switch ($status){
				case "processing":
					break;

				case "cancel":
					break;

				case "error":
					break;

				case "sent":
					break;

				default:
					// build selector for email templates
					if(count($templates_options) > 1){
						$p = new XMLElement('p');
						$options = array();
						$options[] = array(NULL, NULL, __('--- please select ---'));
						foreach($templates_options as $template){
							$options[] = array(
								$template[0],
								$template[0] == $newsletter_properties['template'],
								$template[1]
							);
						}
						$p->appendChild(
							Widget::Label(__('Email Template: '),
							Widget::Select('fields['.$this->get('element_name').'][template]', $options))
						);
						$gui->appendChild($p);
					}
					elseif(count($templates_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][template]',
							$templates_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No email template has been configured.')));
					}

					// build selector for senders
					if(count($senders_options) > 1){
						$p = new XMLElement('p');
						$options = array();
						$options[] = array(NULL, NULL, __('--- please select ---'));
						foreach($senders_options as $sender){
							$options[] = array(
								$sender[0],
								$sender[0] == $newsletter_properties['sender'],
								$sender[1]
							);
						}
						$p->appendChild(
							Widget::Label(__('Sender: '),
							Widget::Select('fields['.$this->get('element_name').'][sender]', $options))
						);
						$gui->appendChild($p);
					}
					elseif(count($senders_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][sender]',
							$senders_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No sender has been configured.')));
					}

					// build checkboxes for recipient groups
					if(count($recipient_groups_options) > 1){
						$p = new XMLElement('p', __('Recipient Groups: '));
						$gui->appendChild($p);
						$p = new XMLElement('p', NULL, array('class' => 'recipient-groups'));
						foreach($recipient_groups_options as $recipient_group){
							$label = Widget::Label();
							$input = Widget::Input(
								'fields['.$this->get('element_name').'][recipient_groups][]',
								$recipient_group[0],
								'checkbox',
								(!empty($recipient_group[0]) && in_array($recipient_group[0], explode(',', $newsletter_properties['recipients'])))
								? array('checked' => 'checked')
								: NULL
							);
							$label->setValue($input->generate() . $recipient_group[1]);
							$label->setAttribute('class', 'recipient-group');
							$p->appendChild($label);
						}
						$gui->appendChild($p);
					}
					elseif(count($recipient_groups_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][recipient_groups][]',
							$recipient_groups_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No recipient group has been configured.')));
					}

					// build 'save and send' button
					if(isset($this->_entry_id)){
						$p = new XMLElement('p');
						$p->appendChild(new XMLElement(
							'button',
							__('Send'),
							array(
								'name' => 'action[save]',
								'type' => 'submit',
								'value' => 'en-send:'.$this->_field_id.':'.$this->_entry_id.':'.DOMAIN.':'.$live_mode,
								'class' => 'send',
								'id' => 'savesend'
							)
						));
						$gui->appendChild($p);
					}
					else{
						$p = new XMLElement('p', __('The entry has not been created yet. No emails can be sent.'));
						$gui->appendChild($p);
					}
			}
			$wrapper->appendChild($gui);

			// // standard:
			// if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			// else $wrapper->appendChild($label);

		}

		/**
		 * Prepares field values for database.
		 */
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null){
			$status = self::__OK__;
			if(empty($data)) return NULL;

			/*
				TODO when saving a newsletter, we __must__ check if
				the properties are valid (i.e. configured in the
				section editor);
				otherwise it would be super-simple to send with
				unwanted or invalid properties;
			*/

			$entry_data = array();
			if($entry_id){
				// grab existing entry data
				$entry_data = Symphony::Database()->fetchRow(0, sprintf(
					"SELECT *
					 FROM `tbl_entries_data_%d`
					 WHERE `entry_id` = %d
					 LIMIT 1",
					$this->get('id'),
					$entry_id
				));
			}

			$newsletter = EmailNewsletterManager::save(array(
				'id'               => $entry_data['newsletter_id'],
				'template'         => $data['template'],
				'recipients'       => implode(',', $data['recipient_groups']),
				'sender'           => $data['sender'],
				'started_by'       => Administration::instance()->Author->get('id'),
			));
			$properties = $newsletter->getProperties();
			$newsletter_id = $properties['id'];

			$result = array(
				'author_id' => Administration::instance()->Author->get('id'),
				'newsletter_id' => $newsletter_id,
			);
			return $result;
		}

/*-------------------------------------------------------------------------
	Output
-------------------------------------------------------------------------*/
		/**
		 * Allow data source filtering?
		 * @return: boolean
		 */
		public function canFilter(){
			return false;
		}

		/**
		 * Allow data source parameter output?
		 * @return: boolean
		 */
		public function allowDatasourceParamOutput(){
			return true;
		}

		/**
		 * get param pool value
		 * @return: string email newsletter sender ID
		 */
		public function getParameterPoolValue($data){
			return $data['sender_id'];
		}

		/**
		 * Fetch includable elements (DS editor)
		 * @return: array() elements
		 */
		public function fetchIncludableElements(){
			return array(
				$this->get('element_name')
			);
		}

		/**
		 * Append element to datasource output
		 */
		public function appendFormattedElement(&$wrapper, $data, $encode = false){
			$node = new XMLElement($this->get('element_name'));
			$node->setAttribute('author-id', $data['author_id']);
			$node->setAttribute('status', $data['status']);
			$node->setAttribute('total', $data['stats_rec_total']);
			$node->setAttribute('sent', $data['stats_rec_sent']);
			$node->setAttribute('errors', $data['stats_rec_errors']);
			$node->appendChild(new XMLElement('subject', $data['subject']));

			// load configuration;
			// use saved (entry) config XML if available (i.e.: if the email newsletter has been sent);
			// fallback: the field's configuration XML
			if(!empty($data['config_xml'])){
				$config = simplexml_load_string($data['config_xml']);
			}
			else{
				$field_data = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_fields_email_newsletter` WHERE `field_id` = ".$this->get('id')." LIMIT 1");
				$config = simplexml_load_string($field_data['config_xml']);
			}

			// sender
			$sender = new XMLElement('senders');
			$sender_id = $data['sender_id'];
			if(!empty($sender_id)){
				$sender_data = $config->xpath("senders/item[@id = $sender_id]");
				$sender->setValue((string)$sender_data[0]);
				$sender->setAttribute('id', $data['sender_id']);
			}
			$node->appendChild($sender);

			// recipients
			$rec_groups = $config->xpath('recipients/group');
			$recipient_group_ids = explode(',', $data['rec_group_ids']);
			$recipients = new XMLElement('recipients');
			foreach($rec_groups as $rec_group){
				if(in_array($rec_group['id'], $recipient_group_ids)){
					$group = new XMLElement('group', $rec_group);
					$group->setAttribute('id', $rec_group['id']);
					$recipients->appendChild($group);
				}
			}
			$node->appendChild($recipients);

			$wrapper->appendChild($node);
		}

		/**
		 * Provide example form markup
		 */
		public function getExampleFormMarkup(){
			// nothing to show here
			return;
		}

/*-------------------------------------------------------------------------
	Helpers
-------------------------------------------------------------------------*/
		/**
		 * Build the email newsletter status table (counts)
		 *
		 * @param array $entry_data
		 * @return string HTML table
		 */
		private function __buildStatusTable($entry_data){
			$aTableHead = array(
				array(__('Total'), 'col'),
				array(__('Sent'), 'col'),
				array(__('Errors'), 'col'),
			);
			$td1 = Widget::TableData($entry_data['stats_rec_total'] ? $entry_data['stats_rec_total'] : '-');
			$td2 = Widget::TableData($entry_data['stats_rec_sent'] ? $entry_data['stats_rec_sent'] : '0');
			$td3 = Widget::TableData($entry_data['stats_rec_errors'] ? $entry_data['stats_rec_errors'] : '0');
			$aTableBody = array();
			$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3));
			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				NULL
			);
			$table->setAttributeArray(array('class' => 'status'));
			return $table;
		}
	}
