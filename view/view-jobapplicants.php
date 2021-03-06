<?php
global $wpdb;
global $current_user;

// include casting class
include(dirname(dirname(__FILE__)) ."/app/casting.class.php");

wp_deregister_script('jquery'); 
wp_register_script('jquery_latest', 'http://code.jquery.com/jquery-1.11.0.min.js'); 
wp_enqueue_script('jquery_latest');

// rb agency settings
	$rb_agency_options = get_option('rb_agency_options');
	$rb_agency_option_allowsendemail = isset($rb_agency_options["rb_agency_option_allowsendemail"])?$rb_agency_options["rb_agency_option_allowsendemail"]:""; 
	$rb_agency_option_agencyemail = $rb_agency_options["rb_agency_option_agencyemail"];


echo $rb_header = RBAgency_Common::rb_header();

?>



<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery(".send_invite").click(function(){
		jQuery(this).html("Sending...");
		jQuery(this).html("Invited.");
	});

	jQuery("#sel_all").click(function(){
		if(jQuery(this).is(':checked')){
			jQuery(".select_app").attr("checked",true);
		} else {
			jQuery(".select_app").attr("checked",false);
		}
	});

	jQuery("#action_submit").click(function(){

		if(jQuery("#action_dropdown").val() == ''){
			alert("You need to select an action first to proceed.");
		} else {
			if(jQuery("#action_dropdown").val() == '2'){

					var data = "";
					jQuery(".select_app:checked").each(function(){
						data = data + ";" + jQuery(this).val();
					});

					if(data == ""){
						alert("You must select at least one from the applicants list before proceeding!");
					} else {
						var loader = "<?php echo plugins_url('rb-agency-casting/view/loader.gif'); ?>";

						jQuery(this).nextAll("#re_bottom").html("<img src='"+loader+"'>");

						jQuery.ajax({
								type: "POST",
								url: "<?php echo admin_url('admin-ajax.php') ?>",
								dataType: 'json',
								data: {
									action: "client_add_casting",
									'talent_id' : data,
									'job_id': "none"
								},
								success: function (results) {
									if(results.data == "success"){
										window.location.href = window.location.pathname;
									}
								}
						});
					}
			} else {

				if(jQuery("#action_dropdown").val() == '1'){
					if(jQuery(".select_app").length > 0){
						var $href = "All";
					} else {
						var $href = "";
					}
				} else if(jQuery("#action_dropdown").val() == '0'){
					var $href = "";
					jQuery(".select_app:checked").each(function(){
						$href = $href + ";" + jQuery(this).val();
					});
				}
				if($href == ""){
					alert("You must select a recipient from applicant lists before proceeding!");
				} else {
					window.location.href = "<?php echo get_bloginfo('wpurl') ?>/email-applicant/" + $href;
				}

			}
		}
	});

	jQuery(".star").mouseover(function(){

		jQuery(this).css('background-position','0px 0px');
		jQuery(this).prevAll(".star").css('background-position','0px 0px');
		jQuery(this).nextAll(".star").css('background-position','0px -15px');
		var count = jQuery(this).prevAll(".star").length + 1;
		jQuery(this).parent().nextAll('.clients_rating').eq(0).val(count);

	});

	jQuery(".rate").click(function(){
		// TODO PATH INVALID
		var loader = "<?php echo plugins_url('rb-agency-casting/view/loader.gif'); ?>";
		var check = "<?php echo plugins_url('rb-agency-casting/view/check.png'); ?>";

		jQuery(this).nextAll(".loading").html("<img src='"+loader+"'>");

		var app_id = jQuery(this).prevAll(".application_id").eq(0).val();

		var rating = jQuery(this).prevAll(".clients_rating").eq(0).val();

		var loading = jQuery(this).nextAll(".loading");

		jQuery.ajax({
				type: "POST",
				url: "<?php echo admin_url('admin-ajax.php') ?>",
				data: {
					action: "rate_applicant",
					'application_id': app_id,
					'clients_rating': rating
				},
				success: function (results) {
					loading.html("<img src='"+check+"'>");
				}
		});

	});

	jQuery("body").on('click','.add_casting', function(){

		var loader = "<?php echo plugins_url('rb-agency-casting/view/loader.gif'); ?>";

		jQuery(this).html("<img src='"+loader+"'>");

		var $this = jQuery(this);

		var profile_id = jQuery(this).prevAll(".profile_id").eq(0).val();

		var job_id = jQuery(this).prevAll(".job_id").eq(0).val();
		console.log(job_id);
		//console.log(profile_id);
		jQuery.ajax({
				type: "POST",
				url: "<?php echo admin_url('admin-ajax.php') ?>",
				dataType: 'json',
				data: {
					action: "client_add_casting",
					'job_id' : job_id,
					'talent_id': profile_id
				},
				success: function (results) {
					console.log(results);
					if(results.data == ""){
							$this.html("Failed. Retry.");
						} else if(results.data == "inserted"){
							$this.html("Remove from Casting");
						} else if(results.data == "deleted"){
							$this.html("Add to CastingCart");
						}	

  				},error: function(err){
  					console.log(err);
  				}
		});

	});

		jQuery("body").on('click','.remove_from_job', function(){

		var loader = "<?php echo plugins_url('rb-agency-casting/view/loader.gif'); ?>";

		jQuery(this).html("<img src='"+loader+"'>");

		var $this = jQuery(this);

		var profile_id = jQuery(this).prevAll(".profile_id").eq(0).val();

		var job_id = jQuery(this).prevAll(".job_id").eq(0).val();
		var app_id = jQuery(this).prevAll(".application_id").eq(0).val();
		console.log(job_id);
		//console.log(profile_id);
		jQuery.ajax({
				type: "POST",
				url: "<?php echo admin_url('admin-ajax.php') ?>",
				dataType: 'json',
				data: {
					action: "remove_profile_from_job",
					'app_id' : app_id
				},
				success: function (results) {
					
					console.log(app_id);			

					jQuery('tr#'+app_id).remove();
  				},error: function(err){
  					console.log(err);
  				}
		});

	});

});
</script>

