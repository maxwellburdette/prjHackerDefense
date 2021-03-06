<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8">
<!-- registration2.php - Register new racers - edit, delete 
  Max Burdette
  04/14/2021 
  -->
 <title>SunRun Registration</title>
<link rel="stylesheet" type="text/css" href="registration.css">

<?PHP
   // Set up connection constants
   // Using default username and password for AMPPS  
   define("SERVER_NAME","localhost");
   define("DBF_USER_NAME", "root");
   define("DBF_PASSWORD", "mysql");
   define("DATABASE_NAME", "sunRun");
   // Global connection object
   $conn = NULL;

   //Link to external library
   require_once(getcwd( ) . "/sunRunLib.php");   
   // Connect to database
   createConnection();
    // Is this a return visit?
    if(array_key_exists('hidIsReturning',$_POST)) {
        //echo "<hr /><strong>\$_POST: </strong>";
        //print_r($_POST);

        // Did the user select a runner from the list?
        // 'new' is the value of the first item on the runner list box 
        if(isset($_POST['lstRunner']) && !($_POST['lstRunner'] == 'new'))
        {
            // Extract runner and sponsor information
            $sql = "SELECT runner.id_runner, fName, lName, phone, gender, sponsorName 
                FROM runner 
                LEFT OUTER JOIN sponsor ON runner.id_runner = sponsor.id_runner 
                WHERE runner.id_runner =" . $_POST['lstRunner'];
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            // Create an associative array mirroring the record in the HTML table
            // This will be used to populate the text boxes with the current runner info
            $thisRunner = [
                "id_runner" => $row['id_runner'],
                "fName" => $row['fName'],
                "lName" => $row['lName'],
                "phone" => $row['phone'],
                "gender" => $row['gender'],
                "sponsor" => $row['sponsorName']
            ];
            //displayMessage("\$thisRunner Array: ", "green");
            //print_r($thisRunner);
        }// end if lstRunner 
     
        // Determine which button may have been clicked
        switch($_POST['btnSubmit']){
        // = = = = = = = = = = = = = = = = = = = 
        // DELETE  
        // = = = = = = = = = = = = = = = = = = = 
        case 'delete':
            //displayMessage("DEBUG DELETE button pushed.", "green");
             //Make sure a runner has been selected.
            if($_POST["txtFName"] == "") {
                displayMessage("Please select a runner's name.", "red");
            } 
            else 
            {
                // Original unsafe SQL
                // $sql = "DELETE FROM runner WHERE id_runner = " . $thisRunner["id_runner"];
                // $sql for prepared statement
                $sql = "DELETE FROM runner WHERE id_runner=?";
                // Prepare
                if($stmt = $conn->prepare($sql)) 
                {
                    // Bind the parameters
                   
                    $stmt->bind_param("i", $thisRunner['id_runner']);
                    if($stmt->errno) {
                        displayMessage("stmt prepare( ) had error.", "red" ); 
                    }
                    
                    
                    // Execute the query
                    $stmt->execute();
                    if($stmt->errno) {
                        displayMessage("Could not execute prepared statement", "red" );
                    }

                    // Free results
                    $stmt->free_result( );

                    // Close the statement
                    $stmt->close( );
                }
            }
            // Zero out the current selected runner
            clearThisRunner( );

        break;
        // = = = = = = = = = = = = = = = = = = = 
        // ADD NEW RUNNER 
        // = = = = = = = = = = = = = = = = = = = 
        case 'new':
            // Get the data from the POST request
            // Used to check for duplicates as well as to INSERT a new record
            $fName = $_POST['txtFName'];
            $lName = $_POST['txtLName'];
            $phone = unformatPhone($_POST['txtPhone']);
            $gender = $_POST['lstGender'];
            $sponsor = $_POST['txtSponsor'];
            
            $sql = "SELECT fName, lName, phone FROM runner WHERE fName=? AND lName=? AND phone=?";
            // Set up a prepared statement
            if($stmt = $conn->prepare($sql)) {
                // Pass the parameters
                //echo "\$fName is: $fName<br />";
                //echo "\$lName is: $lName<br />";
                //echo "\$phone is: $phone<br />";
                $stmt->bind_param("sss", $fName, $lName, $phone) ;
                if($stmt->errno) {
                    displayMessage("stmt prepare( ) had error.", "red" ); 
                }
  
                // Execute the query
                $stmt->execute();
                if($stmt->errno) {
                    displayMessage("Could not execute prepared statement", "red");
                }
    
                // Store the result
                $stmt->store_result( );
                $totalCount = $stmt->num_rows;
                   
                // Free results
                $stmt->free_result( );
                // Close the statement
                $stmt->close( );
            } // end if( prepare( ))

            if($totalCount > 0) {
                displayMessage("This runner is already registered.", "red");
                clearThisRunner( );
                break;
            } 
            // Check for empty name fields or phone 
            if ($_POST['txtFName']=="" 
                || $_POST['txtFName']==""
                || $_POST['txtPhone']=="") 
            {
                displayMessage("Please type in a first and last name and a phone number.", "red");
            }
            // First name and last name are populated
            else {
                // Set up a prepared statement
                $sql = "INSERT INTO runner (id_runner, fName, lName, phone, gender) VALUES (NULL, ?,?,?,?)";
                // Set up a prepared statement
                if($stmt = $conn->prepare($sql)) {
                    // Pass the parameters
                    $stmt->bind_param("ssss", $fName, $lName, $phone, $gender) ;
                    if($stmt->errno) {
                        displayMessage("stmt prepare( ) had error.", "red" ); 
                    }
              
                    // Execute the query
                    $stmt->execute();
                    if($stmt->errno) {
                        displayMessage("Could not execute prepared statement", "red" );
                    }
                    displayMessage($fName." ".$lName." was added.", "green");
              
                    // Free results
                    $stmt->free_result( );
                    // Close the statement
                    $stmt->close( );

                    //Store temporary id 


                    $sql = "(SELECT id_runner FROM runner
                    WHERE fName = ? AND lName = ?)";
                    if($stmt = $conn->prepare($sql))
                        {
                        //Pass parameters
                        $stmt->bind_param('ss', $fName, $lName);

                        if($stmt->errno) {
                            displayMessage("stmt prepare( ) had error.", "red" ); 
                        }
          
                        // Execute the query
                        $stmt->execute();
                        if($stmt->errno) {
                            displayMessage("Could not execute prepared statement", "red" );
                        }
                        $stmt->store_result( );
                        // Bind result variables
                        // one variable for each field in the SELECT
                        // This is the variable that fetch( ) will use to store the result
                        $stmt->bind_result($tempID);
   
                        // Fetch the value - returns the next row in the result set
                        //Stores tempid for inserting sponsor
                        while($stmt->fetch( )) {
                        // output the result
                            $tempID;
                        }
          
                        // Free results
                        $stmt->free_result( );
                        // Close the statement
                        $stmt->close( );
                    }

                    // Add to Table:sponsor containing the foreign key id_runner
                    $sql = "INSERT INTO sponsor (id_sponsor, sponsorName, id_runner) 
                    VALUES (NULL, ?, ?)";
                    //If there is no sponsor it doesn't run the query
                    if ($sponsor =="") 
                    {
                        displayMessage("No Sponsor added.", "red");
                    }
                    else
                    {
                        if($stmt = $conn->prepare($sql))
                        {
                        //Pass parameters
                        $stmt->bind_param('si', $sponsor, $tempID);

                        if($stmt->errno) {
                            displayMessage("stmt prepare( ) had error.", "red" ); 
                        }
          
                        // Execute the query
                        $stmt->execute();
                        if($stmt->errno) {
                            displayMessage("Could not execute prepared statement", "red" );
                        }
          
                        // Free results
                        $stmt->free_result( );
                        // Close the statement
                        $stmt->close( );
                        }
                    }
                } // end if( prepare( ))
            }
            clearThisRunner( );
            // end of if/else($total > 0)
            
        break;
        
        // = = = = = = = = = = = = = = = = = = = 
        // UPDATE   
        // = = = = = = = = = = = = = = = = = = = 
        case 'update':
            $id = $thisRunner['id_runner'];
            $fName = $_POST['txtFName'];
            $lName = $_POST['txtLName'];
            $phone = unformatPhone($_POST['txtPhone']);
            $gender = $_POST['lstGender'];
            $sponsor = $_POST['txtSponsor'];
            //displayMessage("UPDATE button pushed.", "green");
            // Check for empty name 
            if ($_POST['txtFName']=="" || $_POST['txtLName']=="") {
                displayMessage("Please select a runner's name.", "red");
            }
            // First name and last name are selected
            else {
                $isSuccessful = false;
                // Update Table:runner
                // Hard-coded test SQL 
                // Make sure value for id_runner exists in Table:runner.
                //$sql = "UPDATE runner SET fName='FirstTest',
                //                          lName='LastTest',
                //                           phone='1112223333'
                //                          WHERE id_runner = 4";
                // $sql = "UPDATE runner SET fName='" . $_POST['txtFName'] . "', "
                // . " lName = '" . $_POST['txtLName'] . "', "
                // . " phone = '" . unformatPhone($_POST['txtPhone']) . "', "
                // . " gender = '" . $_POST['lstGender'] . "' 
                // WHERE id_runner = " . $thisRunner['id_runner'];

                //Run procedure
                $sql = "CALL updateRunner(".$id.", '".$fName."', '".$lName."', '".$gender."', '".$phone."');";
                $result = $conn->query($sql);
                if($result) {
                    $isSuccessful = true;
                }
                // Update Table:sponsor
                // !!!! Does not update sponsor unless an entry already exists in the table !!!!
                $sql = "UPDATE sponsor SET sponsorName='" . $_POST['txtSponsor'] . "' WHERE id_runner = " . $thisRunner['id_runner'];
                $result = $conn->query($sql);
                if(!$result) {
                    $isSuccessful = false;
                }
                // If successful update the variables
                if($isSuccessful) {
                    displayMessage("Update successful!", "green");
                    $thisRunner['id_runner'] = $_POST['id_runner'];
                    $thisRunner['fName']  = $_POST['txtFName'];
                    $thisRunner['lName']  = $_POST['txtLName'];
                    $thisRunner['phone']  = unformatPhone($_POST['txtPhone']);
                    $thisRunner['gender'] = $_POST['lstGender'];
                    $thisRunner['sponsor']= $_POST['txtSponsor'];
   
                    // Save array as a serialized session variable
                    $_SESSION['sessionThisRunner'] = urlencode(serialize($thisRunner));
                }
            }
            // Zero out the current selected runner
            clearThisRunner( );
          break;
                    
       } // end of switch( )
        
    }
    else // or, a first time visitor?
    {
      //echo '<h1>Welcome</h1>';
    } // end of if new else returning
