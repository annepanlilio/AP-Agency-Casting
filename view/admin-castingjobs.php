<?php


$siteurl = get_option('siteurl');
	// Casting Class
	include (dirname(__FILE__) ."/../app/casting.class.php");
	include(RBAGENCY_PLUGIN_DIR ."ext/easytext.php");

	global $wpdb;

	// Get Options
	$rb_agency_options_arr = get_option('rb_agency_options');
		$rb_agency_option_agencyname	= $rb_agency_options_arr['rb_agency_option_agencyname'];
		$rb_agency_option_agencyemail	= $rb_agency_options_arr['rb_agency_option_agencyemail'];
		$rb_agency_option_agencyheader	= $rb_agency_options_arr['rb_agency_option_agencyheader'];

	// Declare Hash
	$SearchMuxHash	=  isset($_GET["SearchMuxHash"])?$_GET["SearchMuxHash"]:""; // Set Hash
	$hash =  "";

	wp_register_script('jquery_latest', plugins_url('../js/jquery-1.11.0.min.js', __FILE__),false,1,true);
	wp_enqueue_script('jquery_latest');
	wp_enqueue_script( 'jqueryui',  plugins_url('../js/jquery-ui.js', __FILE__),false,1,true);
	wp_register_script('jquery-timepicker',  plugins_url('../js/jquery-timepicker.js', __FILE__),false,1,true);
	wp_enqueue_script('jquery-timepicker');
	wp_register_style( 'timepicker-style', plugins_url('../css/timepicker-addon.css', __FILE__) );
	wp_enqueue_style( 'timepicker-style' );

	echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';


	//xyr code
	//altering the main table for Job_Audition_Time_End fields... - due someone added this field .. and i dont who is it.
	$queryAlterCheck = "SELECT Job_Audition_Time_End FROM " . table_agency_casting_job ." LIMIT 1";
	$resultsDataAlter = $wpdb->get_results($queryAlterCheck,ARRAY_A);
	$count_alter = $wpdb->num_rows;
	if($count_alter == 0 or !($resultsDataAlter)){
		$queryAlter = "ALTER TABLE " . table_agency_casting_job ." ADD Job_Audition_Time_End VARCHAR(10) NOT NULL";
		//$queryAlter = "ALTER TABLE " . table_agency_casting_job ." CHANGE Job_Audition_Time_End VARCHAR(10) NOT NULL";
		$resultsDataAlter = $wpdb->get_results($queryAlter,ARRAY_A);
	}

	//altering the main table for Job_Time_End fields... - due someone added this field .. and i dont who is it.
	$queryAlterCheck = "SELECT Job_Time_End FROM " . table_agency_casting_job ." LIMIT 1";
	$resultsDataAlter = $wpdb->get_results($queryAlterCheck,ARRAY_A);
	$count_alter = $wpdb->num_rows;
	if($count_alter == 0 or !($resultsDataAlter)){
		$queryAlter = "ALTER TABLE " . table_agency_casting_job ." ADD Job_Time_End VARCHAR(10) NOT NULL";
		$resultsDataAlter = $wpdb->get_results($queryAlter,ARRAY_A);
		$queryAlter = "ALTER TABLE " . table_agency_casting_job ." ADD Job_Time_Start VARCHAR(10) NOT NULL";
		//$queryAlter = "ALTER TABLE " . table_agency_casting_job ." CHANGE Job_Audition_Time_End VARCHAR(10) NOT NULL";
		$resultsDataAlter = $wpdb->get_results($queryAlter,ARRAY_A);
	}




	/*
	 * Display Inform Talent
	 */
	if( (isset($_SESSION['cartArray']) || isset($_GET["action"]) && $_GET["action"] == "informTalent" || !isset($_GET["action"])) && $_GET['action'] !=='viewAllAuditions' ){
	?>


	<div style="clear:both"></div>

		<div class="wrap">
		<div id="rb-overview-icon" class="icon32"></div>
		<h2>Casting Jobs</h2>

		<?php
		// Delete selected profiles
		if(isset($_POST["action2"]) && $_POST["action2"] == "deleteprofile"){
			$arr_selected_profile = array();
			$data = current($wpdb->get_results($wpdb->prepare("SELECT * FROM ".table_agency_casting_job." WHERE Job_ID= %d ", $_GET["Job_ID"])));
			$arr_profiles = explode(",",$data->Job_Talents);

			foreach($_POST as $key => $val ){
				if(strpos($key, "profiletalent") !== false){
					$wpdb->query($wpdb->prepare("DELETE FROM ".table_agency_castingcart_profile_hash." WHERE CastingProfileHashProfileID = %s",$val));
					$wpdb->query($wpdb->prepare("DELETE FROM  " . table_agency_casting_job_application . " WHERE Job_ID = ".$_GET["Job_ID"]." AND Job_UserLinked = %s",$val));
					array_push($arr_selected_profile, $val);
				}
			}

			$new_set_profiles = implode(",",array_diff($arr_profiles,$arr_selected_profile));
			$wpdb->query($wpdb->prepare("UPDATE ".table_agency_casting_job." SET Job_Talents=%s WHERE Job_ID = %d", $new_set_profiles, $_GET["Job_ID"]));
			echo ('<div id="message" class="updated"><p>'.count($arr_selected_profile).(count($arr_selected_profile) <=1?" profile":" profiles").' removed successfully!</p></div>');
		}
		// Delete selected profiles
		if(isset($_POST["action2"]) && $_POST["action2"] == "deletecastingprofile"){
			$arr_selected_profile = array();
			$data = current($wpdb->get_results($wpdb->prepare("SELECT * FROM ".table_agency_casting_job." WHERE Job_ID= %d ", $_GET["Job_ID"])));
			$arr_profiles = explode(",",$data->Job_Talents);

			foreach($_POST as $key => $val ){
				if(strpos($key, "profiletalent") !== false){
					$wpdb->query($wpdb->prepare("DELETE FROM ".table_agency_castingcart." WHERE CastingCartTalentID = %s",$val));

					array_push($arr_selected_profile, $val);
					$profile_user_linked = $wpdb->get_row("SELECT ProfileUserLinked FROM ".table_agency_profile." WHERE ProfileID = '".$val."' ");
					$wpdb->query("DELETE FROM ".table_agency_casting_job_application." WHERE Job_ID = '".$_GET["Job_ID"]."' AND Job_UserProfileID = '".$val."'");

				}
			}


			echo ('<div id="message" class="updated"><p>'.count($arr_selected_profile).(count($arr_selected_profile) <=1?" profile":" profiles").' removed successfully!</p></div>');
		}
		// Remove to Profile Casting
		if(isset($_POST["addprofilestocasting"])){

			$profiles = explode(",",$_POST["addprofilestocasting"]);
			$job_id = 0;
			$agent_id = 0;
			//print_r($profiles);
			if(isset($_GET["Job_ID"]) && !isset($_POST["addtoexisting"])){
				$existing_profiles = $wpdb->get_results("SELECT CastingCartTalentID FROM ".table_agency_castingcart." WHERE CastingJobID = '".$_GET["Job_ID"]."'",ARRAY_A);
				$job_id  = $_GET["Job_ID"];
				$agent_id = $_POST["Agent_ID"];
			} elseif(isset($_POST["addtoexisting"])){
				list($job_id,$agent_id) = explode("-",$_POST["Job_ID"]);
				$existing_profiles = $wpdb->get_results("SELECT CastingCartTalentID FROM ".table_agency_castingcart." WHERE CastingJobID = '".$job_id."'",ARRAY_A);
			}
			$arr_profiles = array();
			foreach ($existing_profiles as $key) {
				array_push($arr_profiles, $key["CastingCartTalentID"]);
			}

			$sql = array();
			/**foreach ($profiles as $key) {
				if(!in_array($key,$arr_profiles)){
					$sql[] ="($job_id, '".$agent_id."','".$key."')";
				}
				/*$wpdb->get_results("SELECT * FROM ".table_agency_casting_job_application." WHERE Job_ID='".$job_id."' AND Job_UserLinked = '".$key."'");
				$is_applied = $wpdb->num_rows;
				if($is_applied <= 0){
					$get_profile_user_linked = $wpdb->get_row("SELECT ProfileUserLinked FROM ".table_agency_profile." WHERE ProfileID ='".$key."' ");

					$wpdb->query("INSERT INTO  " . table_agency_casting_job_application . " (Job_ID, Job_UserLinked) VALUES('".$job_id."','".$get_profile_user_linked->ProfileUserLinked."') ");
				}*/

			/**}**/
			$handler_arr = array();
			foreach($profiles as $k=>$v){
				if(in_array($v,$handler_arr)){
					$sql[] = "($job_id, '".$agent_id."','".$v."')";
				}
				$handler_arr[] = $v;
			}
			//print_r($sql);
			$implodedSQL = implode(',',$sql);
			if(!empty($sql)){

				$sql = "SELECT CastingJobID FROM ".$wpdb->prefix."agency_castingcart LIMIT 1";
				$r = $wpdb->get_results($sql);
				if(count($r) == 0){
					//create column
					$queryAlter = "ALTER TABLE " . $wpdb->prefix ."agency_castingcart ADD CastingJobID INT(10) default 0 AFTER CastingCartID";
					$resultsDataAlter = $wpdb->query($queryAlter,ARRAY_A);
				}
			//$wpdb->query("INSERT INTO " . table_agency_casting_job_application . "  (Job_ID, Job_UserLinked) VALUES  (".$job_id.",". $current_user->ID .")");
				$final_sql = "INSERT INTO ".table_agency_castingcart."(CastingJobID,CastingCartProfileID, CastingCartTalentID) VALUES".$implodedSQL;
				//echo $final_sql;
				$added = $wpdb->query($final_sql);
				//echo  $wpdb->last_error;
				if($added){
					echo ('<div id="message" class="updated"><p>'.count($sql).(count($sql) <=1?" profile":" profiles").' successfully added to casting cart!</p></div>');
				}

			}

		}
		// Add selected profiles
		if(isset($_POST["addprofiles"])){

			if(isset($_GET["action2"]) && $_GET["action2"] == "addnew"){
				$profiles = $_POST["addprofiles"];

				if(strpos($profiles,",") !== false){
						$profiles = explode(",",$profiles);
						foreach ($profiles as $key) {
							array_push($_SESSION["cartArray"],$key);
						}
				} else {
					array_push($_SESSION["cartArray"],$profiles);
				}
			} else {
										$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".table_agency_casting_job." WHERE Job_ID= %d ", $_GET["Job_ID"]));
										$add_new_profiles = $data->Job_Talents.",".$_POST["addprofiles"];
										$castingHash = $wpdb->get_row("SELECT * FROM ".table_agency_casting_job." WHERE Job_ID='".$_GET["Job_ID"]."'");
										$profiles = $_POST["addprofiles"];

										if(strpos($profiles,",") !== false){
											$profiles = explode(",",$profiles);
											//print_r($profiles);
											foreach($profiles as $profileid){
													$hash_profile_id = RBAgency_Common::generate_random_string(20,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
												$sql = "INSERT INTO ".table_agency_castingcart_profile_hash."
												(
													CastingProfileHashID,
													CastingProfileHashJobID,
													CastingProfileHashProfileID,
													CastingProfileHash
												)  VALUES(
													'',
													'".$castingHash->Job_Talents_Hash."',
													'".$profileid."',
													'".$hash_profile_id."'
												)";
												$wpdb->query($sql);

												$results = $wpdb->get_row("SELECT ProfileContactPhoneCell,ProfileContactEmail, ProfileID FROM ".table_agency_profile." WHERE ProfileID IN(".(!empty($profileid)?$profileid:"''").")",ARRAY_A);

												//disabled admin send email
												if(!empty( $results )){
													//RBAgency_Casting::sendText(array($results["ProfileContactPhoneCell"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$hash_profile_id);
													//RBAgency_Casting::sendEmail(array($results["ProfileContactEmail"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$hash_profile_id);
												}

											}
										} else {
												$hash_profile_id = RBAgency_Common::generate_random_string(20,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
												$profileid = str_replace(",","",(isset($_POST["addprofiles"])?$_POST["addprofiles"]:""));
											$sql = "INSERT INTO ".table_agency_castingcart_profile_hash."
												(
													CastingProfileHashID,
													CastingProfileHashJobID,
													CastingProfileHashProfileID,
													CastingProfileHash
												)
												VALUES
												(
												'',
												'".$castingHash->Job_Talents_Hash."',
												'".$profileid."',
												'".$hash_profile_id."'
												)";
												$wpdb->query($sql);
												$results = $wpdb->get_row("SELECT ProfileContactPhoneCell,ProfileContactEmail, ProfileID FROM ".table_agency_profile." WHERE ProfileID IN(".(!empty($profileid)?$profileid:"''").")",ARRAY_A);
												//disabled admin send email
												if(!empty( $results )){
													//RBAgency_Casting::sendText(array($results["ProfileContactPhoneCell"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$hash_profile_id);
													//RBAgency_Casting::sendEmail(array($results["ProfileContactEmail"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$hash_profile_id);
												}
										}

										$wpdb->query($wpdb->prepare("UPDATE ".table_agency_casting_job." SET Job_Talents=%s WHERE Job_ID = %d", implode(",",array_unique(explode(",",$add_new_profiles))), $_GET["Job_ID"]));
										echo ('<div id="message" class="updated"><p>Added successfully!</p></div>');
				}
		}
		// Insert Profiles to Casting Job
		if(isset($_POST["action2"]) && $_POST["action2"] =="add"){
				if (!isset($_GET["Job_ID"])) {
					
					#check for job into
					$sql = "SELECT Job_Intro FROM ".table_agency_casting_job;
					$results = $wpdb->get_results($sql,ARRAY_A);
					if(count($results)==0){
						$queryAlter = "ALTER TABLE " .table_agency_casting_job." ADD Job_Intro text default NULL";
						$resultsDataAlter = $wpdb->query($queryAlter,ARRAY_A);
					}
			
					$cartArray = isset($_SESSION['cartArray'])?$_SESSION['cartArray']:array();
					$cartString = implode(",", array_unique($cartArray));
					$cartString = RBAgency_Common::clean_string($cartString);
					$hash = RBAgency_Common::generate_random_string(10,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
					$sql = "INSERT INTO ".table_agency_casting_job." (
							Job_Title,
							Job_Intro,
							Job_Text,
							Job_Date_Start,
							Job_Date_End,
							Job_Time_Start,
							Job_Time_End,
							Job_Location,
							Job_Region,
							Job_Offering,
							Job_Talents,
							Job_Visibility,
							Job_Criteria,
							Job_Type,
							Job_Talents_Hash,
							Job_Audition_Date_Start,
							Job_Audition_Date_End,
							Job_Audition_Venue,
							Job_Audition_Time,
							Job_Audition_Time_End,
							Job_UserLinked,
							Job_Date_Created
						)
						VALUES(
							'".esc_attr($_POST["Job_Title"])."',
							'".esc_attr($_POST["Job_Intro"])."',
							'".esc_attr($_POST["Job_Text"])."',
							'".esc_attr($_POST["Job_Date_Start"])."',
							'".esc_attr($_POST["Job_Date_End"])."',
							'".esc_attr($_POST["Job_Time_Start"])."',
							'".esc_attr($_POST["Job_Time_End"])."',
							'".esc_attr($_POST["Job_Location"])."',
							'".esc_attr($_POST["Job_Region"])."',
							'".esc_attr($_POST["Job_Offering"])."',
							'".$cartString."',
							'".esc_attr($_POST["Job_Visibility"])."',
							'".esc_attr($_POST["Job_Criteria"])."',
							'".esc_attr($_POST["Job_Type"])."',
							'".$hash."',
							'".esc_attr($_POST["Job_Audition_Date_Start"])."',
							'".esc_attr($_POST["Job_Audition_Date_End"])."',
							'".esc_attr($_POST["Job_Audition_Venue"])."',
							'".esc_attr($_POST["Job_Audition_Time"])."',
							'".esc_attr($_POST["Job_Audition_Time_End"])."',
							'".esc_attr($_POST["Job_AgencyName"])."',
							NOW()
						)
					";



					$wpdb->query($sql);
					$Job_ID = $wpdb->insert_id;

					$insert_to_casting_custom = array();

					$profilecustom_ids = array();
					$profilecustom_types = array();
					foreach($_POST as $k=>$v){
						$parsek = explode("_",$k);
						if($parsek[0] == 'ProfileCustom2'){
							$profilecustom_ids[] = $parsek[1];
							$profilecustom_types[] = $parsek[2];
						}
					}

					foreach($profilecustom_ids as $k=>$v){
						foreach($_POST["ProfileCustom2_".$v."_".$profilecustom_types[$k]] as $key=>$value){
							if($profilecustom_types[$k] == 9 || $profilecustom_types[$k] == 5){
								$data = implode("|",$_POST["ProfileCustom2_".$v."_".$profilecustom_types[$k]]);

							}else{
								$data = $_POST["ProfileCustom2_".$v."_".$profilecustom_types[$k]][$key];
							}
							if(empty($data) || $data == '--Select--'){
								$data = NULL;
							}
							//print_r($_POST);
							$insert_to_casting_custom[] = "INSERT INTO ".$wpdb->prefix."agency_casting_job_customfields(Job_ID,Customfield_ID,Customfield_value,Customfield_type) values('".esc_attr($Job_ID)."','".esc_attr($v)."','".esc_attr($data)."','".esc_attr($profilecustom_types[$k])."')";
						}

					}
					$wpdb->query("ALTER TABLE ". $wpdb->prefix."agency_casting_job_customfields CHANGE Customfield_value Customfield_value VARCHAR(100)");
					$temp_arr = array();
					foreach($insert_to_casting_custom as $k=>$v){
						if(!in_array($v,$temp_arr)){
							$wpdb->query($v);
							$temp_arr[$k] = $v;
						}
					}

					$results = $wpdb->get_results("SELECT ProfileContactPhoneCell,ProfileContactEmail, ProfileID FROM ".table_agency_profile." WHERE ProfileID IN(".(!empty($cartString)?$cartString:"''").")",ARRAY_A);
					foreach($results as $mobile){
						$hash_profile_id = RBAgency_Common::generate_random_string(20,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
						$sql = "INSERT INTO ".table_agency_castingcart_profile_hash."
						(
							CastingProfileHashID,
							CastingProfileHashJobID,
							CastingProfileHashProfileID,
							CastingProfileHash
						)
						VALUES(
							'',
							'".$hash."',
							'".$mobile["ProfileID"]."',
							'".$hash_profile_id."'
						)";
						$wpdb->query($sql);

						RBAgency_Casting::sendText(array($mobile["ProfileContactPhoneCell"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$hash."/".$hash_profile_id);
						RBAgency_Casting::sendEmail(array($mobile["ProfileContactEmail"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$hash."/".$hash_profile_id);

					}

					unset($_SESSION['cartArray']);
					echo ('<div id="message" class="updated"><p>Added successfully! <a href="'.admin_url("admin.php?page=rb_agency_castingjobs").'">View jobs</a></p></div>');
				} else {
					echo "No profiles selected in Casting cart.";
				}
		} elseif(isset($_POST["action2"]) && $_POST["action2"] =="edit"){


		$rs= $wpdb->get_results("SELECT * FROM ".table_agency_casting_job);
		$job_intro = '';
		foreach($rs as $r){
			$job_intro = $r->Job_Intro;
		}

		if(!isset($job_intro)){
			$wpdb->query("ALTER TABLE ". $wpdb->prefix."agency_casting_job ADD Job_Intro VARCHAR(100)");
		}
		$wpdb->query("ALTER TABLE ". $wpdb->prefix."agency_casting_job_customfields CHANGE Customfield_value Customfield_value VARCHAR(100)");

							$sql = "UPDATE ".table_agency_casting_job."
								SET
										Job_Title = '".esc_attr($_POST["Job_Title"])."',
										Job_Intro = '".esc_attr($_POST["Job_Intro"])."',
										Job_Text = '".esc_attr($_POST["Job_Text"])."',
										Job_Date_Start = '".esc_attr($_POST["Job_Date_Start"])."',
										Job_Date_End = '".esc_attr($_POST["Job_Date_End"])."',
										Job_Time_Start = '".esc_attr($_POST["Job_Time_Start"])."',
										Job_Time_End = '".esc_attr($_POST["Job_Time_End"])."',
										Job_Location = '".esc_attr($_POST["Job_Location"])."',
										Job_Region = '".esc_attr($_POST["Job_Region"])."',
										Job_Offering = '".esc_attr($_POST["Job_Offering"])."',
										Job_Talents = '".esc_attr($_POST["Job_Talents"])."',
										Job_Visibility = ".$_POST["Job_Visibility"].",
										Job_Criteria = '".esc_attr($_POST["Job_Criteria"])."',
										Job_Type = '".esc_attr($_POST["Job_Type"])."',
										Job_Talents_Hash = '".esc_attr($_POST["Job_Talents_Hash"])."',
										Job_Audition_Date_Start = '".esc_attr($_POST["Job_Audition_Date_Start"])."',
										Job_Audition_Date_End = '".esc_attr($_POST["Job_Audition_Date_End"])."',
										Job_Audition_Venue = '".esc_attr($_POST["Job_Audition_Venue"])."',
										Job_Audition_Time = '".esc_attr($_POST["Job_Audition_Time"])."',
										Job_Audition_Time_End = '".esc_attr($_POST["Job_Audition_Time_End"])."'
									WHERE Job_ID = ".esc_attr($_GET["Job_ID"])."
							";

							$wpdb->query($sql);

							//print_r($_POST);
							/**UPDATE CUSTOM FIELDS**/
                            $wpdb->delete($wpdb->prefix."agency_casting_job_customfields",array('Job_ID'=>$_GET["Job_ID"]));
							foreach($_POST as $k=>$v){
							 
								$parseCustom = explode("_",$k);
								if($parseCustom[0] == 'ProfileCustom2'){
				    
                                    $data = 'NULL';
                                            if(isset($_POST[$k]) && !empty($_POST[$k])){
											   if($parseCustom[2] == 9 || $parseCustom[2] == 5){
													$data = implode("|",$_POST[$k]);
												}else{
													$data = $v[0];
												}
											}
                                    
									
										                                        
                                        $insert_to_casting_custom = "INSERT INTO ".$wpdb->prefix."agency_casting_job_customfields(Job_ID,Customfield_ID,Customfield_value,Customfield_type) values('".esc_attr($_GET["Job_ID"])."','".esc_attr($parseCustom[1])."','".esc_attr($data)."','".esc_attr($parseCustom[2])."')";
									    $wpdb->query($insert_to_casting_custom);
                                   
                                    
                                        
                                   
								}
                                
                            }

							/**END UPDATE CUSTOM FIELDS**/
							if(isset($_POST["resend"]) and !empty($_POST["Job_Talents_Resend_To"])){
								$pIDS = array();
								$profileIDSHandler = array();
								$explodedIDS = explode(',',$_POST["Job_Talents_Resend_To"]);
								//print_r($explodedIDS);
								foreach($explodedIDS as $k=>$v){
									if(in_array($v,$profileIDSHandler)){
										$pIDS[] = $v;
									}
									$profileIDSHandler[] = $v;
								}
								$idsImploded = '('.implode(',',$pIDS).')';

								$results = $wpdb->get_results("SELECT ProfileID,ProfileContactPhoneCell,ProfileContactEmail FROM ".table_agency_profile." WHERE ProfileID IN $idsImploded",ARRAY_A);
								$arr_mobile_numbers = array();
								$arr_email = array();
								$castingHash = $wpdb->get_row("SELECT * FROM ".table_agency_casting_job." WHERE Job_ID='".$_GET["Job_ID"]."'");
								foreach($results as $mobile){
									array_push($arr_mobile_numbers, $mobile["ProfileContactPhoneCell"]);
									array_push($arr_email, $mobile["ProfileContactEmail"]);
									$results_hash = $wpdb->get_row($wpdb->prepare("SELECT * FROM  ".table_agency_castingcart_profile_hash." WHERE  CastingProfileHashProfileID = %s",$mobile["ProfileID"]));
									RBAgency_Casting::sendText(array($mobile["ProfileContactPhoneCell"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$results_hash->CastingProfileHash);
									RBAgency_Casting::sendEmail(array($mobile["ProfileContactEmail"]),get_bloginfo("wpurl")."/profile-casting/jobs/".$castingHash->Job_Talents_Hash."/".$results_hash->CastingProfileHash);
								}
							}
							unset($_SESSION['cartArray']);
							echo ('<div id="message" class="updated"><p>Updated successfully!</p></div>');

		} elseif(isset($_GET["action2"]) && $_GET["action2"] == "deleteCastingJob"){
				$wpdb->query("DELETE FROM ".table_agency_casting_job." WHERE Job_ID = '".$_GET["removeJob_ID"]."'");
				echo ('<div id="message" class="updated"><p>Deleted successfully!</p></div>');

				RBAgency_Casting::rb_display_casting_jobs();
		}

				$Job_ID = "";
				$Job_Title = "";
				$Job_Intro = "";
				$Job_Text = "";
				$Job_Date_Start = "";
				$Job_Date_End = "";
				$Job_Time_Start = "";
				$Job_Time_End = "";
				$Job_Location = "";
				$Job_Region = "";
				$Job_Offering = "";
				$Job_Talents = "";
				$Job_Visibility = "";
				$Job_Criteria = "";
				$Job_Type = "";
				$Job_Talents_Hash = "";
				$Job_Audition_Date_Start = "";
				$Job_Audition_Date_End = "";
				$Job_Audition_Venue = "";
				$Job_Audition_Time = "";
				$Job_Audition_Time_End = "";
				$CastingContactEmail = "";

		if(isset($_GET["Job_ID"])){

				$sql =  "SELECT job.*, agency.* FROM ".table_agency_casting_job." as job INNER JOIN ".table_agency_casting." as agency ON job.Job_UserLinked = agency.CastingUserLinked WHERE Job_ID= %d ";
				$data = $wpdb->get_results($wpdb->prepare($sql, $_GET["Job_ID"]));
				//print_r($data);
				$data = current($data);
				$Job_ID = $data->Job_ID;
				$Job_AgencyName = $data->CastingContactCompany;
				$Job_Agency_ID = $data->Job_UserLinked;
				$Job_Title = $data->Job_Title;
				$Job_Intro = $data->Job_Intro;
				$Job_Text = $data->Job_Text;
				$Job_Date_Start = $data->Job_Date_Start;
				$Job_Date_End = $data->Job_Date_End;
				$Job_Time_Start = $data->Job_Time_Start;
				$Job_Time_End = $data->Job_Time_End;
				$Job_Location = $data->Job_Location;
				$Job_Region = $data->Job_Region;
				$Job_Offering = $data->Job_Offering;
				$Job_Talents = implode(",",array_filter(explode(",",$data->Job_Talents)));
				$Job_Visibility = $data->Job_Visibility;
				$Job_Criteria = $data->Job_Criteria;
				$Job_Type = $data->Job_Type;
				$Job_Talents_Hash = $data->Job_Talents_Hash;
				$Job_Audition_Date_Start = $data->Job_Audition_Date_Start;
				$Job_Audition_Date_End = $data->Job_Audition_Date_End;
				$Job_Audition_Venue = $data->Job_Audition_Venue;
				$Job_Audition_Time = $data->Job_Audition_Time;
				$Job_Audition_Time_End = $data->Job_Audition_Time_End;
				$CastingContactEmail = $data->CastingContactEmail;
				$CastingContactDisplay = $data->CastingContactDisplay;

				$sql_customfield = "SELECT * FROM ".$wpdb->prefix."agency_casting_job_customfields WHERE Job_ID = %d";
				$data_customfield = $wpdb->get_results($wpdb->prepare($sql_customfield,$_GET['Job_ID']));
			}

	// Notify Client
	if(isset($_POST["notifyclient"])){
		$bcc_emails = isset($_POST["bcc_emails"])?$_POST["bcc_emails"]:"";

		$notified = RBAgency_Casting::sendClientNotification($CastingContactEmail,$_POST["message"],$bcc_emails);
		echo ('<div id="message" class="updated"><p>Notification successfully sent!</p></div>');
	}

	if(isset($_GET["action2"]) && $_GET["action2"] == "addnew" || isset($_GET["Job_ID"])){

				echo "<div id=\"casting-jobs\" class=\"boxblock-container\" style=\"width:50%\">";
				echo "<div class=\"boxblock\">";

						if(isset($_GET["Job_ID"])){
							echo "<h3>".__("Edit Talent Jobs:",RBAGENCY_casting_TEXTDOMAIN)."</h3>";
						} else {
							echo "<h3>".__("Talent Jobs:",RBAGENCY_casting_TEXTDOMAIN)."</h3>";
						}
				echo "<div class=\"innerr s\" style=\"padding: 10px;\">";
/*				if(!isset($_GET["Job_ID"]) && (empty( $_SESSION['cartArray'] ) || !isset($_GET["action"]) )){
					echo "Casting cart is empty. Click <a href=\"?page=rb_agency_search\">here</a> to search and add profiles to casting jobs.";
				} else {*/
				echo "<form class=\"castingtext\" method=\"post\" action=\"\">";
				echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_AgencyName\">".__("Agency/Producer",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div>";
						if(isset($_GET["action2"]) && $_GET["action2"] == "addnew"){
							echo "<select name=\"Job_AgencyName\" style=\"width:186px;\">";
							echo "<option value=\"\">-- ".__("Please Select",RBAGENCY_casting_TEXTDOMAIN)." --</option>";
							$castings = $wpdb->get_results("SELECT * FROM ".table_agency_casting." WHERE CastingIsActive = 1 ORDER BY CastingContactCompany DESC");
							foreach ($castings as $key) {
								echo "<option value=\"".$key->CastingUserLinked."\">".$key->CastingContactDisplay." - ".$key->CastingContactCompany."</option>";
							}
							echo "<select>";
						} else {
							echo "<input type=\"text\" disabled=\"disabled\" id=\"Job_AgencyName\" name=\"Job_AgencyName\" value=\"".$Job_AgencyName."\">";
						}
						echo "</div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Title\">".__("Job Title",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" id=\"Job_Title\" name=\"Job_Title\" value=\"".$Job_Title."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Intro\">".__("Job Intro",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" id=\"Job_Intro\" name=\"Job_Intro\" value=\"".$Job_Intro."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Text\">".__("Description",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><textarea id=\"Job_Text\" name=\"Job_Text\">".$Job_Text."</textarea></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Offering\">".__("Payment",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" id=\"Job_Offering\" name=\"Job_Offering\" value=\"".$Job_Offering."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Date_Start\">".__("Shoot Date Start",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" class=\"datepicker\" id=\"Job_Date_Start\" name=\"Job_Date_Start\" value=\"".$Job_Date_Start."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Date_End\">".__("Shoot Date End",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" class=\"datepicker\" id=\"Job_Date_End\" name=\"Job_Date_End\" value=\"".$Job_Date_End."\"></div>";
					echo "</div>";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Time_Start\">Job Time Start</label>";
						echo "<div>
							<select id=\"Job_Time_Start\" name=\"Job_Time_Start\">\n
							<option value=\"--\">--</option>";
						for($i = 0; $i < 24; $i++) {

							$ampm = $i >= 12 ? 'pm' : 'am';
							$starttime00 = $i % 12 ? $i % 12 .':00' : 12 .':00';
							$starttime30 = $i % 12 ? $i % 12 .':30' : 12 .':30';
							$sselected00 = $Job_Time_Start == $starttime00 . $ampm ? "selected" : "";
							$sselected30 = $Job_Time_Start == $starttime30 . $ampm ? "selected" : "";
						?>
							<option value="<?php echo  $starttime00 . $ampm ?>" <?php echo $sselected00; ?>><?php echo  $starttime00 . $ampm ?></option><?php

						?>

						<option value="<?php echo  $starttime30 . $ampm ?>" <?php echo $sselected30; ?>><?php echo  $starttime30 . $ampm ?></option><?php
						}
						echo "  </select>\n</div>";
					echo "</div>";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Time_End\">".__("Job Time End",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div>
							<select id=\"Job_Time_End\" name=\"Job_Time_End\">\n
							<option value=\"--\">--</option>
							";
						for($i = 0; $i < 24; $i++) {

							$ampm = $i >= 12 ? 'pm' : 'am';
							$starttime00 = $i % 12 ? $i % 12 .':00' : 12 .':00';
							$starttime30 = $i % 12 ? $i % 12 .':30' : 12 .':30';
							$sselected00 = $Job_Time_End == $starttime00 . $ampm ? "selected" : "";
							$sselected30 = $Job_Time_End == $starttime30 . $ampm ? "selected" : "";
						?>
							<option value="<?php echo  $starttime00 . $ampm ?>" <?php echo $sselected00; ?>><?php echo  $starttime00 . $ampm ?></option><?php

						?>

						<option value="<?php echo  $starttime30 . $ampm ?>" <?php echo $sselected30; ?>><?php echo  $starttime30 . $ampm ?></option><?php
						}
						echo "  </select>\n</div>";
					echo "</div>";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Location\">".__("Location",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" id=\"Job_Location\" name=\"Job_Location\" value=\"".$Job_Location."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Region\">".__("Region",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\" id=\"Job_Region\" name=\"Job_Region\" value=\"".$Job_Region."\"></div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Type\">".__("Job Type",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div>";
						$get_job_type = $wpdb->get_results("SELECT * FROM " . table_agency_casting_job_type);
						$count = $wpdb->num_rows;
						if( $count <=0 ) {
							echo "<div style=\"float:right;\">".__("There are no job types added",RBAGENCY_casting_TEXTDOMAIN).". <a href=\"".admin_url("admin.php?page=rb_agency_casting_jobpostings&action=manage_types")."\">".__("Click here to add",RBAGENCY_casting_TEXTDOMAIN)."</a></div><div class=\"clear\"></div>";
						} else {
							echo "<select id='Job_Type' name='Job_Type'>";
								echo "<option value=''>-- ".__("Please Select", RBAGENCY_casting_TEXTDOMAIN)." --</option>";
									if(count($get_job_type)) {
										foreach($get_job_type as $jtype){
											echo "<option value='".$jtype->Job_Type_ID."' ".selected($jtype->Job_Type_ID,$Job_Type,false).">".$jtype->Job_Type_Title."</option>";
										}
									}
							echo "</select> ";
						}
						echo "</div>";
					echo "</div>";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">";
						echo "<label for=\"Job_Visibility\">".__("Job Visibility",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div>";
						echo "<select id='Job_Visibility' name='Job_Visibility'>
									<option value=''>-- ".__("Please Select", RBAGENCY_casting_TEXTDOMAIN)." --</option>
									<option value='0' ".selected(isset($Job_Visibility)?$Job_Visibility:"","0",false).">Invite Only</option>
									<option value='1' ".selected(isset($Job_Visibility)?$Job_Visibility:"","1",false).">Open to All</option>
									<option value='2' ".selected(isset($Job_Visibility)?$Job_Visibility:"","2",false).">Matching Criteria</option>
								</select>";
						echo "&nbsp;<a title=\"Match Criteria\" href=\"#TB_inline?width=200&height=550&inlineId=add-criteria\" class=\"thickbox\"  id=\"job_criteria_field\" ".((isset($Job_Visibility) && $Job_Visibility == 2)?"":"style=\"display:none;\"").">".__("Set",RBAGENCY_casting_TEXTDOMAIN)."</a>";
					echo '<input type="hidden" name="Job_Criteria" value="" />';
					echo '<div id="add-criteria" style="display:none;">';
					echo '<script type="text/javascript">';

					if(!empty($Job_Criteria)){
						echo 'jQuery(function(){jQuery("#criteria").html("Loading Criteria List");

								jQuery.ajax({
										type: "POST",
										url: "'. admin_url('admin-ajax.php') .'",
										data: {
											action: "load_criteria_fields",
											value: "'.$Job_Criteria.'"
										},
										success: function (results) {
											jQuery("#criteria").html(results);
										},
										error: function (err){
											console.log(err);
										}
								}); });';
					} else {
							echo 'jQuery(function(){jQuery("#criteria").html("Loading Criteria List");
									jQuery.ajax({
											type: "POST",
											url: "'. admin_url('admin-ajax.php') .'",
											data: {
												action: "load_criteria_fields"
											},
											success: function (results) {
												jQuery("#criteria").html(results);
											},
											error: function (err){
												console.log(err);
											}
									});
								});';
					}
					echo 'jQuery(function(){
							jQuery("#getcriteria").click(function(){
									var criteria = [];
									jQuery("#criteria .rbfield").each(function(){
											if(jQuery(this).hasClass("rbselect")){
												var val = jQuery(this).find("select").val();
												var id = jQuery(this).attr("attrid");
												if(val !="" && val!== undefined && val !== undefined){
													criteria.push(id+"/"+val);
												}
											} else if(jQuery(this).hasClass("rbtext")){
												var val = jQuery(this).find("input[type=text]").val();
												var id = jQuery(this).attr("attrid");
												if(val != ""){
													criteria.push(id+"/"+val);
												}
											} else if(jQuery(this).hasClass("rbmulti")){
												var min = jQuery(this).find(".rbmin").val();
												var max=  jQuery(this).find(".rbmax").val();
												var id = jQuery(this).attr("attrid");
												if(min!="" && max !="" && min!== undefined && max !== undefined){
													criteria.push(id+"/"+min+"-"+max);
												}
											}

											if(jQuery(this).hasClass("rbradio")){
												var val = jQuery(this).find("input:checked").val();
												var id = jQuery(this).attr("attrid");
												if(val != ""){
													criteria.push(id+"/"+val);
												}
											}
											if(jQuery(this).hasClass("rbcheckbox")){
												var arr = [];
												var id = jQuery(this).attr("attrid");
												var val = jQuery(this).find("input:checked").each(function(){
													arr.push(jQuery(this).val());
												});
												criteria.push(arr.toString()+"/"+id);
											}
									});
									jQuery("input[name=\'Job_Criteria\']").val(criteria.join("|"));
									jQuery("input[name=\'Job_Criteria_Profile\']").val(criteria.join("|"));
									console.log(criteria.join("|"));



									jQuery.ajax({
											type: "POST",
											url: "'. admin_url('admin-ajax.php') .'",
											data: {
												action: "load_criteria_fields",
												value : criteria.join("|"),
												jobid : '.$_GET["Job_ID"].'
											},
											success: function (results) {

												jQuery("#criteria").html(results);


											},
											error: function (err){
												console.log(err);
											}
									});

									jQuery(".updatecriteria").html("&nbsp;Criteria successfully added!");
							});
						});
					';
					echo "</script>";
					echo "<style type='text/css'>";
					echo ".rbfield label{float: left;margin-top: 5px;width:150px;}";
					echo ".rbfield {border-bottom:1px solid #ccc;padding-bottom:10px;padding-top:10px;}";
					echo "</style>";
					echo '<div  style="margin:auto;width:70%;">';
						echo '<div id="criteria"></div>';
						echo "<div class=\"rbfield\"><a href=\"javascript:;\" id=\"getcriteria\" class=\"button-primary button\">Update</a><span class=\"updatecriteria\"></span></div>";
						echo "</div>";
					echo "</div>";

					echo "</div>";
					echo "</div>";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "<label for=\"Job_Audition_Date_Start\">".__("Audition Date Start",RBAGENCY_casting_TEXTDOMAIN)."</label>\n";
						echo "<div><input type=\"text\"  class=\"datepicker\" id=\"Job_Audition_Date_Start\" name=\"Job_Audition_Date_Start\" value=\"".$Job_Audition_Date_Start."\"></div>";
					echo "</div>\n";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "<label for=\"Job_Audition_Date_End\">".__("Audition Date End",RBAGENCY_casting_TEXTDOMAIN)."</label>";
						echo "<div><input type=\"text\"  class=\"datepicker\" id=\"Job_Audition_Date_End\" name=\"Job_Audition_Date_End\" value=\"".$Job_Audition_Date_End."\"></div>";
					echo "</div>\n";
					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "<label for=\"Job_Audition_Time\">".__("Audition Time Start",RBAGENCY_casting_TEXTDOMAIN)."</label>\n";
						echo "<div>\n";
						//echo $Job_Audition_Time;
						echo "  <select id=\"Job_Audition_Time\" name=\"Job_Audition_Time\">\
						<option value=\"--\">--</option>
						n";
						for($i = 0; $i <= 24; $i++) {

							$ampm = $i >= 12 ? 'pm' : 'am';
							$starttime00 = $i % 12 ? $i % 12 .':00' : 12 .':00';
							$starttime30 = $i % 12 ? $i % 12 .':30' : 12 .':30';
							$sselected00 = $Job_Audition_Time == $starttime00 . $ampm ? "selected" : "";
							$sselected30 = $Job_Audition_Time == $starttime30 . $ampm ? "selected" : "";
						?>
							<option value="<?php echo  $starttime00 . $ampm ?>" <?php echo $sselected00; ?>><?php echo  $starttime00 . $ampm ?></option><?php

						?>

						<option value="<?php echo  $starttime30 . $ampm ?>" <?php echo $sselected30; ?>><?php echo  $starttime30 . $ampm ?></option><?php
						}
						echo "  </select>\n";
						echo "</div>\n";
						//<input type=\"text\"  class=\"timepicker\" id=\"Job_Audition_Time\" name=\"Job_Audition_Time\" value=\"".$Job_Audition_Time."\"></div>";
					echo "</div>\n";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "<label for=\"Job_Audition_Time_End\">".__("Audition Time End",RBAGENCY_casting_TEXTDOMAIN)."</label>\n";
						echo "<div>\n";
						///echo $Job_Audition_Time_End;
						echo "  <select id=\"Job_Audition_Time_End\" name=\"Job_Audition_Time_End\">\n
						<option value=\"--\">--</option>";
						for($i = 0; $i < 24; $i++) {
							$ampm = $i >= 12 ? 'pm' : 'am';
							$endtime00 = $i % 12 ? $i % 12 .':00' : 12 .':00';
							$endtime30 = $i % 12 ? $i % 12 .':30' : 12 .':30';
							$eselected00 = $Job_Audition_Time_End == $endtime00 . $ampm ? "selected" : "";
							$eselected30 = $Job_Audition_Time_End == $endtime30 . $ampm ? "selected" : "";
						?>
							<option value="<?php echo  $endtime00 . $ampm ?>"  <?php echo $eselected00; ?>><?php echo  $endtime00 . $ampm ?></option><?php

						?>

						<option value="<?php echo  $endtime30 . $ampm ?>" <?php echo $eselected30; ?>><?php echo  $endtime30 . $ampm ?></option><?php
						}
						echo "  </select>\n";
						echo "</div>\n";
						//<input type=\"text\"  class=\"timepicker\" id=\"Job_Audition_Time\" name=\"Job_Audition_Time\" value=\"".$Job_Audition_Time."\"></div>";
					echo "</div>\n";

					echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "<label for=\"Job_Audition_Venue\">".__("Audition Venue",RBAGENCY_casting_TEXTDOMAIN)."</label>\n";
						echo "<div><input type=\"text\" id=\"Job_Audition_Venue\" name=\"Job_Audition_Venue\" value=\"".$Job_Audition_Venue."\"></div>";
					echo "</div>\n";

					if(isset($_GET["Job_ID"])){
						global $wpdb;
						$query_get ="SELECT * FROM ".$wpdb->prefix."agency_casting_job_customfields WHERE Job_ID = ".$_GET['Job_ID'];
						$wpdb->get_results($query_get,ARRAY_A);
						if($wpdb->num_rows == 0){
							rb_get_customfields_admin_castingjobs();
						}else{
							rb_get_customfields_castingjobs();
						}

						echo "<div class=\"rbfield rbtext rbsingle \" id=\"\">\n";
						echo "	<label for=\"comments\">&nbsp;</label>\n";
						echo "	<div>\n";
						echo "		<input type=\"checkbox\" name=\"resend\" value=\"1\"/> &nbsp;".__("Resend notifcation to selected shortlisted talents",RBAGENCY_casting_TEXTDOMAIN)." \n\n";
						echo "	</div>\n";
						echo "</div><br/><br/>";
						echo "<input type=\"submit\" value=\"Save\" name=\"castingJob\" class=\"button-primary\" />";
						echo "<input type=\"hidden\" name=\"action2\" value=\"edit\"/>";
						echo "<input type=\"hidden\" name=\"Job_Talents\" value=\"".$Job_Talents."\"/>";
						echo "<input type=\"hidden\" name=\"Job_Talents_Hash\" value=\"".$Job_Talents_Hash."\"/>";
						echo "<input type=\"hidden\" name=\"Job_Talents_Resend_To\" value=\"\"/>";
						echo "<a href=\"".admin_url("admin.php?page=". $_GET['page'])."\" class=\"button\">Cancel</a>\t";
						echo "<a target=\"_blank\" style=\"float:right;\" href=\"".get_bloginfo("url")."/view-applicants/?filter_jobtitle=".(!empty($_GET["Job_ID"])?$_GET["Job_ID"]:0)."&filter_applicant=&filter_jobpercentage=&filter_rating=&filter_perpage=10&filter=filter\"  class=\"button-primary\">View Applicants</a>";
						echo "<div style=\"clear:both\"></div>";

					} else {
						rb_get_customfields_admin_castingjobs();
						echo "<input type=\"hidden\" name=\"action2\" value=\"add\"/>";
						echo "<input type=\"submit\" value=\"Submit\" name=\"castingJob\" class=\"button-primary\" />";
						echo "<a href=\"".admin_url("admin.php?page=rb_agency_castingjobs")."\" class=\"button\">".__("Cancel",RBAGENCY_casting_TEXTDOMAIN)."</a>";

					}

				echo "</form><!-- .castingtext -->";
				echo "</div><!-- .innerr -->";
				//  }// if casting cart is not empty
					echo '<script type="text/javascript">
							jQuery(document).ready(function(){
								jQuery( ".datepicker" ).datepicker();
								jQuery( ".datepicker" ).datepicker("option", "dateFormat", "yy-mm-dd");
								jQuery("#Job_Date_Start").val("'.$Job_Date_Start.'");
								jQuery("#Job_Date_End").val("'.$Job_Date_End.'");
								jQuery("#Job_Audition_Date_Start").val("'.$Job_Audition_Date_Start.'");
								jQuery("#Job_Audition_Date_End").val("'.$Job_Audition_Date_End.'");
								jQuery("#Job_Visibility").change(function(){
									if(jQuery(this).val() == 2){
										jQuery("#job_criteria_field").show();
									} else {
										jQuery("#criteria").html("");
										jQuery("#job_criteria_field").hide();
									}
								});

								//jQuery(".timepicker").timepicker({
								//	hourGrid: 4,
								//	minuteGrid: 10,
								//	timeFormat: "hh:mm tt"
								//});
							});
					</script>';
				echo "</div><!-- .boxblock -->";
				echo "</div><!-- .boxblock-container -->";


				$cartArray = null;
				// Set Casting Cart Session
				if (isset($_SESSION['cartArray']) && !isset($_GET["Job_ID"])) {

					$cartArray = $_SESSION['cartArray'];
				} elseif(isset($_GET["Job_ID"])){
					$cartArray = explode(",",$Job_Talents);
				}
				?>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					var arr = [];
					var arr_casting = [];


					jQuery("form[name=formDeleteProfile] input[type=checkbox]").each(function(){
						arr.push(jQuery(this).val());
					});
					//jQuery("input[name=Job_Talents_Resend_To]").val(arr.toString());




					jQuery("#selectall").change(function(){
							var ischecked = jQuery(this).is(':checked');
							jQuery("form[name=formDeleteProfile] input[type=checkbox]").each(function(){
								if(ischecked){
								jQuery(this).removeAttr("checked");
								jQuery(this).prop("checked",true);
								arr.push(jQuery(this).val());
								} else {
								jQuery(this).prop("checked",true);
								jQuery(this).removeAttr("checked");
								arr = [];
								}
							});
							jQuery("input[name=Job_Talents_Resend_To]").val(arr.toString());
					});

					jQuery("#selectallcasting").change(function(){
							var ischecked = jQuery(this).is(':checked');
							jQuery("form[name=formDeleteCastingProfile] input[type=checkbox]").each(function(){
								if(ischecked){
								jQuery(this).removeAttr("checked");
								jQuery(this).prop("checked",true);
								arr.push(jQuery(this).val());
								} else {
								jQuery(this).prop("checked",true);
								jQuery(this).removeAttr("checked");
								arr = [];
								}
							});
							jQuery("input[name=Job_Talents_Resend_To]").val(arr.toString());
					});

					jQuery("#selectallcasting").change(function(){
							var ischecked = jQuery(this).is(':checked');
							jQuery("#castingcartbox input[type=checkbox]").each(function(){
								if(ischecked){
								jQuery(this).removeAttr("checked");
								jQuery(this).prop("checked",true);
								arr_casting.push(jQuery(this).val());
								} else {
								jQuery(this).prop("checked",true);
								jQuery(this).removeAttr("checked");
								arr_casting = [];
								}
							});
					});

					jQuery("input[name^=deleteprofilescasting]").click(function(){
							if(jQuery("#castingcartbox input[name^=profiletalent]:checked").length > 0){
								if(confirm("Are you sure that you want to delete the selected profiles? Click 'Yes' to delete, 'Cancel' to exit.")){
									jQuery("#castingcartbox input[name^=profiletalent]:checked").each(function(){
											jQuery("form[name=formDeleteCastingProfile]").submit();
									});
								}
							} else {
								alert("You must select a profile to delete");
							}

					});

					//jQuery("input[name=Job_Talents_Resend_To]").val('');
					jQuery("#shortlisted input[name^=profiletalent],#castingcartbox input[name^=profiletalent]").click(function(){
						Array.prototype.remove = function(value) {

							var idx = this.indexOf(value);
							if (idx != -1) {
								//alert(idx);
								return this.splice(idx, 1);
							}
							return false;
						}
						if(jQuery(this).is(':checked')){
							arr.push(jQuery(this).val());

						} else {
							arr.remove(jQuery(this).val());
						}
						jQuery("input[name=Job_Talents_Resend_To]").val(arr.toString());
					});

					jQuery("#shortlisted input[name^=deleteprofiles]").click(function(){
						if(jQuery("#shortlisted input[name^=profiletalent]:checked").length > 0){
							if(confirm("Are you sure that you want to delete the selected profiles? Click 'Yes' to delete, 'Cancel' to exit.")){
								jQuery("#shortlisted input[name^=profiletalent]:checked").each(function(){
									jQuery("form[name=formDeleteProfile]").submit();
								});
							}
						} else {
							alert("You must select a profile to delete");
						}
					});

					jQuery("#shortlisted input[name^=addtocastingcart]").click(function(){
						if(jQuery("#shortlisted input[name^=profiletalent]:checked").length > 0){
								if(confirm("Are you sure that you want to add the selected profiles to casting cart? Click 'Yes' to add, 'Cancel' to exit.")){
									jQuery("input[name=addprofilestocasting]").val(arr.toString());
									jQuery("form[name=formAddProfileToCasting]").submit();
								}
							} else {
								alert("You must select a profile");
							}
					});
					});
				</script>

				<?php
				if((!empty( $_SESSION['cartArray']) || isset($_GET["Job_ID"])) ):

					if( (isset($_GET["action2"]) && $_GET["action2"] != "addnew") || !isset($_GET["action2"])) {

						$casting_cart = $wpdb->get_results($wpdb->prepare("SELECT CastingCartTalentID FROM ".table_agency_castingcart." WHERE CastingJobID = %d ",$_GET["Job_ID"]),ARRAY_A);

							// Show Cart
							$arr_profiles = array();
							foreach ($casting_cart as $key) {
								if(is_numeric($key["CastingCartTalentID"])){
									array_push($arr_profiles, $key["CastingCartTalentID"]);
								}

							}
							$imploded_arr_profiles = '('.implode(',',$arr_profiles).')';
							//echo $imploded_arr_profiles;
							//$query = "SELECT  profile.*,media.* FROM ". table_agency_profile ." profile, ". table_agency_profile_media ." media WHERE profile.ProfileID = media.ProfileID AND media.ProfileMediaType = \"Image\" AND media.ProfileMediaPrimary = 1 AND profile.ProfileID IN $imploded_arr_profiles ORDER BY profile.ProfileContactNameFirst ASC";
							//$query = "SELECT  profile.* FROM ". table_agency_profile ." profile WHERE ProfileID IN (,651,661,663,657,660,657,657,663,661,657,663,661,651) ORDER BY ProfileContactNameFirst ASC";
							$query = "SELECT  profile.*,media.ProfileMediaPrimary,media.ProfileMediaType,media.ProfileMediaURL FROM ". table_agency_profile ." profile  LEFT JOIN ". table_agency_profile_media ." media ON (profile.ProfileID = media.ProfileID AND media.ProfileMediaType = \"Image\" AND media.ProfileMediaPrimary = 1 ) WHERE profile.ProfileID IN $imploded_arr_profiles ORDER BY profile.ProfileContactNameFirst ASC";
							$results = $wpdb->get_results($query, ARRAY_A);
							$wpdb->last_error;
							$total_casting_profiles = $wpdb->num_rows;
						echo "<div id=\"castingcartbox\" class=\"boxblock-container\" >";
						echo "<div class=\"boxblock\">";
						echo "<h3 style=\"overflow:hidden\">Client's Casting Cart - ".($total_casting_profiles > 1?$total_casting_profiles." profiles":$total_casting_profiles." profile");
						echo "<span style=\"font-size:12px;float:right;\">";
						echo "<a  href=\"#TB_inline?width=600&height=350&inlineId=notifyclient\" class=\"thickbox button-primary\" title=\"Notify Client\">Notify Client</a>";
						echo "| <input type=\"submit\" name=\"deleteprofilescasting\" class=\"button-primary\" id=\"deleteprofiles\" value=\"Remove selected\" />";
						echo "<input type=\"checkbox\" id=\"selectallcasting\"/>Select all</span>";
						echo "</h3>";
						echo "<div id=\"notifyclient\" style=\"display:none;\">";
						echo "<form method=\"post\" action=\"\">";
						echo "<input type=\"hidden\" name=\"notifyclient\" value=\"1\"/>";
						echo "<table>";
						echo "<tr>";
						echo "<td><label>Client's Email:</label></td>";
						echo "<td   style=\"width:500px;\"><input type=\"text\" disabled=\"disabled\" name=\"emailaddress\" style=\"width:100%;\" value=\"".$CastingContactEmail."\"/></td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td style=\"vertical-align: top;\"><label>BCC:</label></td>";
						echo "<td   style=\"width:500px;\">";
						echo "<input type=\"text\" name=\"bcc_emails\" style=\"width:100%;\" value=\"\"/>";
						echo "<span style=\"font-size:11px;color:#ccc;\">You can enter multiple addresses, separated by commas.</span>";
						echo "</td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td valign=\"top\"><label>Message:</label></td>";
						echo "<td  style=\"width:500px;\"><textarea name=\"message\"  style=\"width:100%;height:200px;\">Hi ".$CastingContactDisplay.", \n\nWe have updated the casting cart for the job ".ucfirst($Job_Title).".\n\nTo review, please click the link below: \n\n". get_bloginfo("wpurl")."/profile-casting/?Job_ID=".$Job_ID."\n\n\n - ".get_bloginfo("name")."</textarea></td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td>&nbsp;</td><td><input type=\"submit\" value=\"Send\" class=\"button-primary\"/></td>";
						echo "</tr>";
						echo "</table>";
						echo "</form>";
						echo "</div>";
							echo "<form method=\"post\" name=\"formDeleteCastingProfile\" action=\"".admin_url("admin.php?page=rb_agency_castingjobs&action=informTalent&Job_ID=".(!empty($_GET["Job_ID"])?$_GET["Job_ID"]:0))."\" class=\"rbaction-list\">\n";
							echo "<input type=\"hidden\" name=\"action2\" value=\"deletecastingprofile\"/>";
							echo "<div class=\"innerr\" style=\"padding: 10px;\">";
								foreach ($results as $data) {
									echo "<div class=\"list-item\" id=\"profile-".$data["ProfileID"]."\">";
									echo "<div class=\"photo\">";
									echo "	<a href=\"". RBAGENCY_PROFILEDIR . $data['ProfileGallery'] ."/\" target=\"_blank\"><img \" src=\"". get_bloginfo("url")."/wp-content/plugins/rb-agency/ext/timthumb.php?src=".RBAGENCY_UPLOADDIR . $data["ProfileGallery"] ."/". $data['ProfileMediaURL'] ."&h=170&w=170&zc=2\" /></a>";
									echo "</div>\n";
									echo "<br>\n";
									echo "<strong class=\"name\">".(isset($_GET["Job_ID"])?"<input type=\"checkbox\" name=\"profiletalent_".$data["ProfileID"]."\" value=\"".$data["ProfileID"]."\"/>":""). stripslashes($data['ProfileContactNameFirst']) ." ". stripslashes($data['ProfileContactNameLast']) . "</strong><br>";
									if(isset($_GET["Job_ID"])){
										$query = "SELECT CastingAvailabilityStatus as status FROM ".table_agency_castingcart_availability." WHERE CastingAvailabilityProfileID = %d AND CastingJobID = %d";
										$prepared = $wpdb->prepare($query,$data["ProfileID"],$_GET["Job_ID"]);
										$availability = $wpdb->get_row($prepared);

										$count2 = $wpdb->num_rows;

										if($count2 <= 0){
											echo "<span class=\"status unconfirmed\">Unconfirmed</span><br>";
										} else {
											if($availability->status == "available"){
												echo "<span class=\"status available\">Available</span><br>";
											} else {
												echo "<span class=\"status notavailable\">Not Available</span><br>";
											}
										}

										$dir = RBAGENCY_UPLOADPATH ."_casting-jobs/";
												@$files = scandir($dir, 0);

												$medialink_option = $rb_agency_options_arr['rb_agency_option_profilemedia_links'];

												for($i = 0; $i < count($files); $i++){
												$parsedFile = explode('-',$files[$i]);

													if($parsedFile[0] == $_GET['Job_ID'] && $data["ProfileID"] == $parsedFile[1]){
														//$mp3_file = str_replace(array($parsedFile[0].'-',$parsedFile[1].'-'),'',$files[$i]);
														if($medialink_option == 2){
															//open in new window and play
															echo '<a href="'.site_url().'/wp-content/uploads/profile-media/_casting-jobs/'.$files[$i].'" target="_blank">Play Audio</a><br>';
														}elseif($medialink_option == 3){
															//open in new window and download
															//$force_download_url = RBAGENCY_PLUGIN_URL."ext/forcedownload.php?file=".'_casting-jobs/'.$files[$i];
															//echo '<a href="'.$force_download_url.'" target="_blank">Download Audio</a><br>';
															$force_download_url = wpfdl_dl('_casting-jobs/'.$files[$i],get_option('wpfdl_token'),'dl');
															echo '<a '.$force_download_url.' target="_blank">Play Audio</a><br>';
														}

													}
												}
									}
									echo "</div>\n";
									echo "<style type=\"text/css\">";
									echo "#shortlisted #profile-".$data["ProfileID"]."{opacity: 0.3;}";
									echo "</style>";

								}

								if($total_casting_profiles <= 0){
									echo __("No Profiles Found", RBAGENCY_TEXTDOMAIN);
								}

							echo "<div class=\"clear\"></div>";
							echo "</div>";
							echo "</form>";
						echo "</div>";
						echo "</div>";
						}
						// Talents Shortlisted
						echo "<div id=\"shortlisted\" class=\"boxblock-container\" >";
						echo "<div class=\"boxblock\">";
						if(!empty($cartArray)){
									$cartString = implode(",", array_unique($cartArray));
									$cartString = RBAgency_Common::clean_string($cartString);
						}
						// Show Cart
						//$query = "SELECT  profile.*,media.* FROM ". table_agency_profile ." profile, ". table_agency_profile_media ." media WHERE profile.ProfileID = media.ProfileID AND media.ProfileMediaType = \"Image\" AND media.ProfileMediaPrimary = 1 AND profile.ProfileID IN (".(!empty($cartString)?$cartString:0).") ORDER BY profile.ProfileContactNameFirst ASC";

						$query = "SELECT  profile.*,media.ProfileMediaPrimary,media.ProfileMediaType,media.ProfileMediaURL FROM ". table_agency_profile ." profile  LEFT JOIN ". table_agency_profile_media ." media ON (profile.ProfileID = media.ProfileID AND media.ProfileMediaType = \"Image\" AND media.ProfileMediaPrimary = 1 ) WHERE profile.ProfileID IN (".(!empty($cartString)?$cartString:0).") ORDER BY profile.ProfileContactNameFirst ASC";
						$results = $wpdb->get_results($query, ARRAY_A);
						$count = $wpdb->num_rows;
						$total_casting_profiles = $count;


						echo "<h3 style=\"overflow: hidden\">Talents Shortlisted by Admin - ".($total_casting_profiles > 1?$total_casting_profiles." profiles":$total_casting_profiles." profile");
						if(!empty( $_SESSION['cartArray']) || isset($_GET["Job_ID"])){
							echo "<span style=\"font-size:12px;float:right;\">";
							echo "<a  href=\"#TB_inline?width=600&height=550&inlineId=add-profiles;get_profiles();\" class=\"thickbox button-primary\" title=\"Add profiles to '".$Job_Title."' Job\">Add Profiles</a>";
							if(isset($_GET["Job_ID"])){
								echo "<input type=\"submit\" name=\"deleteprofiles\" class=\"button-primary\" id=\"deleteprofiles\" value=\"Remove selected\" />";
								echo "<input type=\"submit\" name=\"addtocastingcart\" class=\"button-primary\" id=\"addtocastingcart\" value=\"Add to Client's Casting Cart\" />";
								echo "<input type=\"checkbox\" id=\"selectall\"/>Select all</span>";
							}
						}
						echo "</h3>";
						echo "<div class=\"innerr\" style=\"padding: 10px;\">";

					?>
					<?php add_thickbox(); ?>
					<div id="add-profiles" style="display:none;">
					<input type="hidden" name="Job_Criteria_Profile" value="" />
					<table>
					<tr>
					<td><label>First Name:</label> <input type="text" name="firstname"/></td>
					<td><label>Last Name:</label> <input type="text" name="lastname"/></td>
					</tr>
					</table>
					<div class="results-info" style="width:80%;float:left;border:1px solid #fafafa;padding:5px;background:#ccc;">
						Loading...
					</div>
					<input type="submit" value="Add to Job" id="addtojob" class="button-primary" style="float:right" />

					<div id="profile-search-result">

					</div>
					<style type="text/css">
					.profile-search-list{
						background:#FAFAFA;
						width: 31.3%;
						float:left;
						margin:5px;
						cursor: pointer;
						border:1px solid #fff;
					}
					.profile-search-list.selected{
						border:1px solid black;
					}
					.castingtext label{
						float: left;
						margin-top: 5px;
						margin-right: 20px;
						width:140px;
					}
					.castingtext input[type=text], .castingtext textarea{
						width:50%;
					}
					</style>

					</div>
					<script type="text/javascript">
					jQuery(function(){
							var arr_profiles = [];
							var selected_info = "";
							var total_selected = 0;
							var arr_listed = Array();

							jQuery("form[name=formDeleteProfile] div[id^=profile-]").each(function(i,d){
									arr_listed[i] = jQuery(this).attr("id").split("profile-")[1];
							});

							function get_profiles(){


								console.log(jQuery("input[name=\'Job_Criteria_Profile\']").val());
								var param = jQuery("input[name=\'Job_Criteria_Profile\']").val();

								jQuery.ajax({
										dataType: 'json',
										url: '<?php echo admin_url('admin-ajax.php','relative'); ?>',
										data: {
											'action': 'rb_agency_search_profile',
											'value' : param
										},
										success: function(d){
											var profileDisplay = "";
											console.log(arr_listed);
											jQuery.each(d,function(i,p){
												if(jQuery.inArray(p.ProfileID+"",arr_listed) < 0){

														var fullname = p.ProfileContactNameFirst+" "+p.ProfileContactNameLast;

														if(fullname.length > 10) fullname = fullname.substring(0,15)+"[..]";

														profileDisplay = "<table class=\"profile-search-list\" id=\"profile-"+p.ProfileID+"\">"
																		+"<tr>"
																			+"<td style=\"width:40px;height:40pxbackground:#ccc;\">"+((p.ProfileMediaURL !="")?"<img src=\"<?php echo  get_bloginfo('url').'/wp-content/plugins/rb-agency/ext/timthumb.php?src='.RBAGENCY_UPLOADDIR;?>/"+p.ProfileGallery+"/"+p.ProfileMediaURL+"&w=40&h=40&zc=2\" style=\"width:40px;height:40px;\"/>":"")+"</td>"
																			+"<td>"
																			+"<strong>"+fullname+"</strong>"
																			+"<br/>"
																			+"<span style=\"font-size: 11px;\">"+getAge(p.ProfileDateBirth)+","+p.GenderTitle+"</span>"
																			+"<br/>"
																			+"<a href=\"<?php echo get_bloginfo("wpurl");?>/profile/"+p.ProfileGallery+"/\" target=\"_blank\">View Profile</a>"
																			+"</td>"
																		+"</tr>"
																		+"</table>";
														jQuery("#profile-search-result").append(profileDisplay);
														arr_profiles.push({name:p.ProfileContactNameFirst.toLowerCase()+" "+p.ProfileContactNameLast.toLowerCase(),profileid:p.ProfileID});

												}
											});

											jQuery("table[class^=profile-search-list]").click(function(){
													jQuery(this).toggleClass("selected" );
													total_selected = 0;
													jQuery("table.profile-search-list.selected").each(function(){
														total_selected++;

													});
													jQuery(".selected-info").remove();
													if(total_selected >0){
														jQuery("#TB_ajaxWindowTitle").html(jQuery("#TB_ajaxWindowTitle").html()+"<span class=\"selected-info\"> - "+total_selected+" profiles selected.</span>");
													}

											});

											jQuery(".results-info").html(arr_profiles.length+ " Profiles found. "+selected_info);

										},
										error: function(e){
											console.log(e);
										}
								});
							}

							get_profiles();

							function getAge(dateString)
							{
								var today = new Date();
								var birthDate = new Date(dateString);
								var age = today.getFullYear() - birthDate.getFullYear();
								var m = today.getMonth() - birthDate.getMonth();
								if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate()))
								{
									age--;
								}
								if(isNaN(age)){
									age = "Not Set";
									return age;
								}
								return age+"y/o";
							}

							var fname = jQuery("div[id=add-profiles] input[name=firstname]");
							var lname = jQuery("div[id=add-profiles] input[name=lastname]");
							jQuery("#add-profiles input[name=firstname],#add-profiles input[name=lastname]").keyup(function(){
								var keyword = fname.val().toLowerCase()+ " " +lname.val().toLowerCase();

								var result = find(arr_profiles,keyword);

								if(result.length > 0){
									jQuery("table[id^=profile-]").hide();
									jQuery("table[id^=profile-][class=selected]").show();

									jQuery.each(result,function(i,p){
										jQuery("table[id^='profile-"+p.profileid+"']").show();
									});
									jQuery(".results-info").html("Search Result: "+result.length+" "+(result.length>1?"profiles":"profile")+" found. "+selected_info);
								} else {
									jQuery(".results-info").html("'"+keyword+"' not found. "+selected_info);
								}
							});

							function find(arr,keyword) {
									var result = [];

								jQuery.each(arr,function(i,p){
									if (p.name.indexOf(keyword) >= 0) {
										result.push({profileid:p.profileid});
									}
								});

								return result;
							}

							jQuery("#addtojob").click(function(){
								var arr_profiles_selected = [];
								jQuery("table.profile-search-list.selected").each(function(){
									var profiles = jQuery(this).attr("id").split("profile-")[1];
									arr_profiles_selected.push(profiles);
								});
								jQuery("input[name=addprofiles]").val(arr_profiles_selected.join());
								window.parent.tb_remove();
								arr_profiles_selected = [];
								jQuery("form[name=formAddProfile]").submit();

							});
					});
					</script>
				<?php
				if(isset($_GET["action2"]) && $_GET["action2"] == "addnew"){
					echo "<form method=\"post\" name=\"formAddProfile\" action=\"".admin_url("admin.php?page=rb_agency_castingjobs&action2=addnew&action=informTalent")."\" >\n";
				} else {
					echo "<form method=\"post\" name=\"formAddProfile\" action=\"".admin_url("admin.php?page=rb_agency_castingjobs&action=informTalent&Job_ID=".(!empty($_GET["Job_ID"])?$_GET["Job_ID"]:0))."\" >\n";
				}
				echo "<input type=\"hidden\" value=\"\" name=\"addprofiles\"/>";
				echo "</form>";

				echo "<form method=\"post\" name=\"formAddProfileToCasting\" action=\"".admin_url("admin.php?page=rb_agency_castingjobs&action=informTalent&Job_ID=".(!empty($_GET["Job_ID"])?$_GET["Job_ID"]:0))."\" >\n";
				echo "<input type=\"hidden\" value=\"\" name=\"addprofilestocasting\"/>";
				echo "<input type=\"hidden\" value=\"".(isset($Job_Agency_ID)?$Job_Agency_ID:"") ."\" name=\"Agent_ID\" />";
				echo "</form>";

				echo "<form method=\"post\" name=\"formDeleteProfile\" action=\"".admin_url("admin.php?page=rb_agency_castingjobs&action=informTalent&Job_ID=".(!empty($_GET["Job_ID"])?$_GET["Job_ID"]:0))."\" class=\"rbaction-list\" >\n";
				echo "<input type=\"hidden\" name=\"action2\" value=\"deleteprofile\"/>";
				foreach ($results as $data) {
					echo "<div class=\"list-item\" id=\"profile-".$data["ProfileID"]."\">";
					echo "<div class=\"photo\">";
					echo "<a href=\"". RBAGENCY_PROFILEDIR . $data['ProfileGallery'] ."/\" target=\"_blank\">";
					echo "<img src=\"". get_bloginfo("url")."/wp-content/plugins/rb-agency/ext/timthumb.php?src=".RBAGENCY_UPLOADDIR . $data["ProfileGallery"] ."/". $data['ProfileMediaURL'] ."&h=170&w=170&zc=2\" />";
					echo "</a>";
					echo "</div>\n";
					echo "<br>";
					echo "<strong class=\"name\">";
					if(isset($_GET["Job_ID"])){
						echo "<input type=\"checkbox\" name=\"profiletalent_".$data["ProfileID"]."\" value=\"".$data["ProfileID"]."\"/>";
					}
					echo  stripslashes($data['ProfileContactNameFirst']) ." ". stripslashes($data['ProfileContactNameLast']) . "</strong><br>";
						if(isset($_GET["Job_ID"])){

							echo "<input type=\"hidden\" name=\"delete_profile_id[]\" value=\"".$data["ProfileID"]."\">";

							$query = "SELECT CastingAvailabilityStatus as status FROM ".table_agency_castingcart_availability." WHERE CastingAvailabilityProfileID = %d AND CastingJobID = %d";
							$prepared = $wpdb->prepare($query,$data["ProfileID"],$_GET["Job_ID"]);
							$availability = current($wpdb->get_results($prepared));

							$count2 = $wpdb->num_rows;

							if($count2 <= 0){
								echo "\n<span class=\"status unconfirmed\">Unconfirmed</span><br>";
							} else {
								if($availability->status == "available"){
									echo "\n<span class=\"status available\">Available</span><br>";
								} else {
									echo "\n<span class=\"status notavailable\">Not Available</span><br>";
								}
							}



												$dir = RBAGENCY_UPLOADPATH ."_casting-jobs/";
												@$files = scandir($dir, 0);

												$medialink_option = $rb_agency_options_arr['rb_agency_option_profilemedia_links'];

												for($i = 0; $i < count($files); $i++){
													//echo $files[$i];
												$parsedFile = explode('-',$files[$i]);

													if($parsedFile[0] == $_GET['Job_ID'] && $data["ProfileID"] == $parsedFile[1]){
														$mp3_file = str_replace(array($parsedFile[0].'-',$parsedFile[1].'-'),'',$files[$i]);
														if($medialink_option == 2){
															//open in new window and play
															echo '<a href="'.site_url().'/wp-content/uploads/profile-media/_casting-jobs/'.$files[$i].'" target="_blank">Play Audio</a><br>';
														}elseif($medialink_option == 3){
															//open in new window and download

															$force_download_url = wpfdl_dl('_casting-jobs/'.$files[$i],get_option('wpfdl_token'),'dl');
															echo '<a '.$force_download_url.' target="_blank">Play Audio</a><br>';
														}

													}
												}
						}
					echo "</div>\n";
				}
				echo "</form>\n";
				echo "	<style type=\"text/css\">
							.rbaction-list .list-item { float: left; width: 170px; margin-right:10px; margin-top: 10px; text-align: center; }
							.rbaction-list .list-item .name { display: inline-block; }
							.rbaction-list .list-item .status { display: inline-block; padding: 5px; font-weight: bold; font-size: small; }
							.rbaction-list .list-item .status.unconfirmed { color: #5505ff; }
							.rbaction-list .list-item .status.available { color: #2bc50c; }
							.rbaction-list .list-item .status.notavailable { color: #ee0f2a; }
						</style>";

				if($count <= 0){
					echo __("No Profiles Found", RBAGENCY_TEXTDOMAIN);
				}

						echo "<div style=\"clear:both;\"></div>";
					echo "</div>";
				echo "</div>";
				echo "</div>";
			endif;
		echo "</div>";

		}}// end add/edit job



if(isset($_GET['action']) && $_GET['action'] == 'viewAllAuditions' && isset($_GET['Job_ID'])){


	$download_applicants_url =  RBAGENCY_casting_BASEDIR."view/download_applicants.php";
	if(isset($_POST["mass_delete"])){
				unset($_POST["mass_delete"]);
				unset($_POST['batch_download']);

				$ids_arr = array();
			foreach($_POST['usercheckbox'] as $k=>$v){
				$ids_arr[] = $v;
				//echo $v;
			}
			$ids = implode(",",$ids_arr);

				$wpdb->query("DELETE FROM ".table_agency_castingcart_availability." WHERE CastingAvailabilityProfileID IN(".$ids.") ");
				//print_r($_POST);
				echo "<div id=\"message\" class=\"updated\"><p>Successfully deleted.</p></div>";

			}

	if(isset($_POST['batch_download'])){

		if($_POST['exportType'] == 'none'){

			echo "<script> alert('Please select file type format'); </script>";
		}elseif(empty($_POST['usercheckbox'])){
			echo "<script> alert('Please select profile'); </script>";
		}else{
			unset($_POST["mass_delete"]);
			unset($_POST['batch_download']);
			$exporttype = $_POST['exportType'];
			unset($_POST['exportType']);

			$ids_arr = array();
			foreach($_POST['usercheckbox'] as $k=>$v){
				$ids_arr[] = $v;
				//echo $v;
			}
			$ids = implode(",",$ids_arr);
			//echo $ids;
			header("Location:".$download_applicants_url."?profileids=".$ids."&Job_ID=".$_GET['Job_ID']."&export_type=".$exporttype);

		}


	}


	//echo RBAGENCY_PLUGIN_URL;


	$q = "SELECT cs_job.*, avail.* FROM  ".table_agency_casting_job." AS cs_job INNER JOIN ".table_agency_castingcart_availability."
					AS avail ON cs_job.Job_ID = avail.CastingJobID WHERE cs_job.Job_ID = ".$_GET['Job_ID'];

	//$q = "SELECT * FROM ".table_agency_castingcart." WHERE CastingJobID = ".$_GET['Job_ID'];
	$profileIDs_arr = array();
	$job_data = $wpdb->get_results($q);
	/**echo $wpdb->last_error;
	foreach($qq as $qqq){
		if(is_numeric($qqq->CastingAvailabilityProfileID)){
			$profileIDs_arr[] = $qqq->CastingAvailabilityProfileID;
		}

		//$qqq->CastingCartTalentID;
	}

	$imploded_profileIDs_arr = '('.implode(',',$profileIDs_arr).')';
	//echo $imploded_profileIDs_arr;

	$query = "SELECT cs_job.*, avail.* FROM  ".table_agency_casting_job." AS cs_job INNER JOIN ".table_agency_castingcart_availability."
					AS avail ON cs_job.Job_ID = avail.CastingJobID WHERE avail.CastingAvailabilityProfileID IN $imploded_profileIDs_arr
					";
	$job_data = $wpdb->get_results($query);**/

	//echo "<pre>";
	//print_r($job_data);
	//echo "</pre>";
	?>
	<style>
	.checkAllAuditions{
		margin-top: 10px!important;
	margin-bottom: -40px!important;
	}
	</style>
	<br>
	<h1>Auditions for <?php echo $job_data[0]->Job_Title; ?></h1>
	<form method="post" action="<?php echo admin_url("admin.php?page=". $_GET['page']."&action=viewAllAuditions&Job_ID=".$_GET['Job_ID']); ?>">
	<table cellspacing="0" class="widefat fixed">
		<thead>
			<tr class="thead">
				<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" class="checkAllAuditions"/></th>
				<th class="column" scope="col">Name</th>
				<th class="column" scope="col">Job Title</th>
				<th class="column" scope="col">Date Confirmed</th>
				<th class="column" scope="col">MP3 Audition Files</th>
				<th class="column" scope="col">Availability</th>
			</tr>
		</thead>
		<tbody>

			<?php
				if(count($job_data) > 0)
				{
					foreach($job_data as $job)
					{
						$fullname = $profile['ProfileContactNameFirst'].' '.$profile['ProfileContactNameLast'];

							if(empty($fullname)){
								$wpdb->query("DELETE FROM ".table_agency_castingcart_availability." WHERE CastingAvailabilityProfileID = ".$job->CastingAvailabilityProfileID);
							}

							if(!empty($job->CastingAvailabilityProfileID)){
			?>
				<tr>
				<th class="check-column" scope="row">
					<input type="checkbox" value="<?php echo $job->CastingAvailabilityProfileID; ?>" class="administrator" id="<?php echo $_GET['Job_ID']; ?>" name="usercheckbox[]"/>
				</th>
				<td>
				<?php
				$profileID = $job->CastingAvailabilityProfileID;
				$queryData1 = "SELECT * FROM " . table_agency_profile . " WHERE ProfileID = ".$profileID;
				$qd = $wpdb->get_results($queryData1,ARRAY_A);
				//print_r($qd);
				foreach($qd as $profile){
					$fullname = $profile['ProfileContactNameFirst'].' '.$profile['ProfileContactNameLast'];
				}
				if(!empty($fullname)){
					echo stripcslashes($fullname);
				}else{
					//remove the profile
					//echo $profileID;

				}

				?>
				</td>
				<td><a href="<?php echo site_url(); ?>/job-detail/<?php echo $job->Job_ID ?>" target="_blank"><?php echo $job->Job_Title; ?></a></td>
				<td><?php echo $job->CastingAvailabilityDateCreated; ?></td>
				<td>


				<?php



					$profileID = $job->CastingAvailabilityProfileID;
					@$dir = RBAGENCY_UPLOADPATH ."_casting-jobs/";
					$files = scandir($dir, 0);

					$medialink_option = $rb_agency_options_arr['rb_agency_option_profilemedia_links'];

					for($i = 0; $i < count($files); $i++){
						$parsedFile = explode('-',$files[$i]);

							if($parsedFile[0] == $job->Job_ID && $profileID == $parsedFile[1]){
								//$mp3_file = str_replace(array($parsedFile[0].'-',$parsedFile[1].'-'),'',$files[$i]);
									if($medialink_option == 2){
										//open in new window and play
										echo '<a href="'.site_url().'/wp-content/uploads/profile-media/_casting-jobs/'.$files[$i].'" target="_blank">Play Audio</a><br>';
									}elseif($medialink_option == 3){
										//open in new window and download
										//$force_download_url = RBAGENCY_PLUGIN_URL."ext/forcedownload.php?file=".'_casting-jobs/'.$files[$i];
										//echo '<a href="'.$force_download_url.'" target="_blank">'.$mp3_file.'</a><br>';

										$force_download_url = wpfdl_dl('_casting-jobs/'.$files[$i],get_option('wpfdl_token'),'dl');
										echo '<a '.$force_download_url.' target="_blank">Play Audio</a><br>';
									}

							}
					}
				?>
				</td>
				<td><?php echo ucfirst($job->CastingAvailabilityStatus); ?></td>
			</tr>

			<?php
						}
					}
				}

				?>
		</tbody>
		<tfoot>
					<tr class="thead">
						<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" class="checkAllAuditions"/></th>
						<th class="column">Name</th>
						<th class="column">Job Title</th>
						<th class="column">Date Confirmed</th>
						<th class="column">MP3 Audition Files</th>
						<th class="column">Availability</th>
					</tr>
				</tfoot>
	</table>
	<br>
	<input type="submit" class="btn button-secondary" onclick="javascript:return !confirm('Are you sure that you want to delete the selected?')?false:true;" name="mass_delete" value="Delete"/>
	<input type="submit" class="btn button-secondary" onclick="javascript:return true;" name="batch_download" value="Download"/> <select name="exportType"><option value="none">SELECT FILE FORMAT</option><option value="csv">CSV</option><option value="xls">XLS</option></select>

<?php

}elseif(!isset($_GET['action']) || isset($_GET['Job_Title']) || isset($_GET['page_index'])){
	// Load casting jobs list
		RBAgency_Casting::rb_display_casting_jobs();
}
		?>
