<?php
    /**
     * @class  recruitController
     * @author NHN (developers@xpressengine.com)
     * @brief  recruit module controller class
     **/

    class recruitController extends recruit{

        /**
         * @brief
         **/
        function init() {
        }

        /**
         * @brief insert/update jobs 
         **/
        function procRecruitInsertJob() {
            if($this->module_info->module != "recruit") return new Object(-1, "msg_invalid_request");

            $logged_info = Context::get('logged_info');
            if(!$logged_info) return new Object(-1, "msg_not_permitted");

            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';
			$obj->commentStatus = $obj->comment_status;

            //check the title variable
            settype($obj->title, "string");
            if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
            if($obj->title == '') $obj->title = 'Untitled';

            if(!$this->grant->manager) {
                unset($obj->title_color);
                unset($obj->title_bold);
            }

            // document module model object
            $oDocumentModel = &getModel('document');

            // document module controller object
            $oDocumentController = &getController('document');
            $oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            $obj->comment_status = 'ALLOW';
            $obj->notify_message = 'N';
            $obj->member_srl = $logged_info->member_srl;
			$obj->user_name = $logged_info->user_name;
			$obj->nick_name =  $logged_info->nick_name;
			$obj->email_address = $logged_info->email_address;
			$obj->homepage = $logged_info->homepage;
			$obj->user_id = $logged_info->user_id;
			$obj->category_srl = $obj->job_category;
			$obj->location_srl = $obj->job_location;
			$obj->contact_email = $obj->contact_email;
			$obj->contact_address = $obj->contact_address;
			$obj->regdate = date('YmdHis', strtotime($obj->open_date));
			$obj->close_date = date('YmdHis', strtotime($obj->close_date));
			$oDocument->add('member_srl', $obj->member_srl);

            if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl) {
				if(!$oDocument->isGranted()) return new Object(-1,'msg_not_permitted');
                $output = $oDocumentController->updateDocument($oDocument, $obj);
				$args->document_srl = $obj->document_srl;
				$args->location_srl = $obj->location_srl?intval($obj->location_srl):0;
				$args->contact_email = $obj->contact_email;
				$args->contact_address = $obj->contact_address;
				$args->close_date = $obj->close_date;
				$insertJobVars= executeQuery('recruit.updateJobVars', $args);
                $msg_code = 'job_success_updated';
            } else {
                $output = $oDocumentController->insertDocument($obj,$this->grant->manager);
				$args->document_srl = $output->get('document_srl');
				$args->module_srl =  intval($obj->module_srl);
				$args->location_srl = $obj->location_srl?intval($obj->location_srl):0;
				$args->contact_email = $obj->contact_email;
				$args->contact_address = $obj->contact_address;
				$args->close_date = $obj->close_date;
				$insertJobVars= executeQuery('recruit.insertJobVars', $args);
                $msg_code = 'job_success_registed';
                $obj->document_srl = $output->get('document_srl');
            }

            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            $this->setMessage($msg_code);

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispRecruitJobdetail', 'document_srl', $obj->document_srl);
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief delete job(s)
         **/
        function procRecruitDeleteJob() {
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y') return new Object(-1, "msg_not_permitted");

            $document_srl = Context::get('document_srl');
            if(!$document_srl) return new Object(-1,'msg_invalid_document');

            // document module model
            $oDocumentController = &getController('document');
			$oCommentModel = &getModel('comment');

            //delete the job(s)
            $document_srl = explode(',',$document_srl);
            foreach($document_srl as $srl)
            {
				$comment_list = $oCommentModel->getCommentList($srl);
				$comment_list = $comment_list->data;
                $output = $oDocumentController->deleteDocument($srl);
				if(!$output->toBool()) return $output;
				$args->document_srl = $srl;
				$deleteJobVars= executeQuery('recruit.deleteJobVars', $args);
				if($comment_list){
					 foreach($comment_list as $comment){
						$obj->comment_srl = $comment->comment_srl;
						$deleteApplicantVars= executeQuery('recruit.deleteApplicantExtraVars', $obj);
					 }
				}
            }

            $this->add('mid', Context::get('mid'));
            $this->add('page', $output->get('page'));
            $this->setMessage('job_success_deleted');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispJobList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief insert/update applicants 
         **/
        function procRecruitInsertApplicant() {
			$logged_info = Context::get('logged_info');
			
			$bAnonymous = false;
			if(!$logged_info) $bAnonymous = true;

            $vars = Context::getRequestVars();
            $obj->module_srl = $vars->module_srl;
			$obj->document_srl = $vars->document_srl;
			$obj->comment_srl = $vars->comment_srl;
			$obj->email_address = $vars->email_address;
			$obj->content = removeHackTag($vars->self_introduction);
		
			if(!$logged_info) $obj->nick_name =  $vars->applicant_name;

			$check_cv_format = $this->_checkFileValidation($obj->module_srl);
			if(!$check_cv_format){
				return new Object(-1, 'cv_upload_failed');
			}

            //get document item
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($obj->document_srl);
            if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');

            //get comment model and controller object
            $oCommentModel = &getModel('comment');
            $oCommentController = &getController('comment');

            //if has no comment_srl(insert),then generate a new comment_srl
            if(!$obj->comment_srl) {
                $obj->comment_srl = getNextSequence();
            } else {
                $comment = $oCommentModel->getComment($obj->comment_srl);
            }

            //if is a new comment_srl ,then insert the comment. Or update the comment
            if($comment->comment_srl != $obj->comment_srl) {
                $output = $oCommentController->insertComment($obj, $bAnonymous);
                $comment_srl = $output->get('comment_srl');
				$insert_extravars = $this->_insertApplicantExtraVars($comment_srl);
				$msg = 'application_success_registed';
            } else {
				$obj->parent_srl = $comment->parent_srl;
                $output = $oCommentController->updateComment($obj);
                $comment_srl = $obj->comment_srl;
				$update_extravars = $this->_updateApplicantExtraVars($comment_srl);
				$msg = 'application_success_updated';
            }

            if(!$output->toBool()) return $output;

            // Check if upload file is successfully uploaded
            $upload_cv = $this->_insertFile($obj->module_srl, $comment_srl);

            //has upload file and the comment's uploaded_count is zero
            //then update the comment's information to update the attach file number
            $comment = $oCommentModel->getComment($obj->comment_srl);
            if(!$comment->uploaded_count && $_FILES)
            {
                $output = $oCommentController->updateComment($obj);
            }
            if(!$output->toBool()) return $output;

			// send a notification email
			if($this->module_info->use_notification == 'Y'){
				$this->_sendNotifyEmail($obj->comment_srl, $obj->document_srl);
			}

            $this->setMessage($msg);
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $obj->comment_srl);

            if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url')
				                ? Context::get('success_return_url')
				                : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispRecruitJobdetail','document_srl',$obj->document_srl);
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief delete applicant(s)
         **/
        function procRecruitDeleteApplicant()
        {
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y') return new Object(-1, "msg_not_permitted");

            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return new Object(-1,'msg_invalid_request');

			$comment_srl = explode(',',$comment_srl);
            $oCommentController = &getController('comment');
            foreach($comment_srl as $srl)
            {
                $output = $oCommentController->deleteComment($srl);
				 if(!$output->toBool()) return $output;
				$obj->comment_srl = $srl;
				$deleteApplicantVars= executeQuery('recruit.deleteApplicantExtraVars', $obj);
            }

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('application_success_deleted');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispRecruitJobdetail', 'document_srl', $output->get('document_srl'));
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief insert applicant extra info 
         **/
        function _insertApplicantExtraVars($comment_srl)
        {
            $vars = Context::getRequestVars();
			$args->comment_srl = strval($comment_srl);
			$args->module_srl = $this->module_srl;
			$args->applicant_name = strval($vars->applicant_name);
            $args->birth =  date('YmdHis', strtotime($vars->birthday));
            $args->phone_no = $vars->phone_number;
			$diff = abs(time()-strtotime($vars->birthday));
			$args->age = intval($diff / (365*60*60*24));
			$logged_info = Context::get('logged_info');
            if(!$logged_info) $args->member_srl = 0;
            else $args->member_srl = $logged_info->member_srl;

            $output = executeQuery('recruit.insertApplicantExtraVars', $args);
			return $output;
        }

        /**
         * @brief update applicant extra info 
         **/
        function _updateApplicantExtraVars($comment_srl)
        {
			if(!$comment_srl) return new Object(-1, 'msg_invalid_request');
            $vars = Context::getRequestVars();
			$args->comment_srl = $comment_srl;
			$args->applicant_name = strval($vars->applicant_name);
            $args->birth =  date('YmdHis', strtotime($vars->birthday));
            $args->phone_no = $vars->phone_number;
			$diff = abs(time()-strtotime($vars->birthday));
			$args->age = intval($diff / (365*60*60*24));

            $output = executeQuery('recruit.updateApplicantExtraVars', $args);
			return $output;
        }

        /**
         * @brief check whether  the upload CV is in correct format or oversize
         **/
		function _checkFileValidation($module_srl){
			$file = $_FILES['fileName'];

			if($file['name']){
				if($file['error'] > 0) return false; 

				$oFileModel = &getModel('file');
				$file_module_config = $oFileModel->getFileModuleConfig($module_srl);
				// check file type
				$allowedType = explode(';', $file_module_config->allowed_filetypes);	
				$ext = $this->_getFileExt($file['name']);
				$ext = '*.'.$ext;

				if($allowedType && !in_array($ext, $allowedType) && !in_array('*.*',$allowedType)){
					return false;
				}
				// check file size
				$file_size = round($file['size']/1048576, 2);
				if($file_size>floatval($file_module_config->allowed_filesize)){
					return false;
				}
			}
			return true;
		}

        /**
         * @brief insert CV for an application
         **/
        function _insertFile($module_srl, $comment_srl)
        {
            $file = $_FILES['fileName'];
            $oFileModel = &getModel('file');

            $module_srl = $module_srl;
            $upload_target_srl = $comment_srl;
            if(is_uploaded_file($file['tmp_name']))
            {
                $oFileController = &getController('file');
				$oFileController->deleteFiles($upload_target_srl);
                $fileOutput = $oFileController->insertFile($file, $module_srl, $upload_target_srl);
            }

            //insert set file validate is Y
            if(!Context::get('comment_srl'))
            {
                $oFileController->setFilesValid($upload_target_srl);
            }
			return $fileOutput;
        }

        /**
         * @brief send a notification email
         **/
		function _sendNotifyEmail($comment_srl, $document_srl){
			if(!$comment_srl || !$document_srl) return null;

			$oRecruitModel = &getModel('recruit');
			$oDocumentModel = &getModel('document');
			
			$job_info = $oRecruitModel->getJobBySrl($document_srl);
			if(!$job_info)  return null;
			$target_email = $job_info->contact_email?$job_info->contact_email:$this->module_info->contact_mail;

			$applicant_info =  $oRecruitModel->getApplicationBySrl($comment_srl);
			$sender_email = $applicant_info->get('email_address');

			$subject = "[Recruitment Notification] ";
			$subject .= "Job: ".cut_str($job_info->title, 20)."        ";	
			$subject .= "Applicant: ".cut_str($applicant_info->applicant_name, 20);	

			$content = '';
			$content .= "New applicant notification email for recruitment\r\n\r\n";
			$content .= "Job Information\r\n";
			$content .= "-----------------------------------------------------\r\n";
			$content .= "Job Title : ".$job_info->title."\r\n";
			if($job_info->category_srl && $this->module_info->use_category == 'Y'){
				$category_info = $oDocumentModel->getCategory($job_info->category_srl);
				if($category_info)
					$content .= "Category : ".$category_info->title."\r\n";
			}
			if($job_info->location_srl && $this->module_info->use_location == 'Y'){
				$recruit_location = $oRecruitModel->getLocationBySrl($job_info->location_srl);
				$content .= "Location : ".$recruit_location->description."\r\n";
			}
			$content .= "Date : ".zdate($job_info->regdate, 'Y-m-d')." ~  ".zdate($job_info->close_date, 'Y-m-d')."\r\n";
			$content .= "\r\n\r\n";

			$content .= "Applicant Information\r\n";
			$content .= "-----------------------------------------------------\r\n";
			$content .= "Name : ".$applicant_info->applicant_name."\r\n";
			$content .= "Email : ".$applicant_info->email_address."\r\n";
			if(intval($applicant_info->age)>0) $content .= "Age : ".$applicant_info->age."\r\n";
			if($applicant_info->content)  $content .= "Self Introduction : ".removeHackTag($applicant_info->content)."\r\n\r\n";
			if($applicant_info->cv){
				$download_url = getSiteUrl().urldecode($applicant_info->cv->download_url);
				$download_url = str_replace('&amp;','&', $download_url);
				$content .= "CV Download : ".$download_url."\r\n";
			}

			$send_email = $this->_sendEmail($sender_email,$target_email, $subject, $content);
			$this->add('send_email',$send_email);
		}

		function _sendEmail($sender_email, $target_email, $subject, $content){
			$send_email = false;
			$oMail = new Mail();

			$oMail->setContentType("plain");

			$obj->send_email = $sender_email;
			if(!$oMail->isVaildMailAddress($obj->send_email)){
				return false;
			}
			$oMail->setSender($obj->send_email, $obj->send_email);

			$oMail->setTitle($subject);
			$oMail->setContent(htmlspecialchars($content));

			$target_mails = explode(',',$target_email);
				
			for($i=0;$i<count($target_mails);$i++) {
				$email_address = trim($target_mails[$i]);
				if(!$email_address) continue;
				if(!$oMail->isVaildMailAddress($email_address)) $send_email = false;
				$oMail->setReceiptor($email_address, $email_address);

				$oMail->send();
				$send_email = true;
			}

			return $send_email;
		}

        function _getFileExt($name){
            $pos = strpos($name,'?');
            if($pos){
            	$name=substr($name,0,$pos);
            }
            $ext = explode(".", $name);
            return (count($ext)==1) ? "" : array_pop($ext);
        }

    }