<?php
include "./../../admin/views/virt.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Virtual Tour Images</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f0f4f8;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .header {
      font-size: 28px;
      font-weight: bold;
      color: #333;
      margin-bottom: 15px;
    }

    .subtext {
      font-size: 16px;
      color: #666;
      margin-bottom: 25px;
    }

    .upload-section {
      border: 2px dashed #9ca3af;
      padding: 40px;
      text-align: center;
      border-radius: 10px;
      background-color: #f9fafb;
    }

    .upload-section:hover {
      background-color: #eef2f7;
    }

    .upload-section input[type="file"] {
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="header">Virtual Tour Image Upload</div>
    <div class="subtext">Hi Admin! You can upload images here to showcase in the virtual tour section of your website.</div>

    <div class="upload-section">
      <p>Drag and drop your images here<br>or</p>
      <input type="file" name="virtualTourImages[]" multiple>
    </div>
  </div>

</body>
</html>
