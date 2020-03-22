<?php
//read file input, interprets it and send to database
//read input from textbox, and then apply translation
//two models:
// 1. default models --> user didn't sign in then translation still works
// 2. user sign in --> log in and upload translation model, the model must apply by the application
//                     or didn't upload a translation model then apply default model.

// things to do: place default model here and enable translation here too.
require_once 'login.php';

//connect to database;
$conn = new mysqli($hn,$un,$pw,$db);
if($conn->connect_error) die("No connection: " . $conn->connect_error);

//create a dictionary table in the database
$createTable = "CREATE TABLE IF NOT EXISTS dictionary (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    english VARCHAR(30) NOT NULL,
    spanish VARCHAR(30) NOT NULL
  );";
$filetable = $conn->query($createTable);
if(!$filetable) die("Create Table Failed!");


//import the file from the current directory
//read, intepret, and store in the database
//the dictionary should be formatted to one line in text file (.txt)
//Inside the (.txt) file, the dictionary should include "English = Spanish,"
//ex: Hello's = Hola, Good morning = Buenos dias,
$file = '/opt/lampp/htdocs/P174/english-spanish.txt';
storeDictionary($conn, $file);

//Webpage (First Page of my program)
echo<<<_BEFORESIGNIN
      <html>
        <body>
          <h1>TRANSLATE ANYTHING PHP</h1>
          <form method = 'post' action=main.php enctype='multipart/form-data'>
            Translation Text [Default Translation]:
            <br>
            <textarea name="translationtext" rows = "3" cols = "80"></textarea>
            <br><br>
            Instructions: <br>
            Please enter "." after you complete your sentence. (ex: Hello. How are you.).
            <br>
            Do not go enter a new line after you complete your sentence.
            <br><br>
            <input type = "submit" name="translatesubmit" value="Translate">
          </form>
          <br>
          Please sign up and/or log in to upload a dictionary of your choice <br><br>
          <a href=auth.php> SignUp/LogIn </a>
          <h3> Translation based on default translation: </h3>
_BEFORESIGNIN;

//if translatesubmit is hit, then do the translation
if(htmlentities(isset($_POST['translatesubmit'])))
{
  //translate the sentence from the user
  translate($conn);
}

//translation to translate user's input to the default translation
function translate($conn)
{
    $user = 'default';
    $user_input = get_post($conn, 'translationtext');
    $need_translation = htmlentities($user_input);
    $split_string = explode('.', $need_translation);
    array_pop($split_string);

    foreach($split_string as $product)
    {
      $find_phrase = trim($product);
      $find_translation = "SELECT * FROM dictionary WHERE english='$find_phrase' && username='$user' ";
      $translated = $conn->query($find_translation);
      if(!$translated) die("Database access failed:" . $conn->error);
      $row = $translated->num_rows;

      //if row is more than 0, then print the translation.
      if($row > 0)
      {
        for($i=0; $i<$row; ++$i)
        {
          $translated->data_seek($i);
          $rows = $translated->fetch_array(MYSQLI_NUM);
          echo $rows[3] . " ";
        }
      }
      else
      {
        echo "No translation found!";
      }
    }
    $translated->close();
}

function storeDictionary($conn,$file)
{
    $user = 'default';
    $temp_file = $file;
    $content = file_get_contents($temp_file);
    // $printed_data = $conn->real_escape_string($content);
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

      //if there's duplicate, continue, else insert the word into db.
      if($checkrows > 0){
          continue;
          $check_result->close();
          $insert_result->close();
      }
      else {
        $insert_dictionary = "INSERT INTO dictionary (username, english, spanish) VALUES ('$user', '$trim_eng', '$trim_spa')" ;
        $insert_result = $conn->query($insert_dictionary);
        if(!$insert_result) echo "INSERT failed: '$query' <br>" . $conn->error. "<br><br>";
      }
    }
}

function get_post($conn, $var)
{
  return $conn->real_escape_string($_POST[$var]);
}

$conn->close();
?>
