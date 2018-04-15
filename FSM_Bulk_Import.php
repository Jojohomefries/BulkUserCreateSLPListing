<?php

register_activation_hook(__FILE__,'FSM_Bulk_Upload_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'FSM_Bulk_Upload_remove' );

//Bulk Script


function FSM_Bulk_Upload_install() {

}
function FSM_Bulk_Upload_remove() {

}
function fsmbulk_enqueue_script() {   
     //wp_enqueue_script( 'jsimport', plugin_dir_url( __FILE__ ) . 'jsimport.js', array('jquery'), '1.0' );
}
add_action('admin_enqueue_scripts', 'fsmbulk_enqueue_script');


if ( is_admin() ){

/* Call the html code */
add_action('admin_menu', 'FSMBulk_Upload_admin_menu');

    function FSMBulk_Upload_admin_menu() {
            add_menu_page('Bulk Import Practitioners', 'Bulk Import Practitioners', 'manage_options',
            'FSM_Bulk_Upload', 'FSM_Bulk_Upload_html_page');
        }


    function FSM_Bulk_Upload_html_page()
    {

    	$terms = get_terms( 'stores', array(
		    'hide_empty' => false,
		) );
		$termnames = array();
		foreach ($terms as $key => $value) {
			array_push($termnames, $value->name);
		}
		//print_r($termnames);


    	?>
    	<p>Please import your CSV file here.  Please use <a href="/wp-content/plugins/FSM_Bulk_Import/format.csv">this format</a>, or the file will be rejected.<p>
    	<input type="file" id="csvFileInput" onchange="handleFiles(this.files)"
            accept=".csv">
    	<div id="results"></div>

		<script type="text/javascript">

			var cat_array =<?php echo json_encode($termnames );?>;
			console.log(cat_array);

			 function handleFiles(files) {
		      // Check for the various File API support.
		      if (window.FileReader) {
		          // FileReader are supported.
		          getAsText(files[0]);
		      } else {
		          alert('FileReader is not supported in this browser.');
		      }
		    }

		    function getAsText(fileToRead) {
		      var reader = new FileReader();
		      // Read file into memory as UTF-8      
		      reader.readAsText(fileToRead);
		      // Handle errors load
		      reader.onload = loadHandler;
		      reader.onerror = errorHandler;
		    }

		    function loadHandler(event) {
		      var csv = event.target.result;
		      processData(csv);
		    }

		    function processData(csv) {
		    	jQuery("#results").html("");
		        var allTextLines = csv.split(/\r\n|\n/);
		        var lines = [];
		        for (var i=0; i<allTextLines.length; i++) {
		            var data = allTextLines[i].split(',');
		                var tarr = [];
		                for (var j=0; j<data.length; j++) {
		                    tarr.push(data[j]);
		                }
		                lines.push(tarr);
		        }
		       //lines is the CSV in array form
		      console.log(lines);


		      //First Name, Last Name, Credentials, Company, Address, Address 2, City, State, Zip, Country, Email, Phone
		      var headers = ['First Name', 'Last Name', 'Credentials', 'Company', 'Address', 'Address 2', 'City', 'State', 'Zip', 'Country', 'Email', 'Phone','Categories','Tags'];
		      if (arraysEqual(headers,lines[0])) {
		      	jQuery('#results').append('Headers match, continuing.<br/><br/>');
		      } else {
		      	jQuery('#results').append('Headers do not match: quitting.');
		      	return false;
		      }



		      
			    for (var i=1; i<lines.length-1; i++) {

			    	if (lines[i][10] != '') { 
			    	//if email is not blank

			    		
					      if (lines[i][12] != '') {
					      	//check categories, if categories are not in the system, skip
					      	var catList = '';
					      	var arrcat = lines[i][12].split(';');
					      	var inArr = true;
					      	for (var j = 0, len = arrcat.length; j < len; j++) {
							  //arrcat[i]
							  if (!contains(cat_array,arrcat[j])) {
							  	inArr = false;
							  	catList += catList + arrcat[j]+', ';
							  }
							}

							  if (!inArr) {
							  	jQuery('#results').append('Record '+i+' was skipped: '+catList+' not in system<br/>');
		            			lines[i][0]='SKIPME';
							  } else {
					      		jQuery('#results').append('Record '+i+' is OK.<br/>');
					      	  }

							
					      } else { 
					      	//no categories on this record
					      	jQuery('#results').append('Record '+i+' is OK.<br/>');
					      }

		            } else {
		            	//email is blank
		            	jQuery('#results').append('Record '+i+' was skipped: email cannot be empty<br/>');
		            	lines[i][0]='SKIPME';
		            }
			    
			    }

			    console.log(lines);
			  
			  		            //now recurse the ajax function
		            jQuery('#results').append('<br/><br/>Beginning import:<br/>');
		            importData(lines,1);




		    }

		    function importData(array,i) {
		    	var ind = i;
		    var dataObject = { 
				      		firstName: 		array[ind][0],
		                   	lastName:  		array[ind][1],
		                   	credentials: 	array[ind][2],
		                   	company: 		array[ind][3],
		                   	address: 		array[ind][4],
		                   	address2: 		array[ind][5],
		                   	city: 			array[ind][6],
		                   	state: 			array[ind][7],
		                   	zip: 			array[ind][8],
		                   	country: 		array[ind][9],
		                   	email: 			array[ind][10],
		                   	phone: 			array[ind][11],
		                   	categories: 	array[ind][12],
		                   	tags: 			array[ind][13],

		                };
		                console.log('importing: '+ind);
		                console.log(dataObject);


		                //send the object to ajax function
		                //post success message to #results
		                jQuery.ajax({
					        url: '/wp-admin/admin-ajax.php',
					        type: 'POST',
					        data: {
						        "data": JSON.stringify(dataObject),
						        "action": "upload_bulk"
						    },
					        success: function(dataR)
					        {
					        	if (dataR != 'NODATA') {
					        		jQuery('#results').append("Item "+ind+": "+dataR+"<br/>");
					        	}
					        	
					        	if (ind<array.length-1) {
					        		importData(array,ind+1);
					        	} else {
					        		jQuery('#results').append('<br/>Bulk Import completed!');
					        	}
					        }
					    });
		            }

		    function errorHandler(evt) {
		      if(evt.target.error.name == "NotReadableError") {
		          alert("Cannot read file !");
		      }
		    }

		    function arraysEqual(arr1, arr2) {
			    if(arr1.length !== arr2.length)
			        return false;
			    for(var i = arr1.length; i--;) {
			        if(arr1[i] !== arr2[i])
			            return false;
			    }

			    return true;
			}
			function contains(arr, element) {
			    for (var i = 0; i < arr.length; i++) {
			        if (arr[i] === element) {
			            return true;
			        }
			    }
			    return false;
			}

		</script>


    	<?php

    }
}//end if is admin


add_action('wp_ajax_nopriv_upload_bulk', 'upload_bulk_cb');
add_action('wp_ajax_upload_bulk', 'upload_bulk_cb');
function upload_bulk_cb() {

	global $wpdb;
	$data = json_decode(stripslashes($_POST['data']));
	

	if (empty($data->email) && empty($data->firstName)) {
		echo 'NODATA';
	} else if ($data->firstName == 'SKIPME') {
		echo $data->email.' SKIPPED';
	} else {


	//create user
		//echo 'testing: '.email_exists( $data->email );
		//wp_die();
  
		if( null == email_exists( $data->email ) ) {

    //create listing
			$password = wp_generate_password( 12, true );
			$user_id = wp_create_user ( $data->email, $password, $data->email );
			$user=get_user_by( 'id', $user_id );
			$user->add_role( 'practitioner' );


			$tgs = explode(";", $data->tags);

		  $sl_store = $data->firstName.' '.$data->lastName.' '.$data->credentials;
          $sl_description=$data->company;
          $sl_address=$data->address;
          $sl_address2=$data->address2;
          $sl_city=$data->city;
          $sl_state=$data->state;
          $sl_zip=$data->zip;
          $sl_country=$data->country;
          $sl_email=$data->email;
          $sl_phone=$data->phone;
           $sl_categories=explode(";", $data->categories);
           $sl_tags=implode(",", $tgs);

           //echo print_r($data);
           //wp_die();
          
          //get lat/lng from google
          $geocodeURL="https://maps.googleapis.com/maps/api/geocode/json??language=en&address=";
          $fullURL = $geocodeURL . urlencode("$sl_address $sl_city $sl_state  $sl_zip $sl_country");
           $response = wp_remote_get( $fullURL);
        $raw_json = is_wp_error( $response ) ? null : $response['body'];
        $json = json_decode($raw_json);
        $lat=$json->results[0]->geometry->location->lat;
        $lng=$json->results[0]->geometry->location->lng;

 		$importArr =  array( 
                'sl_store' => $sl_store, 
                'sl_address' => $sl_address,
                'sl_address2' => $sl_address2,
                'sl_city' =>  $sl_city,
                'sl_state' => $sl_state,
                'sl_country' => $sl_country,
                'sl_zip' => $sl_zip,
                'sl_description' => $sl_description,
                'sl_phone' => $sl_phone,
                'sl_email' => $sl_email,
                'sl_url' => "",
                'sl_latitude'=>$lat,
                'sl_longitude'=>$lng,
                'sl_private'=>'0',
                'sl_tags'=>$sl_tags,
                
                );

 		

            $wpdb->insert( $wpdb->prefix.'store_locator', $importArr );
                
           //   echo print_r($importArr);
          // wp_die();  
               
     $practitioner_id=$wpdb->insert_id;
     //update usermeta
     update_user_meta( $user_id, 'practitioner_id', $practitioner_id );
     

     foreach ($sl_categories as $catName) {
     	$category = get_term_by('name', $catName, 'stores');


           $wpdb->insert( 
            $wpdb->prefix.'slp_tagalong', 
            array( 
                'sl_id' => $practitioner_id, 
                'term_id' => $category->term_id,
                
            ));
     }
  

	//return OK message
     echo $data->email.' IMPORTED';

	} else {
		    //email already in system

            $user = get_user_by( 'email', $data->email );

		//get prac id for listing
		$practitioner_id = get_user_meta( $user->ID, 'practitioner_id', true );

		if (!empty($practitioner_id)) {

            $sl_store = $data->firstName.' '.$data->lastName.' '.$data->credentials;
            $sl_description=$data->company;
            $sl_address=$data->address;
            $sl_address2=$data->address2;
            $sl_city=$data->city;
            $sl_state=$data->state;
            $sl_zip=$data->zip;
            $sl_country=$data->country;
            $sl_email=$data->email;
            $sl_phone=$data->phone;
            $sl_categories=explode(";", $data->categories);
            $sl_tags=implode(",", $tgs);

            //echo print_r($data);
            //wp_die();

            //get lat/lng from google
            $geocodeURL="https://maps.googleapis.com/maps/api/geocode/json??language=en&address=";
            $fullURL = $geocodeURL . urlencode("$sl_address $sl_city $sl_state  $sl_zip $sl_country");
            $response = wp_remote_get( $fullURL);
            $raw_json = is_wp_error( $response ) ? null : $response['body'];
            $json = json_decode($raw_json);
            $lat=$json->results[0]->geometry->location->lat;
            $lng=$json->results[0]->geometry->location->lng;


            $wpdb->query("UPDATE ".$wpdb->prefix."store_locator 
		        SET 
		        sl_address='$sl_address',
		        sl_description='$sl_description',
		        sl_address2='$sl_address2',
		        sl_city='$sl_city',
		        sl_state='$sl_state',
		        sl_zip='$sl_zip',
		        sl_country='$sl_country',
		        sl_email='$sl_email',
		        sl_phone='$sl_phone',
		        sl_url='',
		        sl_latitude='$lat',
		        sl_longitude='$lng',
		        sl_private='0',
		        sl_tags=>$sl_tags
		        
		        WHERE sl_id='$practitioner_id'");

            //update categories for this post
            $wpdb->delete( $wpdb->prefix.'slp_tagalong', [ 'sl_id' => $practitioner_id ] );

            foreach ($sl_categories as $catName) {
                $category = get_term_by('name', $catName, 'stores');

                $wpdb->insert(
                    $wpdb->prefix.'slp_tagalong',
                    array(
                        'sl_id' => $practitioner_id,
                        'term_id' => $category->term_id,

                    ));
            }//end foreach

            echo $data->email.' ALREADY IN SYSTEM - UPDATED LISTING';
        } else {

            //no prac ID, create manually

            $user->add_role( 'practitioner' );


            $tgs = explode(";", $data->tags);

            $sl_store = $data->firstName.' '.$data->lastName.' '.$data->credentials;
            $sl_description=$data->company;
            $sl_address=$data->address;
            $sl_address2=$data->address2;
            $sl_city=$data->city;
            $sl_state=$data->state;
            $sl_zip=$data->zip;
            $sl_country=$data->country;
            $sl_email=$data->email;
            $sl_phone=$data->phone;
            $sl_categories=explode(";", $data->categories);
            $sl_tags=implode(",", $tgs);

   
            //get lat/lng from google
            $geocodeURL="https://maps.googleapis.com/maps/api/geocode/json??language=en&address=";
            $fullURL = $geocodeURL . urlencode("$sl_address $sl_city $sl_state  $sl_zip $sl_country");
            $response = wp_remote_get( $fullURL);
            $raw_json = is_wp_error( $response ) ? null : $response['body'];
            $json = json_decode($raw_json);
            $lat=$json->results[0]->geometry->location->lat;
            $lng=$json->results[0]->geometry->location->lng;

            $importArr =  array(
                'sl_store' => $sl_store,
                'sl_address' => $sl_address,
                'sl_address2' => $sl_address2,
                'sl_city' =>  $sl_city,
                'sl_state' => $sl_state,
                'sl_country' => $sl_country,
                'sl_zip' => $sl_zip,
                'sl_description' => $sl_description,
                'sl_phone' => $sl_phone,
                'sl_email' => $sl_email,
                'sl_url' => "",
                'sl_latitude'=>$lat,
                'sl_longitude'=>$lng,
                'sl_private'=>'0',
                'sl_tags'=>$sl_tags,

            );



            $wpdb->insert( $wpdb->prefix.'store_locator', $importArr );


            $practitioner_id=$wpdb->insert_id;
            //update usermeta
            update_user_meta( $user->ID, 'practitioner_id', $practitioner_id );



            foreach ($sl_categories as $catName) {
                $category = get_term_by('name', $catName, 'stores');


                $wpdb->insert(
                    $wpdb->prefix.'slp_tagalong',
                    array(
                        'sl_id' => $practitioner_id,
                        'term_id' => $category->term_id,

                    ));
            }

    


            echo $data->email.' ALREADY IN SYSTEM - NO LISTING FOUND, CREATED LISTING';
        }//end if prac id



	}//end if email in system
  


	}//end if validation check
	wp_die();

}

?>