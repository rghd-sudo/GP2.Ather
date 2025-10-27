<?php
session_start();
include 'index.php';


$sql = "
SELECT 
    id,
    user_name,
    user_id,
    major,
    course,
    professor,
    purpose,
    type,
    file_name,
    created_at,
    status
FROM 
    requests
ORDER BY 
    created_at DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("❌ SQL Error: " . mysqli_error($conn));
}



$data = [];
$stats = ['completed' => 0, 'rejected' => 0, 'draft' => 0];

if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $data[] = $row;
        switch(strtolower($row['status'])){
            case 'completed': $stats['completed']++; break;
            case 'rejected': $stats['rejected']++; break;
            case 'draft': $stats['draft']++; break;
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>all request </title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #fffcf5;
    margin: 0;
    padding: 20px;
  }
  
  .stats_container {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 30px;
  }
  
  .stats-card {
    background-color: #a8a0a0;
    border-radius: 15px;
    padding: 20px;
    width: 180px;
    text-align: center;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
  }
  
  .stat-number { font-size: 24px; margin-bottom: 10px; }
  .stat-label { font-size: 14px; color: #333; }
  
  .progress-bar {
    width: 100%;
    height: 10px;
    background: #ddd;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
  }
  
  .progress {
    height: 100%;
    width: 0%;
    transition: width 0.5s ease-in-out;
  }
  
  .completed .progress { background: green; }
  .rejected .progress { background: red; }
  .draft .progress { background: blue; }
  
  .completed { border: 3px solid green; }
  .rejected { border: 3px solid red; }
  .draft { border: 3px solid blue; }
  
  .search-bar { text-align: center; margin-top: 20px; }
  .search-bar input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 200px;
  }
  
  .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  /* ---- أزرار الإجراءات ---- */
.btn {
  padding: 5px 12px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  margin-right: 5px; /* مسافة بسيطة بين الأزرار */
  transition: all 0.2s ease;
}

/* زر التعديل */
.btn.edit {
  background-color: #4a90e2; /* أزرق */
  color: white;
}

.btn.edit:hover {
  background-color: #357abd; /* أزرق غامق عند المرور */
}

/* زر الحذف */
.btn.delete {
  background-color: #e74c3c; /* أحمر */
  color: white;
}

