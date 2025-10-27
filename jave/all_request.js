fetch(".php")
.then(response => response.json())
.then(stats => {
  document.getElementById("completedCount").textContent = stats.completed;
  document.getElementById("rejectedCount").textContent = stats.rejected;
  document.getElementById("draftCount").textContent = stats.draft;

  document.getElementById("completedProgress").style.width = (stats.completed / stats.total * 100) + "%";
  document.getElementById("rejectedProgress").style.width = (stats.rejected / stats.total * 100) + "%";
  document.getElementById("draftProgress").style.width = (stats.draft / stats.total * 100) + "%";

  const tbody = document.getElementById("tableBody");
  tbody.innerHTML = "";
  stats.data.forEach(item => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${item.id}</td>
      <td>${item.student_name}</td>
      <td>${item.type}</td>
      <td>${item.data}</td>
      <td>${item.purpose}</td>
      <td class="status-${item.status}">${item.status}</td>
    `;
    tbody.appendChild(tr);
  });
})
.catch(err => console.error(err));
