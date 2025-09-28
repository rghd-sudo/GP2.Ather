<?php include 'index.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recommendation System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #fafafa;
    }

    /* ---------- القائمة الجانبية ---------- */
    .sidebar {
      width: 200px;
      background: #cbe2ec;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 20px;
    }
    .sidebar a {
      display: block;
      padding: 12px;
      color: #000;
      text-decoration: none;
      font-size: 16px;
      margin: 5px 0;
    }
    .sidebar a:hover {
      background: #b2d3e6;
      border-radius: 8px;
    }

    /* ---------- المحتوى ---------- */
    .content {
      margin-left: 220px;
      padding: 20px;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    .logo img {
      width: 60px;
    }

    .btn {
      background: #48b29c;
      border: none;
      padding: 12px 20px;
      border-radius: 20px;
      color: #fff;
      cursor: pointer;
      font-size: 16px;
    }
    .btn:hover {
      background: #3b9a86;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      background: #fff;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 12px;
      text-align: center;
    }
    th {
      background: #f5f5f5;
    }
    .pending {
      color: orange;
      font-weight: bold;
    }
    .accepted {
      color: green;
      font-weight: bold;
    }
    .actions button {
      border: none;
      padding: 6px 10px;
      margin: 0 3px;
      border-radius: 6px;
      cursor: pointer;
    }
    .delete {
      background: #f8a5a5;
    }
    .edit {
      background: #a5d8f8;
    }
  </style>
</head>
<body>
  <!-- القائمة الجانبية -->
  <div class="sidebar">
    <a href="Student_profile.php">profile</a>
    <a href="new_request.php">New Request</a>
    <a href="#">Track Request</a>
    <a href="#">Notifications</a>
  </div>

  <!-- المحتوى -->
  <div class="content">
    <div class="logo">
      <img src="logo.png" alt="Logo">
      <h3>ATHER GRADUATE</h3>
    </div>

    <button class="btn" onclick="window.location.href='new_request.php'">
      + New Recommendation Request
    </button>

    <h3>My Request</h3>
    <table>
      <tr>
        <th>#</th>
        <th>Professor</th>
        <th>Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
      <?php
      $sql = "SELECT * FROM requests ORDER BY id ASC";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              echo "<tr>
                      <td>".$row['id']."</td>
                      <td>".$row['professor']."</td>
                      <td>".$row['date']."</td>
                      <td class='".($row['status']=="Pending"?"pending":"accepted")."'>".$row['status']."</td>
                      <td class='actions'>
                        <button class='delete'>🗑</button>
                        <button class='edit'>✏</button>
                      </td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='5'>لا توجد طلبات بعد</td></tr>";
      }
      ?>
    </table>
  </div>
</body>
</html>