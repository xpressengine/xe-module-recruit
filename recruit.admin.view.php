<?php
    /**
     * @class  recruitAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit module admin view class
     **/

    class recruitAdminView extends recruit {

        function init() {
			 // check module_srl is existed or not
			$module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

             // generate module model object
            $oModuleModel = &getModel('module');

            // get the module infomation based on the module_srl
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
					$this->module_info->use_status = explode('|@|', $module_info->use_status);
                    Context::set('module_info',$module_info);
                }
            }

			 if($module_info && $module_info->module != 'recruit') return $this->stop("msg_invalid_request");

			 // get the module category list
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

			$security = new Security();
			$security->encodeHTML('module_info.');
			$security->encodeHTML('module_category..');

			$template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        /*
        *   @brief display the recruit list
        */
        function dispRecruitAdminList() {
            $oRecruitModel = &getModel('recruit');

            $page = Context::get('page');
            $args = Context::getRequestVars();
            $pageArray = array($page);
            $isPage = TRUE;
            $output = $oRecruitModel->getRecruits($args, $isPage, $page);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('recruitsList', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }

        /*
        *   @brief display the recruit creation/edit page
        */
        function dispRecruitAdminCreate() {
           $oModel = &getModel('recruit');

            //get the skins of the recruit module
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            //get the layout of the recruit module
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            //list order
            foreach($this->order_target as $key)
            {
                $order_target[$key] = Context::getLang($key);
            }
            Context::set('order_target', $order_target);

            $this->setTemplateFile('recuirt_insert');
        }

        /**
         * @brief display the category setting page
         **/
        function dispRecruitAdminCategoryInfo() {
            //get the category content
            $oDocumentModel = &getModel('document');
            $catgegory_content = $oDocumentModel->getCategoryHTML($this->module_info->module_srl);
            Context::set('category_content', $catgegory_content);

            $this->setTemplateFile('category_list');
        }

		/**
         * @brief display location management  page
         **/
        function dispRecruitAdminLocation()
        {
			$oRecruitModel = &getModel('recruit');
			$recruit_locations = $oRecruitModel->getLocationList($this->module_info->module_srl);
			Context::set('recruit_locations', $recruit_locations);

            $this->setTemplateFile('location_list.html');
        }

        /**
         * @brief display ExtraVars setting page
         **/
        function dispRecruitAdminExtraVars() {
            //get extra variable page
            $oDocumentAdminModel = &getModel('document');
            $extra_vars_content = $oDocumentAdminModel->getExtraVarsHTML($this->module_info->module_srl);
            Context::set('extra_vars_content', $extra_vars_content);

            $this->setTemplateFile('extra_vars');
        }

        /**
         * @brief display Grant setting page
         **/
        function dispRecruitAdminGrantInfo() {
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');
        }

        /**
         * @brief display the addition setting page
         **/
        function dispRecruitAdminAdditionSetup() {
            // initializing content
            $content = '';
            Context::set('module_srl', $this->module_info->module_srl);
            // getting additional setup using the trigger
            $output = ModuleHandler::triggerCall('recruit.dispRecruitAdminAdditionSetup', 'before', $content);
            Context::set('setup_content', $content);

            // setting up the template
            $this->setTemplateFile('addition_setup');
        }

        /**
         * @brief delete recruit page
         **/
         function dispRecruitAdminDelete()
         {
            if(!Context::get('module_srl')) return $this->dispRecruitAdminList();
            if($this->module_info->module != 'recruit') {
                return $this->alertMessage('msg_invalid_request');
            }
			
            $module_info = $this->module_info;

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

			$security = new Security();
			$security->encodeHTML('module_info..mid','module_info..module','module_info..document_count');

            $this->setTemplateFile('recruit_delete');
         }

         /**
         * @brief alert message 
         **/
        function alertMessage($message) {
            $script =  sprintf('<script type="text/javascript"> xAddEventListener(window,"load", function() { alert("%s"); } );</script>', Context::getLang($message));
            Context::addHtmlHeader( $script );
        }

    }