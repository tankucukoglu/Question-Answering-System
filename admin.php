<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	$username = $_SESSION[ 'login_user' ];

	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
						  
	// If user is not an Admin, redirect them to welcome page
	$sql1 = "select username from User where username='$username' and userType='Admin'";
	$sql2 = "select * from User order by rep desc";
	$sql3 = "select * from UserType order by repThreshold desc";
	$sql4 = "select * from Category";
	
	$isAdmin = mysqli_query( $db, $sql1 );
	$allAccs = mysqli_query( $db, $sql2 );
	$userTypes = mysqli_query( $db, $sql3 );
	$categories = mysqli_query( $db, $sql4 );
	if( mysqli_num_rows( $isAdmin ) === 0 )
	{
		// Return to welcome page with an error code
		header( "location: welcome.php?err=999" );
	}
	
	// A button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'logout' ] ) ) 
		{
			// If Logout button is pressed
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'newUserType' ] ) )
		{
			$userType = $_POST['userType'];
			$repT = $_POST['repThreshold'];
			$sql = "INSERT INTO UserType VALUES('$userType', $repT);";
			mysqli_query( $db, $sql );
			$sql2 = "call updateAllUserTypes();";
			mysqli_query( $db, $sql2 );
			
			header( "location: admin.php?suc=1" );
		}
		else if( isset( $_POST[ 'newCategory' ] ) )
		{
			$catName = $_POST['catName'];
			$catDesc = $_POST['catDesc'];
			$catParent = $_POST['catParent'];
			
			$sql = "INSERT INTO Category VALUES(0, '$catName', '$catDesc');";
			mysqli_query( $db, $sql );
			
			$catID = mysqli_insert_id( $db );
			
			if( $catParent !== 0 )
			{
				$sql2 = "INSERT INTO sub_category VALUES($catID, $catParent);";
				mysqli_query( $db, $sql2 );
			}
			
			header( "location: admin.php?suc=1" );
		}
		else
		{
			// If Back button is pressed
			// Redirect to welcome page
			header( "location: welcome.php" );
		}
	}
?>

<html>
	<head>
		<title>Admin Panel</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<form name="NewUserType" method="post">
			<input type="submit" name="back" value="Back" />
			<input type="submit" name="logout" value="Logout" />
			
			<hr style="background:black; border:0; height:3px" />
			UserType: <input type="text" size=50 maxlength=50 value="Name" name="userType" />
			Rep Threshold: <input type="number" size=50 value=0 name="repThreshold" />
			<input type="submit" name="newUserType" value="Create User Type" />
			
			<hr style="background:black; border:0; height:3px" />
		</form>
		
		<form name="NewCategory" method="post">
			Category Name: <input type="text" size=50 maxlength=50 name="catName" />
			Category Description: <input type="text" size=50 maxlength=250 name="catDesc" />
			Parent Category: <select name="catParent">
				<option value="0" selected="selected">* None *</option>
				<?php
					// Create combo boxes for categories
					$sql = "SELECT catID, name FROM Category ".
							"WHERE catID not in(select childCatID from sub_category)";
					$result = mysqli_query( $db, $sql );
					mysqli_data_seek( $result, 0 );
					while( $tuple = mysqli_fetch_assoc( $result ) ) 
					{
						echo "<option value=\"".$tuple["catID"]."\">".$tuple["name"]."</option>";
					}
				?>
			</select>
			
			<input type="submit" name="newCategory" value="Create Category" />
			
			<hr style="background:black; border:0; height:3px" />
		</form>
		
		<p><font color=purple><b>- All Users in the System -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Username</b></td>
		<td><b>Reputation</b></td><td><b>E-Mail</b></td><td><b>User Type</b></td><td><b>Action</b></td></tr>
			<?php
				// Print all user accounts
				mysqli_data_seek( $allAccs, 0 );
				while( $tuple = mysqli_fetch_assoc( $allAccs ) ) 
				{
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["username"]."</td><td>".$tuple["rep"]."</td>".
						 "<td>".$tuple["email"]."</td><td>".$tuple["userType"]."</td>".
						 "<td><a href=\"closeAccount.php?id=".$tuple["username"]."\">CLOSE</a></td></tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- Existing User Types -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>User Type</b></td><td><b>Rep Threshold</b></td><td><b>Action</b></td></tr>
			<?php
				// Print all user types
				mysqli_data_seek( $userTypes, 0 );
				while( $tuple = mysqli_fetch_assoc( $userTypes ) ) 
				{
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["userType"]."</td><td>".$tuple["repThreshold"]."</td>".
						 "<td><a href=\"deleteUserType.php?id=".$tuple["userType"]."\">DELETE</a></td></tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- Categories -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Category</b></td><td><b>Description</b></td><td><b>Parent</b></td><td><b>Action</b></td></tr>
			<?php
				// Print all categories
				mysqli_data_seek( $categories, 0 );
				while( $tuple = mysqli_fetch_assoc( $categories ) ) 
				{
					$sql = "SELECT name FROM Category, sub_category ".
							"WHERE catID=parentCatID and childCatID=".$tuple["catID"];
					$result = mysqli_query( $db, $sql );
					
					if( mysqli_num_rows( $result ) === 0 )
					{
						$parent = "";
					}
					else
					{
						$tuple2 = mysqli_fetch_assoc( $result );
						$parent = $tuple2["name"];
					}
					
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["name"]."</td><td>".$tuple["description"]."</td><td>$parent</td>".
						 "<td><a href=\"deleteCategory.php?id=".$tuple["catID"]."\">DELETE</a></td></tr>\n";
				}
			?>
		</table></p>
	</div>
	</body>
</html>