<?php
require_once 'login.php';

//connect to database;
$conn = new mysqli($hn,$un,$pw,$db);
if($conn->connect_error) die("No connection: " . $conn->connect_error);

//credential table for storing user
$createTable = "CREATE TABLE IF NOT EXISTS credential (
      firstName VARCHAR(50) NOT NULL,
      lastName VARCHAR(50) NOT NULL,
      email VARCHAR(30) NOT NULL,
      username VARCHAR(50) NOT NULL UNIQUE,
      password VARCHAR(50) NOT NULL
    );";
$credtable = $conn->query($createTable);
if(!$credtable) die($conn->error);

//Webpage
echo<<<_AUTHENTICATION
    <html>
      <head>
        <title> Authentication </title>
      </head>
      <body>
        <h1>TRANSLATE ANYTHING PHP</h1>
        <form method = 'post' enctype='multipart/form-data'>
        <h3>LOG IN</h3>
        <form method="post">
          Username: <input id="username" name="uname" type="text" required>
          <br><br>
          Password: <input id="password" name="pword" type="password" required>
          <br><br>
          <input type = "submit" name="login" value='Log In'>
        </form>
      </body>
    </html>
_AUTHENTICATION;

echo<<<_SIGNUP
      <html>
        <body>
          <h3>SIGN UP</h3>
          <form method = 'post' enctype='multipart/form-data'>
            First Name: <input id="firstname" name="firstname" type="text" required>
            <br><br>
            Last Name: <input id="lastname" name="lastname" type="text" required>
            <br><br>
            Email: <input id="email" name="email" type="text" required>
            <br><br>
            Username: <input id="username" name="username" type="text" required>
            <br><br>
            Password: <input id="password" name="password" type="password" required>
            <br><br>
            <input type = "submit" name="signupform" value="Sign Up">
            <br><br>
            <a href=main.php> Back to homepage without sign-in! </a>
          </form>
        </body>
      </html>
_SIGNUP;

//if(htmlentities(isset($_POST['uname'])) && htmlentities(isset($_POST['pword'])))
if(htmlentities(isset($_POST['login'])))
{
  $un_temp = mysql_entities_fix_string($conn, $_POST['uname']);
  $pw_temp = mysql_entities_fix_string($conn, $_POST['pword']);
  $query = "SELECT * FROM credential WHERE username='$un_temp'";
  $result = $conn->query($query);

  if(!$result) die($connection->error);
  elseif($result->num_rows)
  {
    $row = $result->fetch_array(MYSQLI_NUM);
    $result->close();
    $salt1 = "t@#1";
    $salt2 = "kc!@";
    $token = hash('ripemd128', "$salt1$pw_temp$salt2");

    if($token == $row[4]){
        session_start();
        $_SESSION['username'] = $un_temp;
        echo<<<_hint
          <html>
          <body><h2>Successfully Login!</h2></body>
          </html>
_hint;
        echo "Successful logged in as '$row[0]'";
        die("<p><a href = currentuser.php> Click here to continue!</a></p>");
        // die(header('Location:currentuser.php'));
    }
    else die("Invalid username/password combination");
  }
  else die("Invalid username/password combination");
}
elseif(htmlentities(isset($_POST['signupform']))){

    $fN = mysql_entities_fix_string($conn, $_POST['firstname']);
    $lN = mysql_entities_fix_string($conn, $_POST['lastname']);
    $eM = mysql_entities_fix_string($conn, $_POST['email']);
    $userN = mysql_entities_fix_string($conn, $_POST['username']);
    $passW = mysql_entities_fix_string($conn, $_POST['password']);

    $salt3 = "t@#1";
    $salt4 = "kc!@";
    $token = hash('ripemd128', "$salt3$passW$salt4");
    $query = "INSERT INTO credential (firstName,lastName,email,username,password) VALUES ('$fN', '$lN','$eM','$userN','$token')";
    $result = $conn->query($query);
    if(!$result) die ($conn->error);
}
else {
  header('HTTP/1.0 401 Unauthorized');
  die("Please Log In to proceed! :)");
}

function mysql_entities_fix_string($conn, $string)
{
  return htmlentities(mysql__fix_string($conn, $string));
}

function mysql__fix_string($conn, $string)
{
  if(get_magic_quotes_gpc()) $string =stripslashes($string);
  return $conn->real_escape_string($string);
}

$conn->close();
?>
