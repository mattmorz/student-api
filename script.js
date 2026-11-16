
let students = [];


const PAGE_SIZE = 5;
let currentPage = 1;


const searchInput = document.getElementById("searchInput");
const sortSelect = document.getElementById("sortSelect");
const programFilter = document.getElementById("programFilter");
const yearFilter = document.getElementById("yearFilter");
const resetBtn = document.getElementById("resetBtn");
const tableBody = document.getElementById("tableBody");
const recordCount = document.getElementById("recordCount");
const message = document.getElementById("message");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageInfo = document.getElementById("pageInfo");

// Task 1: Load JSON data
fetch("http://127.0.0.1:8000/api/students.php")
  .then(function (response) {
    
    if (!response.ok) {
      throw new Error("HTTP error " + response.status);
    }
    return response.json();
  })
  .then(function (data) {
    students = data;
    updateTable();
  })
  .catch(function (error) {
    console.error(error);
    message.textContent =
      "Failed to load student records. Make sure you are using a local server (e.g. Live Server).";
  });

// Task 1: Render table rows for the given records
function renderTable(records, totalCount) {
  tableBody.innerHTML = "";

  records.forEach(function (student) {
    const row = document.createElement("tr");
    row.innerHTML =
      "<td>" + student.first_name + "</td>" +
      "<td>" + student.middle_initial + "</td>" +
      "<td>" + student.last_name + "</td>" +
      "<td>" + student.program + "</td>" +
      "<td>" + student.year_level + "</td>";
    tableBody.appendChild(row);
  });

  recordCount.textContent = "Total records: " + totalCount;
  message.textContent = totalCount === 0 ? "No matching records found." : "";
}

// Tasks 2 and 4: Search + filters
function getFilteredRecords() {
  const query = searchInput.value.trim().toLowerCase();
  const program = programFilter.value;
  const year = yearFilter.value;

  return students.filter(function (s) {
    const matchesSearch =
      s.first_name.toLowerCase().includes(query) ||
      s.middle_initial.toLowerCase().includes(query) ||
      s.last_name.toLowerCase().includes(query) ||
      s.program.toLowerCase().includes(query);

    const matchesProgram = program === "all" || s.program === program;
    const matchesYear = year === "all" || s.year_level === Number(year);

    return matchesSearch && matchesProgram && matchesYear;
  });
}

// Task 3: Sort a COPY of the records
function getSortedRecords(records) {
  const sorted = records.slice();
  const option = sortSelect.value;

  sorted.sort(function (a, b) {
    switch (option) {
      case "first-asc":
        return a.first_name.localeCompare(b.first_name);
      case "first-desc":
        return b.first_name.localeCompare(a.first_name);
      case "last-asc":
        return a.last_name.localeCompare(b.last_name);
      case "last-desc":
        return b.last_name.localeCompare(a.last_name);
      case "year-asc":
        return a.year_level - b.year_level;
      case "year-desc":
        return b.year_level - a.year_level;
      default:
        return 0; // keep original order
    }
  });

  return sorted;
}

// Task 5: Combine everything (filter -> sort -> paginate -> render)
function updateTable() {
  const filtered = getFilteredRecords();
  const sorted = getSortedRecords(filtered);

  const totalPages = Math.max(1, Math.ceil(sorted.length / PAGE_SIZE));
  if (currentPage > totalPages) {
    currentPage = totalPages;
  }

  const start = (currentPage - 1) * PAGE_SIZE;
  const pageRecords = sorted.slice(start, start + PAGE_SIZE);

  renderTable(pageRecords, sorted.length);

  pageInfo.textContent = "Page " + currentPage + " of " + totalPages;
  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
}

// Go back to page 1 whenever search, sort, or filters change
function resetPageAndUpdate() {
  currentPage = 1;
  updateTable();
}

// Event listeners
searchInput.addEventListener("input", resetPageAndUpdate);
sortSelect.addEventListener("change", resetPageAndUpdate);
programFilter.addEventListener("change", resetPageAndUpdate);
yearFilter.addEventListener("change", resetPageAndUpdate);

resetBtn.addEventListener("click", function () {
  searchInput.value = "";
  sortSelect.value = "default";
  programFilter.value = "all";
  yearFilter.value = "all";
  resetPageAndUpdate();
});

prevBtn.addEventListener("click", function () {
  if (currentPage > 1) {
    currentPage--;
    updateTable();
  }
});

nextBtn.addEventListener("click", function () {
  currentPage++;
  updateTable();
});