.btn.delete:hover {
  background-color: #c0392b; /* أحمر غامق عند المرور */
}

  
  .thead { background-color: #4a6fa5; }
  .thead th { padding: 15px; text-align: right; font-size: 15px; font-weight: 600; }
  tbody td { padding: 12px 15px; border-bottom: 1px solid #e0e0e0; text-align: right; font-size: 14px; color: #333; }
  tbody tr:hover { background-color: #f1f5f9; transform: translateY(-1px); transition: all 0.2s ease; }
  
  .status-completed { background-color: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; text-align: center; font-weight: 500; display: inline-block; min-width: 70px; }
  .status-pending { background-color: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; text-align: center; font-weight: 500; display: inline-block; min-width: 70px; }
  .status-draft { background-color: #e2e3e5; color: #383d41; padding: 5px 12px; border-radius: 20px; text-align: center; font-weight: 500; display: inline-block; min-width: 70px; }
  .status-rejected { background-color: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; text-align: center; font-weight: 500; display: inline-block; min-width: 70px; }
  

</style>
</head>
<body>


<div class="stats_container"> <!-- الحاوية الرئيسية لكل بطاقات الإحصائيات -->
  
  <!-- بطاقة الحالة المكتملة -->
  <div class="stats-card completed"> <!-- class "completed" يعطي اللون الأخضر للبطاقة -->
    <div class="stat-number"><?php echo $stats['completed']; ?></div> <!-- رقم الطلبات المكتملة -->
    <div class="stat-label">Completed</div> <!-- اسم الحالة -->
    <div class="progress-bar"> <!-- صندوق خلفية شريط التقدم -->
      <div class="progress completed" 
           style="width: <?php echo ($stats['completed'] / max(array_sum($stats),1) * 100); ?>%;">
        <!-- طول الشريط يعتمد على نسبة الحالة من إجمالي الطلبات -->
      </div>
    </div>
  </div>

  <!-- بطاقة الحالة المرفوضة -->
  <div class="stats-card rejected"> <!-- class "rejected" يعطي اللون الأحمر -->
    <div class="stat-number"><?php echo $stats['rejected']; ?></div> <!-- رقم الطلبات المرفوضة -->
    <div class="stat-label">Rejected</div> <!-- اسم الحالة -->
    <div class="progress-bar">
      <div class="progress rejected" 
           style="width: <?php echo ($stats['rejected'] / max(array_sum($stats),1) * 100); ?>%;">
        <!-- طول الشريط يعتمد على نسبة الحالة من إجمالي الطلبات -->
      </div>
    </div>
  </div>

  <!-- بطاقة الحالة المسودة -->
  <div class="stats-card draft"> <!-- class "draft" يعطي اللون الأزرق -->
    <div class="stat-number"><?php echo $stats['draft']; ?></div> <!-- رقم الطلبات المسودة -->
    <div class="stat-label">Draft</div> <!-- اسم الحالة -->
    <div class="progress-bar">
      <div class="progress draft" 
           style="width: <?php echo ($stats['draft'] / max(array_sum($stats),1) * 100); ?>%;">
        <!-- طول الشريط يعتمد على نسبة الحالة من إجمالي الطلبات -->
      </div>
    </div>
  </div>

</div>



<!----شريط البجث---->
<h2>Requests</h2>
<div class="search-bar">
<input type="text" id="searchInput" placeholder="Search">

</div>


<table class="table">
  <thead class="thead">
    <tr>
      <th>#</th>
      <th>Full name</th>
      <th>Type</th>
      <th>Date</th>
      <th>Purpose</th>
      <th>Status</th>
      <th>Actions</th> 
    </tr>
  </thead>
  <tbody id="tableBody">
  <?php foreach($data as $index => $row): ?>
    <tr>
       <td><?php echo $index + 1; ?></td>
       <td><?php echo htmlspecialchars($row['user_name']); ?></td>
        <td><?php echo htmlspecialchars($row['type']); ?></td>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
     <td><?php echo htmlspecialchars($row['purpose']); ?></td>
      <td>
        <?php 
          $statusClass = '';
          switch(strtolower($row['status'])){
            case 'completed': $statusClass = 'status-completed'; break;
            case 'pending': $statusClass = 'status-pending'; break;
            case 'draft': $statusClass = 'status-draft'; break;
            case 'rejected': $statusClass = 'status-rejected'; break;
          }
        ?>
        <span class="<?php echo $statusClass; ?>">
          <?php echo ucfirst($row['status']); ?>
        </span>
      </td>
      <!-- عمود الأزرار -->
      <td>   <!-- يفتح الي المسودة وغيرها عند recommendation-writing  بام   -->
      <button class="btn edit" onclick="window.location.href='recommendation-writing.php?id=<?php echo $row['id']; ?>'">Edit</button>

      <button class="btn delete" onclick="deleteRequest(<?php echo $row['id']; ?>, this)">Delete</button>

      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>



<script>
// بحث مباشر
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("tableBody");
const rows = Array.from(tableBody.getElementsByTagName("tr"));

searchInput.addEventListener("input", function() {
  const search = this.value.toLowerCase();
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(search) ? "" : "none";
  });
});
</script>





<!--زر الحذف-->
<script>
function deleteRequest(id, btn) {
  if (!confirm("Are you sure you want to delete this request?")) return;

  fetch("delete_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + id
  })
  .then(response => response.text())
  .then(result => {
    if (result.trim() === "success") {
      const row = btn.closest("tr");
      row.style.transition = "opacity 0.5s";
      row.style.opacity = "0";
      setTimeout(() => row.remove(), 500);
      alert("✅ تم حذف الطلب بنجاح");
    } else {
      alert("❌ حدث خطأ أثناء الحذف");
    }
  })
  .catch(error => {
    alert("⚠️ فشل الاتصال بالسيرفر");
    console.error(error);
  });
}
</script>



</body>
</html>
