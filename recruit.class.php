<?php
    /**
     * @class recruit
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit package
     **/

    class recruit extends ModuleObject {

        var $search_option = array();

        var $order_target = array( 'regdate', 'title',  'list_order', 'update_order');

        //check if the login user is manager
        function _checkIsManager()
        {
            $logInfo = Context::get('logged_info');
            $managerAcount = explode(',', $this->module_info->admin_mail);
            if($logInfo && in_array($logInfo->email_address, $managerAcount))
            {
                $this->isManager = TRUE;
            }
            Context::set('isManager', $this->isManager);
            return $this->isManager;
        }

        function _checkPermission($allowMSrls, $comment_srl = NULL)
        {
            $flag = FALSE;
            $loggedInfo = Context::get('logged_info');
            if(!is_array($allowMSrls))
            {
                $allowMSrls = array($allowMSrls);
            }

            if($this->isManager || in_array($loggedInfo->member_srl, $allowMSrls))
            {
                $flag = TRUE;
            }

            if(!$flag && $comment_srl)
            {
                $oCommentModel = &getModel('comment');
                $oComment = $oCommentModel->getComment($comment_srl);
                if($oComment->isGranted()) $flag = TRUE;
            }
            return $flag;
        }

        /**
         * @brief module installation
         **/
        function moduleInstall()
        {
            $oModuleController = &getController('module');
            $oModuleController->insertTrigger('recruit.dispRecruitAdminAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');
            return new Object();
        }

        /**
         * @brief check update method
         **/
        function checkUpdate()
        {
            $oModuleModel = &getModel('module');

            if(!$oModuleModel->getTrigger('recruit.dispRecruitAdminAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before')) return true;
            return false;
        }

        /**
         * @brief update module
         **/
        function moduleUpdate()
        {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getTrigger('recruit.dispRecruitAdminAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before'))
	            	$oModuleController->insertTrigger('recruit.dispRecruitAdminAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');
            return new Object(0, 'success_updated');
        }

    	function moduleUninstall()
    	{
    	}

        /**
         * @brief create cache file
         **/
        function recompileCache() {
        }
    }