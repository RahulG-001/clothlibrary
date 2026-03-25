<?php 
require('db.php');
include("auth.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : View Salesman</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>

<body>

<ul class="topNav">
  <li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
  <li><a class="admin" href="../region.html">User Login</a></li>
  <li><a class="admin selected" href="login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>

<div id="mainPanel"> 
  
  <div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;">
    <img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
  </div>
  
  <div id="content_area">
    <div align="right" style="padding-bottom:10px;">
      <?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
        <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
      
    <p>
      <a href="dashboard.php">
        <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> 
      <a href="add_salesman.php">
        <button type="button" class="btn btn-sm btn-primary">Add New Salesman</button>
      </a>
    </p>
      
    <h3>View Salesman</h3>
    
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Phone Number</th>
            <th>Photo</th>
            <th>Password</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $sel_query="SELECT * FROM salesman";
            $result = mysqli_query($con,$sel_query);
            while($row = mysqli_fetch_assoc($result)) { ?>
          <tr>
            <td><?php echo $row["first_name"]; ?></td>
            <td><?php echo $row["last_name"]; ?></td>
            <td><?php echo $row["phone"]; ?></td>
            <td>
              <?php
                $img = isset($row['profile_image']) ? trim((string)$row['profile_image']) : '';
                if ($img !== '') {
                  // Saved under: software/salesman_profile_images/
                  $src = '../salesman_profile_images/'.$img;
                  echo '<img src="'.$src.'" width="40" height="40" style="object-fit:cover; border-radius:4px;" alt="Salesman photo" />';
                } else {
                  echo '<span style="color:#999;">N/A</span>';
                }
              ?>
            </td>
            <td><?php echo $row["password"]; ?></td>
            <td><a href="edit_salesman.php?id=<?php echo $row["id"]; ?>"><span class="glyphicon glyphicon-edit"></span>Edit</a></td>
            <td><a onclick="return checkDelete()" href="delete_salesman.php?id=<?php echo $row["id"]; ?>"><span class="glyphicon glyphicon glyphicon-remove"></span> Delete</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <div id="footer">
    copyright &copy; 2018 The Cloth Library.
  </div>
</div>

<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
<script type="text/javascript">
  function checkDelete(){
    return confirm('Are you sure you want to delete Salesman?');
  }
</script>
</body>
</html>