?>

</head>
<body>
<div id="frame">
<h1>SunRun Registration</h1>

<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>"
      method="POST"
      name="frmRegistration"
      id="frmRegistration">

     <label for="lstRunner"><strong>Select Runner's Name</strong></label>

     <select name="lstRunner" id="lstRunner" onChange="this.form.submit();">
        <option value="new">Select a name</option>
        <?PHP
           // Loop through the runner table to build the <option> list
           $sql = "SELECT id_runner, CONCAT(fName,' ',lName) AS 'name' 
           FROM runner ORDER BY lName";
           $result = $conn->query($sql);
           while($row = $result->fetch_assoc()) {    
              echo "<option value='" . $row['id_runner'] . "'>" . $row['name'] . "</option>\n";
           }
        ?>
   </select> 
   &nbsp;&nbsp;<a href="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">New</a>
   <br />
   <br />
   
   <fieldset>
      <legend>Runner's Information</legend>
            
      <div class="topLabel">
         <label for="txtFName">First Name</label>
         <input type="text" name="txtFName"   id="txtFName"   value="<?php echo $thisRunner['fName']; ?>" />
         
      </div>
      
      <div class="topLabel">
         <label for="txtLName">Last Name</label>
         <input type="text" name="txtLName"   id="txtLName"   value="<?php echo $thisRunner['lName']; ?>" />
      </div>
      
      <div class="topLabel">
         <label for="txtPhone">Phone</label>
         <input type="text" name="txtPhone"   id="txtPhone"   value="<?php echo $thisRunner['phone']; ?>" />
      </div>
      
      <div class="topLabel">
         <label for="lstGender">Gender</label>
         <select name="lstGender" id="lstGender">
            <option value="female">Female</option>
            <option value="male">Male</option>
         </select> 
      </div>
      
      <div class="topLabel">
         <label for="txtSponsor">Sponsor</label>
         <input type="text" name="txtSponsor" id="txtSponsor" value="<?php echo $thisRunner['sponsor']; ?>" />
      </div>
   </fieldset>
   
   <br />
   <button name="btnSubmit" 
           value="delete"
           style="float:left;"
           onclick="this.form.submit();">
           Delete
   </button>
          
   <button name="btnSubmit"    
           value="new"  
           style="float:right;"
           onclick="this.form.submit();">
           Add New Runner Information
   </button>
          
   <button name="btnSubmit" 
           value="update" 
           style="float:right;"
           onclick="this.form.submit();">
           Update
   </button>
   <br />     
  <!-- Use a hidden field to tell server if return visitor -->
  <input type="hidden" name="hidIsReturning" value="true" />
</form>
<br /><br />
<h2>Registered Runners</h2>
<?PHP 
    displayRunnerTable();
    echo '<br />';
?>
<script>
    // Update the values of the list boxes based on the current selection 
   document.getElementById("lstRunner").value = "<?PHP echo $thisRunner['id_runner']; ?>";
   document.getElementById("lstGender").value = "<?PHP echo $thisRunner['gender']; ?>";
</script>
</div>
</body>
</html>