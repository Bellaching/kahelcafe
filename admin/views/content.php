<?php

include './../inc/topNav.php'; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Virtual Tour Upload</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f0f2f5;
    }

    .wrapper {
      max-width: 900px;
      margin: 50px auto;
      padding: 40px;
      background-color: #ffffff;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .title {
      font-size: 28px;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .description {
      font-size: 16px;
      color: #7f8c8d;
      margin-bottom: 30px;
    }

    .divider {
      height: 2px;
      background-color: #ecf0f1;
      margin: 20px 0;
      border-radius: 2px;
    }
  </style>
</head>
<body>

  <div class="wrapper">
    <div class="title">Upload Virtual Tour Images</div>
    <div class="description">
      Hello Admin! Please upload the images you want to display in the virtual tour section. Make sure they are high-quality and relevant to the tour.
    </div>

    <div class="divider"></div>

    <?php include "./../../admin/views/virt.php"; ?>
  </div>

</body>
</html>
