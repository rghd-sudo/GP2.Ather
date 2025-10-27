<?php
include 'php/connection.php';

$sql = "
SELECT 
    r.id,
    u.full_name,
    r.recommendation_type,
    r.request_date,
    r.purpose,
    r.status
FROM 
    requests r
JOIN 
    users u ON r.student_id = u.id
ORDER BY 
    r.request_date DESC
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
 <link rel="stylesheet" href="css/DR_Recommendation.css">
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
  <input type="text" placeholder="Search">
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
      <td><?php echo htmlspecialchars($row['full_name']); ?></td>
      <td><?php echo htmlspecialchars($row['recommendation_type']); ?></td>
      <td><?php echo htmlspecialchars($row['request_date']); ?></td>
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

        <button class="btn delete" onclick="if(confirm('Are you sure?')) window.location.href='delete_request.php?id=<?php echo $row['id']; ?>'">Delete</button>
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
</body>
</html>
