<?php

class StudentTransactionController extends RController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'rights', // perform access control for CRUD operations
		);
	}
	public function behaviors()
	    {
		return array(
		    'eexcelview'=>array(
		        'class'=>'ext.eexcelview.EExcelBehavior',
		    ),
		);
	    }
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view','final_view','new_view','test_view'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */


	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new StudentTransaction;
		$info =new StudentInfo;
		$user =new User;
		$photo =new StudentPhotos;
		$address=new StudentAddress;
		$lang=new LanguagesKnown;
		$auth_assign = new AuthAssignment;
		$student_fees_master = new StudentFeesMaster;

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation(array($info,$model,$user));

		if(!empty($_POST['StudentTransaction']) || !empty($_POST['StudentInfo']))
		{
			//print_r($_POST['StudentTransaction']); exit;
			$model->attributes=$_POST['StudentTransaction'];
			$info->attributes=$_POST['StudentInfo'];
			$user->attributes=$_POST['User'];
		
			$info->student_created_by = Yii::app()->user->id;
			$info->student_creation_date = new CDbExpression('NOW()');
			$info->student_email_id_1=strtolower($user->user_organization_email_id);
			$info->student_adm_date = date('Y-m-d',strtotime($_POST['StudentInfo']['student_adm_date']));			

			$user->user_organization_email_id = strtolower($info->student_email_id_1);
			$user->user_password =  md5($info->student_email_id_1.$info->student_email_id_1);
			$user->user_created_by =  Yii::app()->user->id;
			$user->user_creation_date = new CDbExpression('NOW()');
			$user->user_organization_id = Yii::app()->user->getState('org_id');
			$user->user_type = "student";
			
			if($info->save(false))  
			{  
				$user->save(false);
				$address->save(false);
				$lang->save(false); 						
				$photo->student_photos_path = "no-images";
				$photo->save();
			}
			if(empty($model->student_transaction_batch_id))
			$model->student_transaction_batch_id=0;	  
			$model->student_transaction_languages_known_id= $lang->languages_known_id;
			$model->student_transaction_student_id = $info->student_id;
			$model->student_transaction_user_id = $user->user_id;
			$model->student_transaction_student_address_id = $address->student_address_id;
			$model->student_transaction_student_photos_id = $photo->student_photos_id;
			$model->student_transaction_organization_id = Yii::app()->user->getState('org_id');
			$flag = Studentstatusmaster::model()->findByAttributes(array('status_name'=>'Regular'))->id;
			$model->student_transaction_detain_student_flag = $flag;
			$model->save();

//Fees Assignment to a student=========== By Ravi Bhalodiya=========================================================

			$fees_data = FeesMaster::model()->findByAttributes(array('fees_branch_id'=>$model->student_transaction_branch_id,'fees_academic_term_id'=>$model->student_academic_term_period_tran_id, 'fees_academic_term_name_id'=>$model->student_academic_term_name_id,'fees_quota_id'=>$model->student_transaction_quota_id));

			if($fees_data){
			   $fees_master = FeesMasterTransaction::model()->findAll(array('condition'=>'fees_master_id='.$fees_data->fees_master_id));

			   foreach($fees_master as $list){
			       $fees_detail = FeesDetailsTable::model()->findByPk($list['fees_desc_id']);
			       $student_fees_master->setIsNewRecord(true);	
		   	       $student_fees_master->student_fees_master_id = null;
			       $student_fees_master->student_fees_master_student_transaction_id = $model->student_transaction_id;
			       $student_fees_master->fees_master_table_id = $fees_data->fees_master_id;
			       $student_fees_master->student_fees_master_details_id = $fees_detail->fees_details_name;
			       $student_fees_master->fees_details_amount = $fees_detail->fees_details_amount;                 
			       $student_fees_master->student_fees_master_org_id  = Yii::app()->user->getState('org_id');               
			       $student_fees_master->student_fees_master_created_by = Yii::app()->user->id;
			       $student_fees_master->student_fees_master_creation_date = new CDbExpression('NOW()');
			       $student_fees_master->save();	
			   }
			} 
//==================================================================================================================

			StudentInfo::model()->updateByPk($model->student_transaction_student_id, array('student_info_transaction_id'=>$model->student_transaction_id));

			$auth_assign->itemname = 'Student';
			$auth_assign->userid = $user->user_id;
			$auth_assign->save();

			$this->redirect(array('update','id'=>$model->student_transaction_id));
		} //end of isset if
		else
		{
			$this->render('create',array(
			'model'=>$model,'info'=>$info,'user'=>$user
			));
		}
	}

	public function actionUpdateprofiletab1($id)
	{
	   $stud_trans = StudentTransaction::model()->findByPk($id);
	   if(isset($_POST['StudentTransaction'])){
		if(empty($_POST['StudentTransaction']['student_transaction_batch_id']))
			$_POST['StudentTransaction']['student_transaction_batch_id']=0;	  

	   StudentTransaction::model()->updateByPk($id, 
	array(
	'student_transaction_nationality_id'=>$_POST['StudentTransaction']['student_transaction_nationality_id'],
	'student_transaction_religion_id'=>$_POST['StudentTransaction']['student_transaction_religion_id'],
	'student_transaction_quota_id'=>$_POST['StudentTransaction']['student_transaction_quota_id'],
	'student_transaction_category_id'=>$_POST['StudentTransaction']['student_transaction_category_id'],
	'student_academic_term_period_tran_id'=>$_POST['StudentTransaction']['student_academic_term_period_tran_id'],
	'student_academic_term_name_id'=>$_POST['StudentTransaction']['student_academic_term_name_id'],
	'student_transaction_branch_id'=>$_POST['StudentTransaction']['student_transaction_branch_id'],
	'student_transaction_division_id'=>$_POST['StudentTransaction']['student_transaction_division_id'],
	'student_transaction_batch_id'=>$_POST['StudentTransaction']['student_transaction_batch_id'],
	'student_transaction_shift_id'=>$_POST['StudentTransaction']['student_transaction_shift_id']
	));

	$adm = date("Y-m-d", strtotime($_POST['StudentInfo']['student_adm_date']));

	$birthdate = NULL;
	if($_POST['StudentInfo']['student_dob'] != '')  
	   $birthdate = date("Y-m-d",strtotime($_POST['StudentInfo']['student_dob']));
	$gr = $_POST['StudentInfo']['student_gr_no']; 
	if($_POST['StudentInfo']['student_gr_no'] == null)
		   $gr = null;

	   StudentInfo::model()->updateByPk($stud_trans->student_transaction_student_id, 
	array(
		'student_roll_no'=>$_POST['StudentInfo']['student_roll_no'],
		'student_living_status'=>'HOME',
		'student_gr_no'=>$gr,
		'student_merit_no'=>$_POST['StudentInfo']['student_merit_no'],
		'student_adm_date'=>$adm,
		'student_enroll_no'=>$_POST['StudentInfo']['student_enroll_no'],
		'title'=>$_POST['StudentInfo']['title'],
		'student_dtod_regular_status'=>$_POST['StudentInfo']['student_dtod_regular_status'],
		'student_first_name'=>$_POST['StudentInfo']['student_first_name'],
		'student_middle_name'=>$_POST['StudentInfo']['student_middle_name'],
		'student_last_name'=>$_POST['StudentInfo']['student_last_name'],
		'student_mother_name'=>$_POST['StudentInfo']['student_mother_name'],
		'student_gender'=>$_POST['StudentInfo']['student_gender'],
		'student_mobile_no'=>$_POST['StudentInfo']['student_mobile_no'],
		'student_birthplace'=>$_POST['StudentInfo']['student_birthplace'],
		'student_dob'=>$birthdate,
		));
		}	
		
		$this->redirect(array('update','id'=>$id));
	}
	public function actionUpdateprofiletab2($id)
	{
		$stud_trans = StudentTransaction::model()->findByPk($id);
		 if(isset($_POST['StudentInfo'])){
		StudentInfo::model()->updateByPk($stud_trans->student_transaction_student_id, 
		array(
		'student_guardian_name'=>$_POST['StudentInfo']['student_guardian_name'],
		'student_guardian_relation'=>$_POST['StudentInfo']['student_guardian_relation'],
		'student_guardian_qualification'=>$_POST['StudentInfo']['student_guardian_qualification'],
		'student_guardian_occupation'=>$_POST['StudentInfo']['student_guardian_occupation'],
		'student_guardian_income'=>$_POST['StudentInfo']['student_guardian_income'],
		'student_guardian_occupation_address'=>$_POST['StudentInfo']['student_guardian_occupation_address'],
		'student_guardian_home_address'=>$_POST['StudentInfo']['student_guardian_home_address'],
		'student_guardian_occupation_city'=>$_POST['StudentInfo']['student_guardian_occupation_city'],
		'student_guardian_city_pin'=>$_POST['StudentInfo']['student_guardian_city_pin'],
		'student_guardian_phoneno'=>$_POST['StudentInfo']['student_guardian_phoneno'],
		'student_guardian_mobile'=>$_POST['StudentInfo']['student_guardian_mobile'],
		));
		}
		$this->redirect(array('update','id'=>$id));
	}	
	public function actionUpdateprofiletab3($id)
	{
		$stud_trans = StudentTransaction::model()->findByPk($id);
		if( isset($_POST['StudentInfo'])){
		StudentInfo::model()->updateByPk($stud_trans->student_transaction_student_id, 
		array(
		'student_email_id_2'=>strtolower($_POST['StudentInfo']['student_email_id_2']),
		'student_bloodgroup'=>$_POST['StudentInfo']['student_bloodgroup'],
		));
		LanguagesKnown::model()->updateByPk($stud_trans->student_transaction_languages_known_id, 
		array(
		'languages_known1'=>$_POST['LanguagesKnown']['languages_known1'],
		'languages_known2'=>$_POST['LanguagesKnown']['languages_known2'],
		'languages_known3'=>$_POST['LanguagesKnown']['languages_known3'],
		'languages_known4'=>$_POST['LanguagesKnown']['languages_known4'],
		));
		}
		$this->redirect(array('update','id'=>$id));
	}
	public function actionUpdateprofiletab4($id)
	{
		$stud_trans = StudentTransaction::model()->findByPk($id);
		 if(isset($_POST['StudentAddress']['student_address_c_line1']) ){

		if($_POST['StudentAddress']['stud_address_chkbox']==1){	
		   StudentAddress::model()->updateByPk($stud_trans->student_transaction_student_address_id, 
		array(
		'student_address_c_line1'=>$_POST['StudentAddress']['student_address_c_line1'],
		'student_address_c_line2'=>$_POST['StudentAddress']['student_address_c_line2'],
		'student_address_c_taluka'=>$_POST['StudentAddress']['student_address_c_taluka'],
		'student_address_c_district'=>$_POST['StudentAddress']['student_address_c_district'],
		'student_address_c_country'=>$_POST['StudentAddress']['student_address_c_country'],
		'student_address_c_state'=>$_POST['StudentAddress']['student_address_c_state'],
		'student_address_c_city'=>$_POST['StudentAddress']['student_address_c_city'],
		'student_address_c_pin'=>$_POST['StudentAddress']['student_address_c_pin'],
		'student_address_p_line1'=>$_POST['StudentAddress']['student_address_c_line1'],
		'student_address_p_line2'=>$_POST['StudentAddress']['student_address_c_line2'],
		'student_address_p_taluka'=>$_POST['StudentAddress']['student_address_c_taluka'],
		'student_address_p_district'=>$_POST['StudentAddress']['student_address_c_district'],
		'student_address_p_country'=>$_POST['StudentAddress']['student_address_c_country'],
		'student_address_p_state'=>$_POST['StudentAddress']['student_address_c_state'],
		'student_address_p_city'=>$_POST['StudentAddress']['student_address_c_city'],
		'student_address_p_pin'=>$_POST['StudentAddress']['student_address_c_pin'],
		'student_address_phone'=>$_POST['StudentAddress']['student_address_phone'],
		'student_address_mobile'=>$_POST['StudentAddress']['student_address_mobile'],
		));
		}
		else{
		    StudentAddress::model()->updateByPk($stud_trans->student_transaction_student_address_id, 
		array(
		'student_address_c_line1'=>$_POST['StudentAddress']['student_address_c_line1'],
		'student_address_c_line2'=>$_POST['StudentAddress']['student_address_c_line2'],
		'student_address_c_taluka'=>$_POST['StudentAddress']['student_address_c_taluka'],
		'student_address_c_district'=>$_POST['StudentAddress']['student_address_c_district'],
		'student_address_c_country'=>$_POST['StudentAddress']['student_address_c_country'],
		'student_address_c_city'=>$_POST['StudentAddress']['student_address_c_city'],
		'student_address_c_pin'=>$_POST['StudentAddress']['student_address_c_pin'],
		'student_address_c_state'=>$_POST['StudentAddress']['student_address_c_state'],
		'student_address_p_line1'=>$_POST['StudentAddress']['student_address_p_line1'],
		'student_address_p_line2'=>$_POST['StudentAddress']['student_address_p_line2'],
		'student_address_p_taluka'=>$_POST['StudentAddress']['student_address_p_taluka'],
		'student_address_p_district'=>$_POST['StudentAddress']['student_address_p_district'],
		'student_address_p_country'=>$_POST['StudentAddress']['student_address_p_country'],
		'student_address_p_state'=>$_POST['StudentAddress']['student_address_p_state'],
		'student_address_p_city'=>$_POST['StudentAddress']['student_address_p_city'],
		'student_address_p_pin'=>$_POST['StudentAddress']['student_address_p_pin'],
		'student_address_phone'=>$_POST['StudentAddress']['student_address_phone'],
		'student_address_mobile'=>$_POST['StudentAddress']['student_address_mobile'],
		));
		   }	
		}
		$this->redirect(array('update','id'=>$id));
	}
	public function actionUpdateprofilephoto($id)
	{
		$stud_trans = StudentTransaction::model()->findByPk($id);
		$info = StudentInfo::model()->findByAttributes(array('student_info_transaction_id'=>$id));
		$model=StudentPhotos::model()->findByPk($stud_trans->student_transaction_student_photos_id);
		$old_model=StudentPhotos::model()->findByPk($stud_trans->student_transaction_student_photos_id);

		
		 $this->performAjaxValidation($model);

		if(isset($_POST['StudentPhotos']))
		{	
			$old_photo =  $model->student_photos_path;
			$model->student_photos_path = CUploadedFile::getInstance($model,'student_photos_path');
			if($model->student_photos_path == null)
			{
				$valid_photo = true;
			}
			else
			{
				$valid_photo = $model->validate();
			}
			
			if($valid_photo)
			{
				if($model->student_photos_path!=null)
				{	
				
					$newfname = '';
					$ext= substr(strrchr($model->student_photos_path,'.'),1);
					
					
					//following thing done for deleting previously uploaded photo
					$photo = $old_photo;
					$dir1 = Yii::getPathOfAlias('webroot').'/stud_images/';
					
					if(file_exists($dir1.$photo) && $photo!='no-images' ){
					chmod($dir1.$photo, 0777);
					unlink($dir1.$photo);		
					}		
					if($ext!=null)
					{				
						$newfname = $info->student_enroll_no.'.'.$ext;
						$model->student_photos_path->saveAs(Yii::getPathOfAlias('webroot').'/stud_images/'.$model->student_photos_path = $newfname);
					}
					
					
					Yii::import("ext.EPhpThumb.EPhpThumb");
					$thumb=new EPhpThumb();
					$thumb->init(); //this is needed
					$thumb->create(Yii::getPathOfAlias('webroot').'/stud_images/'.$model->student_photos_path=$newfname)->resize(500,500)->save(Yii::getPathOfAlias('webroot').'/stud_images/'.$model->student_photos_path);
					$model->save(false);
				}
				$this->redirect(array('update','id'=>$id));
			}
			
		}

		$this->render('photo_form',array(
			'model'=>$model,
		));
	}

		
	

	/**`
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionimportationinstruction()
	{
		$model = new StudentTransaction;

		$this->render('importinstruction',array(
			'model'=>$model,
		));
		
	}
	public function actionUpdate($id)
	{
	   	
		$model=$this->loadModel($id);
		$info = StudentInfo::model()->findByPk($model->student_transaction_student_id);
		$address = StudentAddress::model()->findByPk($model->student_transaction_student_address_id);
		$photo = StudentPhotos::model()->findByPk($model->student_transaction_student_photos_id);
		$old_photo = StudentPhotos::model()->findByPk($model->student_transaction_student_photos_id);
		$lang = LanguagesKnown::model()->findByPk($model->student_transaction_languages_known_id);

		$studentdocstrans = new StudentDocsTrans;
		$stud_qua = new StudentAcademicRecordTrans;
		$stud_feed = new FeedbackDetailsTable;
		//$studentcertificate=new StudentCertificateDetailsTable;

		 $this->performAjaxValidation(array($info,$model,$photo,$address,$lang));		

		if(Yii::app()->user->getState('stud_id'))
		{
			$this->render('profile_form',array(
			'model'=>$model,'info'=>$info,'photo'=>$photo,'address'=>$address,'lang'=>$lang,'studentdocstrans'=>$studentdocstrans, 'stud_qua'=>$stud_qua,'stud_feed'=>$stud_feed,'flag'=>0,
		));	
		}
		else {	
		
		
		$this->render('update',array(
			'model'=>$model,'info'=>$info,'photo'=>$photo,'address'=>$address,'lang'=>$lang,'studentdocstrans'=>$studentdocstrans, 'stud_qua'=>$stud_qua,'stud_feed'=>$stud_feed,'flag'=>0,
		));
		}		
	}

	
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$model = $this->loadModel($id);
			$student_info = StudentInfo::model()->findByPk($model->student_transaction_student_id);
			if($model->student_transaction_student_address_id != null)
			$address = StudentAddress::model()->findByPk($model->student_transaction_student_address_id);
			$stud_photo = StudentPhotos::model()->findByPk($model->student_transaction_student_photos_id);
			if($model->student_transaction_languages_known_id != null)		
			$lang_known = LanguagesKnown::model()->findByPk($model->student_transaction_languages_known_id);
			
			
			$dir1 = Yii::getPathOfAlias('webroot').'/stud_images/';
			if($dh = opendir($dir1))
			{
				if($stud_photo->student_photos_path == "no-images")
				{

				}
				else
				{
					if(file_exists($dir1.$stud_photo->student_photos_path))
					{
						//chmod($dir1.$stud_photo->student_photos_path, 777);
						unlink($dir1.$stud_photo->student_photos_path);				
					}
				}
			}
			closedir($dh);
			if($this->loadModel($id)->delete()){
			$use_model = User::model()->findByPk($model->student_transaction_user_id)->delete();
			$stud_photo->delete();
			$student_info->delete();
			if($model->student_transaction_student_address_id != null)
			$address->delete();
			if($model->student_transaction_languages_known_id != null)
			$lang_known->delete();
			}
			
			//echo $model->student_transaction_student_id; exit;

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/** Delete Photo of update profile*/
	public function actionPhotoDelete($id)
	{
		$model = $this->loadModel($id);
		$stud_photo = StudentPhotos::model()->findByPk($model->student_transaction_student_photos_id);
		$dir1 = Yii::getPathOfAlias('webroot').'/stud_images/';
			if($dh = opendir($dir1))
			{
				if($stud_photo->student_photos_path == "no-images")
				{

				}
				else
				{
					if(file_exists($dir1.$stud_photo->student_photos_path))
					{
						//chmod($dir1.$stud_photo->student_photos_path, 777);
						unlink($dir1.$stud_photo->student_photos_path);	
						//$stud_photo->delete();
						$stud_photo->student_photos_path = "no-images";
						$stud_photo->save();			
					}
					else
					{
						$stud_photo->student_photos_path = "no-images";
						$stud_photo->save();			

					}
				}
			}
			closedir($dh);	
		$this->redirect(array('studentTransaction/update','id'=>$model->student_transaction_id));
		
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		/*$dataProvider=new CActiveDataProvider('StudentTransaction');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));*/

		$model=new StudentTransaction('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['StudentTransaction']))
			$model->attributes=$_GET['StudentTransaction'];

		$this->render('admin',array(
			'model'=>$model,
		));


	}

	/**
	 * Manages all models.
	 */
	


	public function actionAdmin()
	{
		$model=new StudentTransaction('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['StudentTransaction']))
			$model->attributes=$_GET['StudentTransaction'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}


	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=StudentTransaction::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($models)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='student-transaction-form')
		{
			echo CActiveForm::validate($models);
			Yii::app()->end();
		}
	}
	
	
}
