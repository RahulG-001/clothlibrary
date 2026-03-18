<?php
require('db.php');
include("auth.php");
require_once("catalog_lib.php");

$sel = "SELECT c.id, c.title, c.file_type, c.created_at, c.updated_at,
               (SELECT COUNT(*) FROM catalog_file f WHERE f.catalog_id = c.id) AS file_count
        FROM catalog c
        ORDER BY c.id DESC";
$result = mysqli_query($con, $sel);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Catalog</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>
<body>
<!-- top nav -->
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
      </a>
    </div>

    <p>
      <a href="dashboard.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button></a>
      <a href="catalog_add.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-plus"></span> Add Catalog</button></a>
    </p>

    <h3>Catalog List</h3>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Preview</th>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Files</th>
            <th>Updated</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
              <tr>
                <td>
                  <?php
                    $publicBase = catalog_public_base();
                    $files = catalog_get_files($con, (int)$row['id']);
                    if (count($files) === 0) {
                      echo '-';
                    } else {
                      $maxShow = 3;
                      $shown = 0;
                      foreach ($files as $f) {
                        if ($shown >= $maxShow) break;
                        $url = $publicBase . htmlspecialchars($f['file_name']);
                        if ($row['file_type'] === 'image') {
                          echo '<a href="'.$url.'" target="_blank" style="display:inline-block;margin-right:6px;">'
                              .'<img src="'.$url.'" style="max-width:80px; max-height:60px;" />'
                              .'</a>';
                        } else {
                          echo '<a href="'.$url.'" target="_blank" title="'.htmlspecialchars($f['original_name']).'" style="margin-right:10px;white-space:nowrap;">'
                              .'<span class="glyphicon glyphicon-file"></span> PDF'
                              .'</a>';
                        }
                        $shown++;
                      }
                      $total = count($files);
                      if ($total > $maxShow) {
                        echo '<span class="text-muted">+' . ($total - $maxShow) . ' more</span>';
                      }
                    }
                  ?>
                </td>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['file_type']); ?></td>
                <td><?php echo (int)$row['file_count']; ?></td>
                <td>
                  <?php
                    $extDate = $row["updated_at"];
                    $newDate = date("d-m-Y", strtotime($extDate));
                    echo $newDate;
                  ?>
                </td>
                <td><a href="catalog_edit.php?id=<?php echo (int)$row['id']; ?>"><span class="glyphicon glyphicon-edit"></span> Edit</a></td>
                <td><a onclick="return checkDelete()" href="catalog_delete.php?id=<?php echo (int)$row['id']; ?>"><span class="glyphicon glyphicon glyphicon-remove"></span> Delete</a></td>
              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr><td colspan="8">No catalog entries found.</td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="footer">copyright &copy; 2018 The Cloth Library.</div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script>
<script src="../bootstarp/bootstrap.min.js"></script>
<script type="text/javascript">
  function checkDelete(){
    return confirm('Are you sure you want to delete this catalog entry?');
  }
</script>
</body>
</html>

