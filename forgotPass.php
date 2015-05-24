<?php
	session_start();
	
	// If user is already logged in, redirect to welcome page
	if( isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: welcome.php" );
	}
	
	// Connect to database
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
	
	// When button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'post' ] ) ) 
		{
			// Get username and email
			$username = mysqli_real_escape_string( $db, $_POST[ 'username' ] );
			$email = mysqli_real_escape_string( $db, $_POST[ 'email' ] );
			
			// If none of them is empty
			if( strlen( $username ) > 0 && strlen( $email ) > 0 )
			{
				// Check if user exists
				$sql = "SELECT password FROM User WHERE username='$username' and email='$email'";
				$result = mysqli_query( $db, $sql );
				$count = mysqli_num_rows( $result );

				if( mysqli_num_rows( $result ) == 1 )
				{
					// User does exist, retrieve password 
					mysqli_data_seek( $result, 0 );
					$tuple = mysqli_fetch_assoc( $result );
					$pass = $tuple["password"];

					header( "location: forgotPass.php?err=2&pass=$pass" );
				}
				else
				{
					// User does not exist
					header( "location: forgotPass.php?err=1" );
				}
			}
		}
		else if( isset( $_POST[ 'back' ] ) ) 
		{
			header( "location: index.php" );
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateForm()
{
	// If username or password is empty, give error message
	var user = document.forms["Retrieve"]["username"].value;
	var email = document.forms["Retrieve"]["email"].value;
	
	if( user == null || user == "" )
	{
		alert( "ERROR: Username can't be empty!" );
		return false;
	}
	
	if( email == null || email == "" )
	{
		alert( "ERROR: Email can't be empty!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Recover Password</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<?php 
			// If user tried registering with an already existing username
			if( $_GET['err'] == 1 )
			{
				echo "<p><font color=red><b>ERROR: User does not exist!</b></font></p>";
			}
			else if( $_GET['err'] == 2 )
			{
				$pass = $_GET['pass'];
				echo "<p><font color=green><b>Your password is: $pass</b></font></p>";
			}
		?>
		
		<table>
		<form name="Retrieve" method="post">
			<tr>
			<td>Enter username: </td><td><input type="text" size=50 maxlength=50 name="username" /></td>
			</tr>
			<tr>
			<td>Enter e-mail: </td><td><input type="text" size=50 maxlength=50 name="email" /></td>
			</tr>
			<tr>
			<td align="center" colspan=2>
			<input type="submit" onclick="return validateForm()" name="post" value="Retrieve" />
			<input type="submit" name="back" value="Back" />
			</td>
			</tr>
		</form>
		</table>
	</div>
	</body>
</html>

