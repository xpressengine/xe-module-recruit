<?php
    /**
     * @class  recruitView
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit view class
     **/

    class recruitView extends recruit {

        var $module_info = NULL;
        var $rSrl = NULL;
        var $viewList = array('Job_Title','category', 'Location','Open_Date','Close_Date');

        /**
         * @brief class initialization
         **/
        function init() {
            if(!Context::get('mid')) Context::set('mid',$this->module_info->mid);

            $oDocumentModel = &getModel('document');
			$oRecruitModel = &getModel('recruit');

			// get module categories list
            if($this->module_info->use_category=='Y'){
                $category_list = $oDocumentModel->getCategoryList($this->module_info->module_srl);
                Context::set('category_list', $category_list);
            }

            // get module locations list
            if($this->module_info->use_location=='Y'){
              	$location_list = $oRecruitModel->getLocationList($this->module_info->module_srl);
				Context::set('location_list', $location_list);
            }

            //set template file path
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_recruit_default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);

            //set the default recruit js file
            Context::addJsFile($this->module_path.'tpl/js/recruit.js');

            $oModuleModel = &getModel('module');
            $grantInfo = $oModuleModel->getGrant($this->module_info, Context::get('logged_info'));
            $this->grantInfo = $grantInfo;
            Context::set('grantInfo',$grantInfo);
        }

        /**
         * @brief display the job list
         */
		function dispJobList()
		{
		    $oRecruitModel = &getModel('recruit');
		    $args = Context::getRequestVars();
		    $args->module_srl = $this->module_info->module_srl;

            // get page param
            $page = Context::get('page') ? Context::get('page'):1;
            $listCount = $this->module_info->list_count ? $this->module_info->list_count:10;
            $pageCount = $this->module_info->page_count ? $this->module_info->page_count:10;
            $pageArgs = array('page' => $page, 'pageCount' => $pageCount, 'listCount' => $listCount);
            $isPage = TRUE;

            // sort_index, order_type
            $args->order_type = $this->module_info->order_type;
            $args->sort_index = $this->module_info->order_target;
			
			// search form category, location, open date, search keywords
			if($this->module_info->use_category == 'Y')
				$args->category_srl = $args->category_srl?$args->category_srl:0;
			if($args->category_srl == 0) unset($args->category_srl);

			if($this->module_info->use_location == 'Y')
				$args->location_srl = $args->location_srl?$args->location_srl:0;
			if($args->location_srl == 0) unset($args->location_srl);

			if($args->date_search){
				switch($args->date_search){
					case "today":
						$args->open_date =  date('Ymd000000', time());
						break;
					case "last_three_days":
						$last_three_days = strtotime("-3 day");
						$args->open_date =  date('Ymd000000', $last_three_days);
						break;
					case "last_week":
						$last_week = strtotime("-1 week");
						$args->open_date =  date('Ymd000000', $last_week);
						break;
					case "last_month":
						$last_month = strtotime("-1 month");
						$args->open_date =  date('Ymd000000', $last_month);
						break;
					case "last_two_months":
						$last_two_months = strtotime("-2 month");
						$args->open_date =  date('Ymd000000', $last_two_months);
						break;
				}
			}
			$args->search_keyword = $args->search_keyword?$args->search_keyword:null;

            $output = $oRecruitModel->getJobList($args, $isPage, $pageArgs);

            // get category infomation
            $this->_dispRecruitCategoryList();

            // get job extra vars
            $this->_getJobExtraVar();

            Context::set('jobList', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile("job_list.html");
		}

        /**
         * @brief get job extra variables
         */
        function _getJobExtraVar()
        {
            $oDocModel = &getModel('document');
            $extraKeys = $oDocModel->getExtraKeys($this->module_info->module_srl);
            Context::set('extraKeys', $extraKeys);
        }

		/**
         * @brief display the job write page
         */
		function dispJobWrite()
		{
			// check if it is not a admin user 
			$logged_info = Context::get('logged_info');
            if($logged_info->is_admin != 'Y') return new Object(-1, 'msg_not_permitted');

            $oDocumentModel = &getModel('document');

            //if has savedDoc, get the savedDoc
            $document_srl = Context::get('document_srl');
			$oDocument = $oDocumentModel->getDocument($document_srl);

			if(!$oDocument->isExists()) {
                $oDocument->add('module_srl', $this->module_srl);
                $oDocument->add('title', Context::get('title'));
				$default_content = "<h4>Job Responsibilities:</h4><p>- </p><p>-</p><p>-</p>
													  <h4>Basic Qualification<br /></h4><p>- </p><p>-</p><p>-</p>
													  <h4>Preferred Qualification<br /></h4><p>- </p><p>-</p><p>-</p>";
				$oDocument->add('default_content', $default_content);
            }

            //if there is not granted and the document is not exsit, login again
            if($oDocument->isExists() && !$oDocument->isGranted())
            {
                return $this->setTemplateFile('input_password_form');
            }

			$oRecruitModel = &getModel('recruit');
			$job_info = $oRecruitModel->getJobBySrl($document_srl);
			Context::set('job_info', $job_info);

            Context::set('document_srl',$document_srl);
            Context::set('oDocument', $oDocument);

            //add the xml_js_filter in to the header 적용
            $oDocumentController = &getController('document');
            $oDocumentController->addXmlJsFilter($this->module_info->module_srl);

            //get and set the extra variable to the Context
            if($document_srl)
            {
                $extraVar = $oDocument->getExtraVars();
            }
            else
            {
                $extraVar = $oDocumentModel->getExtraKeys($this->module_info->module_srl);
            }
            Context::set('extra_keys', $extraVar);

			$oSecurity = new Security();
			$oSecurity->encodeHTML('category_list.text', 'category_list.title');

		    $this->setTemplateFile('job_write.html');
		}

		/**
         * @brief display applicants list
         */
        function _dispRecruitAppList()
        {
            $oRecruitModel = &getModel('recruit');

            $page = Context::get('page') ? Context::get('page'):1;
            $listCount = Context::get('comment_list_count') ? Context::get('comment_list_count'):10;
            $pageArg = array('page' => $page, 'listCount' => $listCount);

            //get application list
            $isPage = TRUE;

            $args->document_srl = Context::get('document_srl');
			$args->module_srl = $this->module_info->module_srl;
            $applicant_list = $oRecruitModel->getApplicationList($args , $isPage, $pageArg);

            Context::set('applicant_list',$applicant_list->data);
            Context::set('page_navigation', $applicant_list->page_navigation);
        }

		/**
         * @brief display job details page
         */
        function dispRecruitJobdetail() {
            //get job infomation
            $oRecruitModel= &getModel('recruit');
            $document_srl = Context::get('document_srl');
            if(!$document_srl) {
                return $this->dispMessage('msg_not_permitted');
            }
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            Context::set('oDocument', $oDocument);

			$logged_info = Context::get('logged_info');
			if($logged_info){
				$application_info =  $oRecruitModel->getApplicationByMember($this->module_info->module_srl, $document_srl, $logged_info->member_srl);
				Context::set('application_info', $application_info);
			}

			$job_info = $oRecruitModel->getJobBySrl($document_srl);
			Context::set('job_info', $job_info);

            //get Application List
            $this->_dispRecruitAppList();

            //get category infomation
            $this->_dispRecruitCategoryList();

            $this->setTemplateFile("job_view.html");
        }

		/**
         * @brief display job application form page
         */
        function disRecruitApply()
        {
            //get job infomation
            $oRecruitModel = &getModel('recruit');
            $document_srl = Context::get('document_srl');
            if(!$document_srl || !$this->grantInfo->write_comment) {
                return $this->dispMessage('msg_not_permitted');
            }
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            Context::set('oDocument', $oDocument);

			$logged_info = Context::get('logged_info');
			if($logged_info){
				$application_info =  $oRecruitModel->getApplicationByMember($this->module_info->module_srl, $document_srl, $logged_info->member_srl);
				Context::set('application_info', $application_info);
			}

			$oFileModel = &getModel('file');
			$file_config = $oFileModel->getFileModuleConfig($this->module_info->module_srl);
			if($file_config) Context::set('file_config', $file_config);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

            $this->setTemplateFile('application_form');
        }

        function dispMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

        function _dispRecruitCategoryList(){
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = &getModel('document');
                Context::set('category_list', $oDocumentModel->getCategoryList($this->module_info->module_srl));

				$oSecurity = new Security();
				$oSecurity->encodeHTML('category_list.', 'category_list.childs.');
            }
        }

    }