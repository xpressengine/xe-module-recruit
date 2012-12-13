<?php
    /**
     * @class  recruitAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit module admin controller class
     **/

    class recruitAdminController extends recruit {

        function init() {
        }

        /**
         * @brief create the Recruit module
         */
        function procRecruitAdminCreation($args = NULL) {
            // get the module model and controller
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // get the params
            $args = Context::getRequestVars();
            $args->module = 'recruit';
            $args->mid = $args->recruitName;
            unset($args->recruitName);

            //add the login user to the admin_mail list
            $args->admin_mail = $args->admin_mail;

            if($args->use_category!='Y') $args->use_category = 'N';
            if(!in_array($args->order_type,array('asc','desc'))) $args->order_type = 'asc';

            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
                $mSrl = $output->get('module_srl');
                if($output->toBool()) $this->_addDefaultExtVar($mSrl);
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            $this->_insertFilePartConfig($output->get('module_srl'));

            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
            if (Context::get('success_return_url')){
	            $this->setRedirectUrl(Context::get('success_return_url'));
    	    }
    	    else
    	    {
    		    $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRecruitAdminCreate', 'module_srl', $output->get('module_srl')));
    	    }
        }

        /**
         * @brief delete the Recruit module
         */
        function procRecruitAdminDeleteRecruit() {
            $module_srl = Context::get('module_srl');

            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

			$obj->module_srl = $module_srl;
			$deleteModuleJobVars= executeQuery('recruit.deleteJobVarsByModule', $obj);
			$deleteModuleApplicantVars= executeQuery('recruit.deleteApplicantExtraVarsByModule', $obj);
			$deleteModuleLocation= executeQuery('recruit.deleteLocationByModule', $obj);

            $this->add('module','recruit');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin',  'act', 'dispRecruitAdminList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief insert/update/delete the Recruit location
         */	
		function procRecruitAdminInsertLocation(){
			$args = Context::getRequestVars();
			$module_srl = Context::get('module_srl');
			$type = Context::get('type');

			$obj->module_srl  = $module_srl;
			$obj->location = $args->location;

			if($type == 'delete'){
				$obj->location_srl = $args->location_srl;
				$output = executeQuery('recruit.deleteLocation', $obj);
			}else{
				if(!$args->location_srl){
					$obj->location_srl =  getNextSequence();
					$output = executeQuery('recruit.insertLocation', $obj);
				}else{
					$obj->location_srl = $args->location_srl;
					$output = executeQuery('recruit.updateLocation', $obj);
				}
			}

			if(!$output->toBool()) return $output;

        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $obj->module_srl, 'act', 'dispRecruitAdminLocation');
				header('location:'.$returnUrl);
				return;
			}
		}

        /**
         * @brief insert default extra variables
         */			
		function _addDefaultExtVar($mSrl)
        {
            $defaultExts = array('salary' => array('name' => 'Salary','type'=>'text',  'is_required'=>'N'));

            $oDocumentAdminController = &getAdminController('document');
            Context::set('module_srl', $mSrl);
            Context::set('search', 'Y');
            foreach($defaultExts as $key => $var)
            {
                Context::set('eid', $key);
                foreach($var as $varName => $val)
                {
                    Context::set($varName, $val);
                }
                $oDocumentAdminController->procDocumentAdminInsertExtraVar();
            }
        }

        /**
         * @brief insert default  file config
         */	
        function _insertFilePartConfig($module_srl)
        {
            Context::set('allowed_filetypes', '*.pdf;*.docx;*.doc');
            Context::set('target_module_srl', $module_srl);
            Context::set('allowed_attach_size', 2);
            Context::set('allowed_filesize', 2);
            Context::set('allowed_attach_size', 2);
            Context::set('error_return_url', getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRecruitAdminCreate', 'module_srl', $module_srl));
            Context::set('module', 'file');
            Context::set('ruleset', 'fileModuleConfig');
            Context::set('success_return_url', getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRecruitAdminCreate', 'module_srl', $module_srl));
            Context::set('target_module_srl', $module_srl);

            $oFileAdminController = &getAdminController('file');
            $oFileAdminController->procFileAdminInsertModuleConfig();
        }

    }