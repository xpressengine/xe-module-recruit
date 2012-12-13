<?php
    /**
     * @class  recruitModel
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit module Model class
     **/
    require_once _XE_PATH_ . "modules/comment/comment.item.php";

    class recruitModel extends recruit {

        function init() {
        }

        /**
         * get recruit modules List
         */
        function getRecruits($args = NULL, $isPage = TRUE, $pageArgs = array('page' => 1, 'listCount' => 5, 'pageCount' => 10))
        {
             //page paramets
            if($isPage && $pageArgs)
            {
                $args->page = $pageArgs['page'] ? $pageArgs['page']:1;
                $args->list_count = $pageArgs['listCount'] ? $pageArgs['listCount']:5;
                $args->page_count = $pageArgs['pageCount'] ? $pageArgs['pageCount']:10;
            }
            $output = executeQuery('recruit.getRecruits', $args);
            return $output;
        }

        /**
         *  brief get jobs List
         */
        function getJobList($obj = NULL, $isPage = TRUE, $pageArgs = array('page' => 1, 'listCount' => 20, 'pageCount' => 10), $needExtra = TRUE)
        {
			if(!$obj->module_srl)  return new Object(-1, "msg_invalid_request");
			$args->module_srl = $obj->module_srl;

    	    //page paramets
            if($isPage && $pageArgs)
            {
                $args->page = $pageArgs['page'] ? $pageArgs['page']:1;
                $args->list_count = $pageArgs['listCount'] ? $pageArgs['listCount']:20;
                $args->page_count = $pageArgs['pageCount'] ? $pageArgs['pageCount']:10;
            }
			if($obj->category_srl) $args->category_srl = $obj->category_srl;
			if($obj->location_srl) $args->location_srl = $obj->location_srl;
			if($obj->open_date) $args->regdate = $obj->open_date;
			if($obj->search_keyword)  $args->search_keyword = $obj->search_keyword;

			$output = executeQuery('recruit.getJobList', $args);
			if(!$output->data) return null;

    	    return $output;
        }

        /**
         *  brief get job info by a given document_srl
         */
		function getJobBySrl($document_srl)
        {
			if(!$document_srl)  return new Object(-1, "msg_invalid_request");

			$args->document_srl = $document_srl;
			$output = executeQuery('recruit.getJobBySrl', $args);
			if(!$output->data) return null;

    	    return $output->data;
        }

        /**
         * get Application List
         */
        function getApplicationList($args, $isPage = TRUE, $pageArgs = array('page' => 1, 'listCount' => 10, 'pageCount' => 10))
        {
			if(!$args->module_srl || !$args->document_srl)  return new Object(-1, "msg_invalid_request");
            $args->sort_index = 'last_update';
            $args->page = $pageArgs['page'] ? $pageArgs['page']:1;
            $args->list_count = $pageArgs['listCount'] ? $pageArgs['listCount']:10;
            $args->page_count = $pageArgs['pageCount'] ? $pageArgs['pageCount']:10;

			// comment.getTotalCommentList query execution
            $output = executeQueryArray("recruit.getApplicationList", $args);

            // return when no result or error occurance
            if(!$output->toBool()||!count($output->data)) return $output;
            foreach($output->data as $key => $val) {
                unset($_oComment);
                $_oComment = new CommentItem(0);
                $_oComment->setAttribute($val);
				$file_list = $_oComment->getUploadedFiles();
				$applicant_cv = $file_list[0];
				$_oComment->cv = $applicant_cv;
                $output->data[$key] = $_oComment;
            }

            return $output;
        }

        /**
         * get application info by srl 
         */
        function getApplicationBySrl($comment_srl)
        {
			if(!$comment_srl)  return new Object(-1, "msg_invalid_request");
			
			$args->comment_srl = $comment_srl;
            $output = executeQuery("recruit.getApplicationBySrl", $args);

            // return when no result or error occurance
            if(!$output->toBool()||!count($output->data)) return $output;

			$comment = $output->data;
			unset($_oComment);
			$_oComment = new CommentItem(0);
            $_oComment->setAttribute($comment);
			$file_list = $_oComment->getUploadedFiles();
			$applicant_cv = $file_list[0];
			$_oComment->cv = $applicant_cv;
            $output->data = $_oComment;

            return $output->data;
        }

        /**
         * get Application By Member 
         */
		function getApplicationByMember($module_srl, $document_srl, $member_srl)
        {
			if(!$module_srl || !$document_srl || !$member_srl)  return new Object(-1, "msg_invalid_request");
            
			$args->module_srl = $module_srl;
			$args->document_srl = $document_srl;
            $args->member_srl = $member_srl;

			$output = executeQuery("recruit.getApplicationByMember", $args);

			 if(!$output->toBool()||!count($output->data)) return $null;

			unset($_oComment);
			$_oComment = new CommentItem(0);
			$_oComment->setAttribute($output->data);
			$file_list = $_oComment->getUploadedFiles();
			$applicant_cv = $file_list[0];
			$_oComment->cv = $applicant_cv;
            return $_oComment;
        }

        /**
         * get location list 
         */
		function getLocationList($module_srl)
        {
			if(!$module_srl)  return new Object(-1, "msg_invalid_request");

			$args->module_srl = $module_srl;
			$output = executeQueryArray('recruit.getLocationList', $args);
			if(!$output->data) return null;
			
			return $output->data;
        }

        /**
         * get location info by location srl 
         */
		function getLocationBySrl($location_srl)
        {
			if(!$location_srl)  return new Object(-1, "msg_invalid_request");

			$args->location_srl = $location_srl;
			$output = executeQuery('recruit.getLocationBySrl', $args);
			if(!$output->data) return null;
			
			return $output->data;
        }
    }