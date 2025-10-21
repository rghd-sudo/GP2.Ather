<?php
// مصفوفة تمثل الطلبات (ممكن لاحقاً تجي من قاعدة بيانات)
$requests = [
[
"id" => 1,
"name" => "Nora Mohammed",
"date" => "8/1/2025",
"type" => "Academic",
"purpose" => "MSc application – KSU"
],
[
"id" => 2,
"name" => "Aseel Ateeq",
"date" => "24/2/2025",
"type" => "Professional",
"purpose" => "Job at STC"
],
[
"id" => 3,
"name" => "Dana Ali",
"date" => "2/3/2025",
"type" => "Academic",
"purpose" => "Scholarship – UK"
],
[
"id" => 4,
"name" => "Shahd Ahmed",
"date" => "1/4/2025",
"type" => "Professional",
"purpose" => "Internship – Huawei"
]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Incoming Recommendation Requests</title>
<style>
body {
font-family: Arial, sans-serif;
background-color: #fafafa;
display: flex;
}

.sidebar {
width: 180px;
background-color: #dbe8ef;
padding: 20px;
height: 100vh;
}

.sidebar h3 {
margin-bottom: 30px;
font-size: 16px;
text-align: center;
}

.sidebar a {
display: block;
margin: 15px 0;
text-decoration: none;
color: black;
}

.content {
flex: 1;
padding: 20px;
}

h2 {
text-align: center;
background-color: #6a8fde;
color: white;
padding: 10px;
border-radius: 8px;
}

.requests {
display: grid;
grid-template-columns: repeat(2, 1fr);
gap: 15px;
margin-top: 20px;
}

.request-card {
border: 1px solid #ccc;
padding: 15px;
border-radius: 8px;
background: white;
box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
}

.request-card h3 {
margin: 0 0 10px;
color: #2c3e50;
}

.request-card p {
margin: 5px 0;
}

.buttons {
margin-top: 10px;
}

.buttons button {
padding: 6px 12px;
margin-right: 8px;
border: none;
border-radius: 5px;
cursor: pointer;
}

.accept {
background-color: #4CAF50;
color: white;
}

.reject {
background-color: #f44336;
color: white;
}
</style>
</head>
<body>

<!-- القائمة الجانبية -->
<div class="sidebar">
<h3>Ather Graduate</h3>
<a href="Professor-Profile.php">Profile</a>
<a href="requests.php">Requests</a>
<a href="recommendation-writing.php">Recommendations</a>
<a href="#">Arwa Abdullah</a>
</div>

<!-- المحتوى -->
<div class="content">
<h2>Incoming Recommendation Requests</h2>
<div class="requests">
<?php foreach ($requests as $req): ?>
<div class="request-card">
<h3>Request <?= $req["id"] ?></h3>
<p><strong>Name:</strong> <?= $req["name"] ?></p>
<p><strong>Date:</strong> <?= $req["date"] ?></p>
<p><strong>Type:</strong> <?= $req["type"] ?></p>
<p><strong>Purpose:</strong> <?= $req["purpose"] ?></p>
<div class="buttons">
<button class="accept">Accept</button>
<button class="reject">Reject</button>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

</body>
</html>