<?php
if (is_user_logged_in()) {
	
	
	$is_active = rb_check_casting_status();
	if($is_active == false and !current_user_can( 'edit_posts' )){
		echo '		
			<div id="rbcontent" role="main">
			
				<header class="entry-header">
				<h1 class="entry-title">'.__('You are not permitted to access this page.',RBAGENCY_casting_TEXTDOMAIN).'</h1>
				</header>
				<div class="entry-content">
				<p class="rbalert error">
					<strong></strong>
				</p>
			</div>';
		echo $rb_footer = RBAgency_Common::rb_footer(); 
		exit;
	}


	echo "<div id=\"rbcontent\">";

	
	
	// casting agents and admin only
	if(RBAgency_Casting::rb_casting_is_castingagent($current_user->ID) || current_user_can( 'edit_posts' )){
	
		$_filter = array();
		
	

		if ( current_user_can( 'edit_posts' ) ) {
			echo "<p><h3>".__("All Applicants to All Job Postings from Casting Agents",RBAGENCY_casting_TEXTDOMAIN)."</h3></p><br>";
		} else {
			echo "<p><h3>".__("Applicants to your Job Postings",RBAGENCY_casting_TEXTDOMAIN)."</h3></p><br>";
		}

		//setup filtering sessions
		if(isset($_GET['filter'])){

			$_SESSION['filter'] = "";
			$_SESSION['job_title'] = "";
			$_SESSION['applicant'] = "";
			$_SESSION['percentage'] = "";
			$_SESSION['perpage'] = "";
			$_SESSION['rating'] = "";
			
			$_jobtitle = $_GET['filter_jobtitle'];
			$_applicant = $_GET['filter_applicant'];
			$_percentage = $_GET['filter_jobpercentage'];
			$_rating = $_GET['filter_rating'];
			$_perpage = $_GET['filter_perpage'];
			
			
			// job title
			if(isset($_GET['filter_jobtitle']) && $_GET['filter_jobtitle'] != ""){
				$_SESSION['job_title'] = $_jobtitle;
				$_SESSION['filter'] = "jobs.Job_ID = " . $_jobtitle;
				$_filter['jobtitle'] = "jobs.Job_ID = '" . $_jobtitle ."'";
			}

			// applicant
			if(isset($_GET['filter_applicant']) && $_GET['filter_applicant'] != ""){
				$_SESSION['applicant'] = $_applicant;
				$AND = ($_SESSION['filter'] != "") ? " AND " : ""; 
				$_SESSION['filter'] .= $AND . "applicants.Job_UserLinked = " . $_applicant;
				$_filter['applicant'] = "applicants.Job_UserLinked = '" . $_applicant ."'";
			}

			// percentage
			if(isset($_GET['filter_jobpercentage']) && $_GET['filter_jobpercentage'] != ""){
				$_SESSION['percentage'] = $_percentage;
				$percent_arr = explode("-",$_percentage);
				$AND = ($_SESSION['filter'] != "") ? " AND " : ""; 
				$_SESSION['filter'] .= $AND . "Job_Criteria_Percentage >= " . $percent_arr[0] . " AND Job_Criteria_Percentage <= " . $percent_arr[1];
				$_filter['percentage'] = "Job_Criteria_Percentage >= " . $percent_arr[0] . " AND Job_Criteria_Percentage <= " . $percent_arr[1];
			}

			// perpage
			if(isset($_GET['filter_rating']) && $_GET['filter_rating'] != ""){
				$_SESSION['rating'] = $_rating;
				$AND = ($_SESSION['filter'] != "") ? " AND " : ""; 
				if($_rating == 'not_rated'){
					$_SESSION['filter'] .= $AND . "(Job_Client_Rating = '' OR Job_Client_Rating IS NULL)";
					$_filter['rating'] = "(Job_Client_Rating = '' OR Job_Client_Rating IS NULL)";
				} else {
					$_SESSION['filter'] .= $AND . "Job_Client_Rating = " . $_rating;
					$_filter['rating'] = "Job_Client_Rating = " . $_rating;
				}
			}

			// perpage
			if(isset($_GET['filter_perpage']) && $_GET['filter_perpage'] != ""){
				$_SESSION['job_perpage'] = $_perpage;
				//$_filter['job_perpage'] = $_perpage; no need to add to filter
			}

		}
		
		
		if(count($_filter) >= 1){
			$_filter_SQL = ' '. implode(' AND ', $_filter) .' ';
		}else{
			$_filter_SQL ='  ';
		}
		// set for display
		$applicant = (isset($_SESSION['applicant']) && $_SESSION['applicant'] != "") ? $_SESSION['applicant'] : "";
		$percentage = (isset($_SESSION['percentage']) && $_SESSION['percentage'] != "") ? $_SESSION['percentage'] : "";
		$jobtitle = (isset($_SESSION['job_title']) && $_SESSION['job_title'] != "") ? $_SESSION['job_title'] : "";
		$rating = (isset($_SESSION['rating']) && $_SESSION['rating'] != "") ? $_SESSION['rating'] : "";
		$perpage = (isset($_SESSION['job_perpage']) && $_SESSION['job_perpage'] != "") ? $_SESSION['job_perpage'] : 2;

		$applicant = $_applicant;
		$percentage = $_percentage;
		$jobtitle = $_jobtitle;
		$rating = $_rating;
		$perpage = is_numeric($_perpage) ? $_perpage : 10;

		
		//pagination setup
		$filter = "";
		$start = get_query_var('target');
		$record_per_page = $perpage;
		$link = get_bloginfo('wpurl') . "/view-applicants/";
		$table_name = table_agency_casting_job_application;

		//for admin view
		if ( current_user_can( 'edit_posts' ) ) {
			if(count($_filter) >= 1){
				$filter = " WHERE " . $_SESSION['filter']; 
				$filter = " WHERE " . $_filter_SQL ;
			}
			
			
			$where = " applicants LEFT JOIN " . table_agency_casting_job . 
					" jobs ON jobs.Job_ID = applicants.Job_ID
					LEFT JOIN  " . table_agency_profile . " profile  ON profile.ProfileUserLinked = applicants.Job_UserLinked
					" . $filter ;
					
					
			$where_wo_filter = " applicants LEFT JOIN " . table_agency_casting_job . 
								" jobs ON jobs.Job_ID = applicants.Job_ID";
		} else {
			//if(isset($_GET['filter']) && $_GET['filter'] != ""){
			if(count($_filter) >= 1){
				$filter = " AND " . $_SESSION['filter']; 
				$filter =  ' AND '.$_filter_SQL;
			}
			
			$where = " applicants LEFT JOIN " . table_agency_casting_job . 
					" jobs ON jobs.Job_ID = applicants.Job_ID 
					LEFT JOIN  " . table_agency_profile . " profile  ON profile.ProfileUserLinked = applicants.Job_UserLinked
						WHERE jobs.Job_UserLinked = " . $current_user->ID . $filter;
			
			$where_wo_filter = " applicants LEFT JOIN " . table_agency_casting_job . 
								" jobs ON jobs.Job_ID = applicants.Job_ID 
									WHERE jobs.Job_UserLinked = " . $current_user->ID;
									
			$where_wo_filter = " jobs WHERE jobs.Job_UserLinked = " . $current_user->ID . $filter;				
			

		}
		
		//clean applicants- delete the applicant that doesnt exist in profiles.
		$x_delete = $wpdb->get_results("DELETE FROM " . table_agency_casting_job_application . " WHERE Job_UserLinked NOT IN 
            (SELECT ProfileUserLinked FROM " . table_agency_profile ." WHERE ProfileUserLinked is NOT NULL)");
	
		

		$selected_page = get_query_var('target');

		if($start != ""){
			$limit1 = ($start * $record_per_page) - $record_per_page;
		} else {
			$limit1 = 0;
		}

		// this query is going to used by email all visible
		$_SESSION['Current_User_Query'] = "SELECT applicants.Job_UserLinked FROM " 
											. table_agency_casting_job_application . $where
											. " GROUP By applicants.Job_ID ORDER By applicants.Job_Criteria_Passed DESC 
											LIMIT " . $limit1 . "," . $record_per_page ;

		// setup filter display
		echo "<form id=\"job-applicants-filter\" method='GET' action='".get_bloginfo('wpurl')."/view-applicants/'>";
		echo "<table class='table-filter'>\n";
		echo "<tbody>";
		echo "    <tr class=\"thead\">\n";
		echo "        <td>".__("Job Title",RBAGENCY_casting_TEXTDOMAIN)."<br>
						<select name='filter_jobtitle' style='width: 100%;'>
							<option value=''>".__("-- Select Job Title --",RBAGENCY_casting_TEXTDOMAIN)."</option>";

		$job_applicant = array();

		if(current_user_can("edit_posts")){
			$load_job_filter = $wpdb->get_results("SELECT * FROM ".table_agency_casting_job." ORDER BY Job_Title");
			if(count($load_job_filter) > 0){
				foreach($load_job_filter as $j){
					echo "<option value='".$j->Job_ID."' ".selected($jobtitle,$j->Job_ID,false).">".$j->Job_Title."</option>";
				}
			}
		} else {
			//load jobs by current user
			
			$load_job_filter = $wpdb->get_results("SELECT * FROM " . table_agency_casting_job_application .
							  " applicants LEFT JOIN ". table_agency_casting_job ." jobs ON applicants.Job_ID=jobs.Job_ID
							  WHERE jobs.Job_UserLinked = " . $current_user->ID . " GROUP By applicants.Job_ID ORDER By applicants.Job_Criteria_Passed DESC");
							 // WHERE jobs.Job_UserLinked = " . $current_user->ID . $filter . " GROUP By applicants.Job_ID ORDER By applicants.Job_Criteria_Passed DESC");
			 
			if(count($load_job_filter) > 0){
				foreach($load_job_filter as $j){
					echo "<option value='".$j->Job_ID."' ".selected($jobtitle,$j->Job_ID,false).">".$j->Job_Title."</option>";
				}
			}
		}
	
		echo "			</select>
						</td>\n";
		echo "        <td>".__("Applicant",RBAGENCY_casting_TEXTDOMAIN)."<br>
						<select name='filter_applicant'>
							<option value=''>".__("-- Select Applicant --",RBAGENCY_casting_TEXTDOMAIN)."</option>";
							
				
			if(current_user_can("edit_posts")){	
				/* $job_applicant = $wpdb->get_results("SELECT *, applicants.Job_UserLinked as app_id FROM " . table_agency_casting_job_application .
							  " applicants LEFT JOIN ". table_agency_casting_job ." jobs ON applicants.Job_ID=jobs.Job_ID
							  WHERE jobs.Job_UserLinked = " . $current_user->ID . " GROUP By applicants.Job_ID ORDER By applicants.Job_Criteria_Passed DESC");
							 $filter
							 
				$job_applicant[$j->app_id] = RBAgency_Casting::rb_casting_ismodel($j->app_id, "ProfileContactDisplay",true);   
				foreach($job_applicant as $key => $val){
					echo "<option value='".$key."' ".selected($key, $applicant,false).">".$val."</option>";
		 
				} */
				
				$job_applicant = $wpdb->get_results("SELECT *, applicants.Job_UserLinked as app_id FROM " . table_agency_casting_job_application .
					" applicants LEFT JOIN ". table_agency_casting_job ." jobs ON applicants.Job_ID=jobs.Job_ID
					" . $filter . " GROUP By applicants.Job_UserLinked ORDER By applicants.Job_Criteria_Passed DESC");
			
				if(count($job_applicant) > 0){
					
					
					foreach($job_applicant as $key => $val){
						$_applicantName = RBAgency_Casting::rb_casting_ismodel($val->app_id, "ProfileContactDisplay",true);
						if(empty($_applicantName)){
							$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "CastingContactDisplay");
							$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "ProfileContactDisplay");
							//no CastingContactDisplay set.. get the first name
							if($_applicantName){
								$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "CastingContactNameFirst");
							}
							
						}
						echo "<option value='".$val->app_id."' ".selected($val->app_id, $applicant,false).">".$_applicantName."</option>";
			 
					}
				}
				
			}else{
				$job_applicant = $wpdb->get_results("SELECT *, applicants.Job_UserLinked as app_id FROM " . table_agency_casting_job_application .
					" applicants LEFT JOIN ". table_agency_casting_job ." jobs ON applicants.Job_ID=jobs.Job_ID
					WHERE jobs.Job_UserLinked = " . $current_user->ID . $filter . " GROUP By applicants.Job_UserLinked ORDER By applicants.Job_Criteria_Passed DESC");
			
				if(count($job_applicant) > 0){
					
					
					foreach($job_applicant as $key => $val){
						$_applicantName = RBAgency_Casting::rb_casting_ismodel($val->app_id, "ProfileContactDisplay",true);
						if(empty($_applicantName)){
							$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "CastingContactDisplay");
							$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "ProfileContactDisplay");
							//no CastingContactDisplay set.. get the first name
							if($_applicantName){
								$_applicantName = RBAgency_Casting::rb_casting_is_castingagent($val->app_id, "CastingContactNameFirst");
							}
							
						}
						echo "<option value='".$val->app_id."' ".selected($val->app_id, $applicant,false).">".$_applicantName."</option>";
			 
					}
				}
			}

		echo "			</select>
		
		
					

		
						</td>\n";
		echo "        <td>".__("Criteria Matched",RBAGENCY_casting_TEXTDOMAIN)."<br>
						<select name='filter_jobpercentage'>
							<option value=''>".__("-- Select Matched % --",RBAGENCY_casting_TEXTDOMAIN)."</option>
							<option value='75-100' ".selected($percentage,'75-100',false).">".__("75% - 100% Matched",RBAGENCY_casting_TEXTDOMAIN)."</option>
							<option value='50-75' ".selected($percentage,'50-75',false).">".__("50% - 75% Matched",RBAGENCY_casting_TEXTDOMAIN)."</option>
							<option value='25-50' ".selected($percentage,'25-50',false).">".__("25% - 50% Matched",RBAGENCY_casting_TEXTDOMAIN)."</option>
							<option value='0-25' ".selected($percentage,'0-25',false).">".__("0% - 25% Matched",RBAGENCY_casting_TEXTDOMAIN)."</option>
						</select>
						</td>\n";

		echo "        <td>".__("Your Rating",RBAGENCY_casting_TEXTDOMAIN)."<br>
						<select name='filter_rating'>
							<option value=''> - </option>";
							echo "<option value='not_rated' ".selected('not_rated', $rating,false).">".__("No Rating",RBAGENCY_casting_TEXTDOMAIN)."</option>";
							$page = 1;
							for($page = 1; $page <= 5; $page ++){
								echo "<option value='$page' ".selected($page, $rating,false).">$page Star</option>";
							}

		echo "			</select>
						</td>\n";

		echo "        <td>".__("Records Per Page",RBAGENCY_casting_TEXTDOMAIN)."<br>
						<select name='filter_perpage'>
							<option value=''>".__("- # of Rec -",RBAGENCY_casting_TEXTDOMAIN)."</option>";
							echo "<option value='2' ".selected(2, $perpage,false).">2</option>";

		$page = 0;
		for($page = 5; $page <= 50; $page += 5){
			echo "<option value='$page' ".selected($page, $perpage,false).">$page</option>";
		}

		echo "			</select>
						</td>\n";

		echo "        <td><input type='submit' name='filter' class='button-primary' value='filter'></td>\n";
		echo "    </tr>\n";
		echo "</tbody>";
		echo "</table>";
		echo "</form>";

		echo "<table cellspacing=\"0\" id=\"job-applicants\" class='rbtable'>\n";
		echo " <thead>\n";
		echo "    <tr class=\"thead\">\n";
		echo "        <th class=\"column-JobID\" id=\"JobID\" scope=\"col\"><input type='checkbox' id='sel_all'> ".__("Select",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "        <th class=\"column-JobTitle\" id=\"JobTitle\" scope=\"col\" style=\"width:100px;\">".__("Job Title / ID",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "        <th class=\"column-JobApplicant\" id=\"JobApplicant\" scope=\"col\">".__("Applicant",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "        <th class=\"column-JobCriteriaPassed\" id=\"CriteriaPassed\" scope=\"col\">".__("Criteria Passed",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "        <th class=\"column-JobApplicationLetter\" id=\"JobApplicationLetter\" scope=\"col\">".__("Application Letter",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "        <th class=\"column-JobAction\" id=\"JobAction\" scope=\"col\">".__("Action",RBAGENCY_casting_TEXTDOMAIN)."</th>\n";
		echo "    </tr>\n";
		echo " </thead>\n";

		// load all job postings
		//for admin view
		
		$load_data = $wpdb->get_results("SELECT *,applicants.Job_UserLinked as app_UserLinked,profile.ProfileID as Job_UserProfileID FROM " . table_agency_casting_job_application .
											$where
											. " ORDER By applicants.Job_Criteria_Passed DESC 
											LIMIT " . $limit1 . "," . $record_per_page );
//print_r($load_data);


		if(count($load_data) > 0){
			foreach($load_data as $load){
				
				$details = RBAgency_Casting::rb_casting_get_model_details($load->Job_UserProfileID);
				if($details->ProfileGallery != ""){
					$display = '<a href="'.get_bloginfo('wpurl').'/profile/'.$details->ProfileGallery.'">'.$details->ProfileContactDisplay.'</a>';
				} else {
					$display = $details->ProfileContactNameFirst;
				}
				echo "    <tr class=\"app_id\" id=\"".$load->Job_Application_ID."\">\n";
				echo "        <td class=\"column-JobID\" scope=\"col\" style=\"width:50px;\"><input type='checkbox' name='select' class='select_app' value='".$load->Job_ID.":".$load->Job_UserLinked."'></td>\n";
				echo "        <td class=\"column-JobTitle\" scope=\"col\" style=\"width:150px;\">".$load->Job_Title."<br><span class=\"id\">Job ID# : ".$load->Job_ID."</span></td>\n";
				echo "        <td class=\"column-JobDate\" scope=\"col\">";
				
				// applicant image
				
				$image = RBAgency_Casting::rb_get_model_image($load->Job_UserProfileID);
				if($image!= ""){
					echo "<div class=\"photo\" style ='clear:both;margin:0;padding:0'>";
					//echo "<span style = 'height: 120px; line-height:120px; width: 120px; display: table-cell; vertical-align: middle; text-align: center; overflow: hidden;'>";
					echo "<a href=\"".get_bloginfo('wpurl')."/profile/".$details->ProfileGallery."\">";
						$image = get_bloginfo("url")."/wp-content/plugins/rb-agency/ext/timthumb.php?src=".$image ."&h=100&w=100&zc=2\" />";

					echo "<img src='".$image."'>";
					echo "</a>";
					//echo "</span>";
					echo "</div>";
				} else {
					echo "<div class=\"no-image photo\" style ='clear:both'>";
					echo "No Image";
					echo "</div>";
				}

				echo $display."<br/><br/></td>\n";

				if(RBAgency_Casting::rb_get_job_visibility($load->Job_ID) == 1){
					echo "        <td class=\"column-JobLocation\" scope=\"col\">".__("100% Matched <br> <hr style='margin:5px'> Open to All<br>",RBAGENCY_casting_TEXTDOMAIN);
				} else {
					echo "        <td class=\"column-JobLocation\" scope=\"col\">".$load->Job_Criteria_Passed . RBAgency_Casting::rb_casting_get_percentage_passed($load->Job_ID, $load->Job_Criteria_Passed) . "<br>";
				}

				$load_detials = unserialize($load->Job_Criteria_Details);

				if(!empty($load_detials)){
					echo "<hr style='margin:5px'>";
					foreach($load_detials as $key => $val){
						$get_title = "SELECT ProfileCustomTitle FROM " . table_agency_customfields . " WHERE ProfileCustomID = " . $key;
						$get_row = $wpdb->get_row($get_title);
						if(count($get_row) > 0){
							echo "<span style='font-size:11px; font-weight:bold'>" . $get_row->ProfileCustomTitle . ": </span><br>";
							echo "<span style='font-size:11px'>" . $val . "</span><br>";
						} else {
							echo "<span style='font-size:11px'>" . $val . "</span><br>";
						}
					}
				}

				echo "</td>\n";

				echo "        <td class=\"column-JobApplicationLetter\" scope=\"col\">".$load->Job_Pitch ."</td>";

				echo "        <td class=\"column-JobAction\" scope=\"col\">";
				if(current_user_can("edit_posts")){
					echo "<a href='".admin_url("admin.php?page=rb_agency_castingjobs&action=informTalent&Job_ID=".$load->Job_ID)."' style=\"font-size:12px;\">".__("Edit Job Details",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				} else {
					echo "<a href='".get_bloginfo('wpurl')."/casting-editjob/".$load->Job_ID."' style=\"font-size:12px;\">".__("Edit Job Details",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				}
				echo "        <input type='hidden' class='job_id' value='".$load->Job_ID."'>";
				echo "        <input type='hidden' class='profile_id' value='".$load->Job_UserProfileID."'>";
				echo "        <input type='hidden' class='application_id' value='".$load->Job_Application_ID."'>";
				if($rb_agency_option_allowsendemail == 1){
					echo "        <a href='".get_bloginfo('wpurl')."/email-applicant/".$load->Job_ID."/".$load->app_UserLinked."' style=\"font-size:12px;\">".__("Send Email",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				}
				if(RBAgency_Casting::rb_check_in_cart($load->Job_UserProfileID,$load->Job_ID)){
					echo "        <a class = 'add_casting' href='javascript:;' style=\"font-size:12px;\">".__("Remove from Casting",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				} else {
					echo "        <a class = 'add_casting' href='javascript:;' style=\"font-size:12px;\">".__("Add to CastingCart",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				}
				echo "<a class = 'remove_from_job' href='javascript:;' style=\"font-size:12px;\" id=\"".$load->Job_Application_ID."\">".__("Remove from Job",RBAGENCY_casting_TEXTDOMAIN)."</a><br>";
				echo "<a href=\"".get_bloginfo("url")."/profile-casting/\" style=\"font-size:12px;\">".__("View Casting Cart",RBAGENCY_casting_TEXTDOMAIN)."</a>";
			      echo "        <p  style='clear:both; margin-top:12px'>Rate Applicant</p>";

				$link_bg = plugins_url('rb-agency-casting/view/sprite.png');

				echo "        <div style='clear:both; margin-top:5px'>";
				echo "					<div class='star' style='float:left; width:15px; height:15px; background:url(\"$link_bg\") ".(isset($load->Job_Client_Rating) && $load->Job_Client_Rating >= 1 ? "0px 0px;" : '0px -15px;' ) ."'></div>";
				echo "					<div class='star' style='float:left; width:15px; height:15px; background:url(\"$link_bg\") ".(isset($load->Job_Client_Rating) && $load->Job_Client_Rating >= 2 ? "0px 0px;" : '0px -15px;' ) ."'></div>";
				echo "					<div class='star' style='float:left; width:15px; height:15px; background:url(\"$link_bg\") ".(isset($load->Job_Client_Rating) && $load->Job_Client_Rating >= 3 ? "0px 0px;" : '0px -15px;' ) ."'></div>";
				echo "					<div class='star' style='float:left; width:15px; height:15px; background:url(\"$link_bg\") ".(isset($load->Job_Client_Rating) && $load->Job_Client_Rating >= 4 ? "0px 0px;" : '0px -15px;' ) ."'></div>";
				echo "					<div class='star' style='float:left; width:15px; height:15px; background:url(\"$link_bg\") ".(isset($load->Job_Client_Rating) && $load->Job_Client_Rating == 5 ? "0px 0px;" : '0px -15px;' ) ."'></div>";
				echo "        </div>
								<input type='hidden' class='application_id' value='".$load->Job_Application_ID."'>
								<input type='hidden' class='clients_rating' value='".(isset($load->Job_Client_Rating) ?$load->Job_Client_Rating:"")."'>
								<input type='button' class='rate' value='Rate' style='clear:both; float:left'> <div class='loading' style='float:right; margin-right:15px; margin-top:5px; width:20px; height:20px'></div>
							</td>\n";
				echo "    </tr>\n";
			}
			echo "</table>";

		} else {

			echo "<tr><td colspan='6'><p>".__("You have no Applicants.<br>If you don't have any job postings, create a new job posting",RBAGENCY_casting_TEXTDOMAIN)." <a href='".get_bloginfo('wpurl')."/casting-postjob'>".__("Here.",RBAGENCY_casting_TEXTDOMAIN)."</a></p></td></tr>";
			echo "</table>";

		}

		echo "<footer>";

		// actual pagination
		RBAgency_Casting::rb_casting_paginate($link, $table_name, $where, $record_per_page, $selected_page);
		
		echo "<div class=\"jobposting-actions\">
				<select id='action_dropdown'>
					<option value=''>".__("-- Select Action --",RBAGENCY_casting_TEXTDOMAIN)."</option>
					<option value='2'>".__("Add/Remove to Casting Cart",RBAGENCY_casting_TEXTDOMAIN)."</option>";
				if($rb_agency_option_agencyemail == 1){
						echo "<option value='0'>".__("Send Email to Selected",RBAGENCY_casting_TEXTDOMAIN)."</option>";
						echo "<option value='1'>".__("Send Email to All Visible",RBAGENCY_casting_TEXTDOMAIN)."</option>";
				}

		echo "
				</select>
				<input type='button' id='action_submit' class='button-primary' value='".__("Submit",RBAGENCY_casting_TEXTDOMAIN)."'>
				<div id='re_bottom' style='margin-left:12px; float:left; width:20px; height:20px'></div>
				</div>\n";
				
		echo "<div class='footer-links'>";

		if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'browse-jobs') > -1){
			echo "<a href='".get_bloginfo('wpurl')."/browse-jobs' class=\"pure-button\">".__("Go Back to Job Postings",RBAGENCY_casting_TEXTDOMAIN)."</a> | \n";
		}
		echo "<a href='".get_bloginfo('wpurl')."/profile-casting' class=\"pure-button\">".__("View Your Casting Cart")."</a> <a href='".get_bloginfo('wpurl')."/casting-dashboard' class=\"pure-button\">".__("Go Back to Casting Dashboard",RBAGENCY_casting_TEXTDOMAIN)."</a>\n";
		echo "</div><!-- .footer-links -->";

	} else {
		echo "<p class=\"rbalert info\">".__("Only Casting Agents are permitted on this page.<br>You need to be registered",RBAGENCY_casting_TEXTDOMAIN)." <a href='".get_bloginfo('wpurl')."/casting-register'>".__("here.",RBAGENCY_casting_TEXTDOMAIN)."</a></p><br>";
	}

	echo "</div> <!-- #rbcontent -->";
} else {
	include ("include-login.php");
}

//get_sidebar(); 
echo $rb_footer = RBAgency_Common::rb_footer(); 

?>