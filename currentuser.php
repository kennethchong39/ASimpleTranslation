<?php
require_once 'login.php';

//connect to database;
$conn = new mysqli($hn,$un,$pw,$db);
if($conn->connect_error) die("No connection: " . $conn->connect_error);

//create dictionary table if not exist in database
$createTable = "CREATE TABLE IF NOT EXISTS dictionary (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    english VARCHAR(30) NOT NULL,
    spanish VARCHAR(30) NOT NULL
  );";
$filetable = $conn->query($createTable);
if(!$filetable) die("Create Table Failed!");

//check if the session array has input
session_start();
if(htmlentities(isset($_SESSION['username'])))
{
  //we will print the output based on the username and display the data from the database
  $username = htmlentities($_SESSION['username']);
  echo "Welcome back " . strtoupper($username) . " ! <br>";

  echo<<<_AFTERSIGNIN
  <html>
      <body>
        <h1>TRANSLATE ANYTHING PHP</h1>
        <form method = 'post' enctype='multipart/form-data'>
          Upload a dictionary: <input type = 'file' name = 'textfile' accept=".txt" size = '10'>
          <br><br>
          <input type = "submit" name="uploadDictionary" value="Upload">
          <br><br>
          Translation Text:
          <br>
          <textarea name="translationtext" rows = "3" cols = "80"></textarea>
          <br><br>
          Instructions: <br>
          Please enter "." after you complete your sentence. (ex: Hello. How are you.).
          <br>
          Do not go enter a new line after you complete your sentence. <br>
          Upload Dictionary Instructions: <br>
          The dictionary should be formatted to one line in the text file (.txt). <br>
          Inside the (.txt) file, the dictionary should include "English = Spanish,". <br>
          ex: "Hello's = Hola, Good morning = Buenos dias,". <br>
          <br>
          <input type = "submit" name="translatesubmit" value="Translate">
          <br><br>
          <input type ="submit" name ="logout" value="Log out">
        </form>
        <h4> Translated: </h4>
_AFTERSIGNIN;

  //check if upload dictionary is clicked, yes then storeDictionary
  //else check if translate submit button is click, yes then translate
  //else check if logout is clicked, yes then logout
  if(htmlentities(isset($_POST['uploadDictionary'])))
  {
    storeDictionary($conn, $username);
  }
  elseif(htmlentities(isset($_POST['translatesubmit'])))
  {
    translate($conn, $username);
  }
  elseif(htmlentities(isset($_POST['logout'])))
  {
    $exit = killsession();
    if($exit == true)
    {
      header('Location: main.php');
    }
    else {
      echo "Fail to logout!";
    }
  }

}
else echo "Please <a href=auth.php> Log In </a> to proceed!";

//to logout the user
function killsession()
{
  $_SESSION = array();
  $var = session_destroy();
  if($var == true)
  {
    return true;
  }
  else
  {
    return false;
  }
}

//to store the data given by the user's upload file
function storeDictionary($conn, $username)
{
  if(htmlentities(isset($_FILES['textfile'])))
  {
    $user = $username;
    $temp_file = htmlentities($_FILES['textfile']['tmp_name']);
    $content = file_get_contents($temp_file);
    $printed_data = $content;

    //split the data and store in an array
    $eachLine = explode(',', $printed_data);
    array_pop($eachLine);

    foreach($eachLine as $product)
    {
      $item = explode('=', $product);
      $eng = htmlentities($conn->real_escape_string($item[0]));
      $spa = htmlentities($conn->real_escape_string($item[1]));
      $trim_eng = trim($eng);
      $trim_spa = trim($spa);

      //to check duplicates
      $check_if_exists = "SELECT * FROM dictionary WHERE username='$user' AND english='$trim_eng' AND spanish='$trim_spa'";
      $check_result = $conn->query($check_if_exists);
      if(!$check_result) die("Database access failed:" . $conn->error);
      $checkrows = $check_result->num_rows;

      //if there's duplicate then continue, else insert the word into the db.
      if($checkrows > 0){
          continue;
          $check_result->close();
          $insert_result->close();
      }
      else{
        $insert_dictionary = "INSERT INTO dictionary (username, english, spanish) VALUES ('$user', '$trim_eng', '$trim_spa')" ;
        $insert_result = $conn->query($insert_dictionary);
        if(!$insert_result) echo "INSERT failed: '$query' <br>" . $conn->error. "<br><br>";
      }
    }
  }
}

function translate($conn, $username)
{
  if(htmlentities(isset($_POST['translatesubmit'])))
  {
    $user = $username;
    $user_input = $conn->real_escape_string($_POST['translationtext']);
    $need_translation = htmlentities($user_input);
    $split_string = explode('.', $need_translation);
    array_pop($split_string);

    foreach($split_string as $product)
    {
      $find_phrase = trim($product);
      $find_translation = "SELECT * FROM dictionary WHERE english='$find_phrase' && username='$user'";
      $translated = $conn->query($find_translation);
      if(!$translated) die("Database access failed:" . $conn->error);
      $row = $translated->num_rows;

      //if $row more than 0 print from user's dictionary,
      //else print from default dictionary,
      //else print "No Translation Found!"
      if($row > 0){
        for($i=0; $i<$row; ++$i)
        {
          $translated->data_seek($i);
          $rows = $translated->fetch_array(MYSQLI_NUM);
          echo $rows[3] . " ";
        }
        $translated->close();
      }
      else{
        $default_translation = "SELECT * FROM dictionary WHERE english='$find_phrase' && username='default'";
        $default_translated = $conn->query($default_translation);
        if(!$default_translated) die("Database access failed:" . $conn->error);

        $row1 = $default_translated->num_rows;
        if($row1 > 0)
        {
          for($i=0; $i<$row1; ++$i)
          {
            $default_translated->data_seek($i);
            $rows1 = $default_translated->fetch_array(MYSQLI_NUM);
            echo $rows1[3] . " ";
          }
        }
        else
        {
          echo "No translation found! ";
        }
        $default_translated->close();
      }
    }
  }
}

$conn->close();
echo "<br></body></html>";

?>
