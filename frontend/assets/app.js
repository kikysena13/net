(() => {
  "use strict";

  const apiUrl = window.PORTAL_CONFIG?.apiUrl;
  const page = document.body.dataset.page;
  let csrfToken = "";
  let students = [];

  async function api(action, options = {}) {
    if (!apiUrl) throw new Error("URL API belum dikonfigurasi di frontend/config.js.");
    const { csrf = false, headers = {}, ...requestOptions } = options;
    const response = await fetch(`${apiUrl}?action=${encodeURIComponent(action)}`, {
      credentials: "include",
      ...requestOptions,
      headers: {
        ...(requestOptions.body ? { "Content-Type": "application/json" } : {}),
        ...(csrf ? { "X-CSRF-Token": csrfToken } : {}),
        ...headers
      }
    });
    let result;
    try {
      result = await response.json();
    } catch {
      throw new Error("Respons API tidak valid. Pastikan backend PHP aktif.");
    }
    if (!response.ok) throw new Error(result.error || "Permintaan gagal.");
    return result;
  }

  function showMessage(element, message, isError = false) {
    if (!element) return;
    element.textContent = message;
    element.classList.toggle("message-error", isError);
    element.hidden = false;
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[character]);
  }

  function setDate() {
    const element = document.querySelector("#today-date");
    if (element) element.textContent = new Intl.DateTimeFormat("id-ID", {
      weekday: "long", day: "numeric", month: "long", year: "numeric"
    }).format(new Date());
  }

  function redirectForRole(role) {
    window.location.replace(role === "admin" ? "admin.html" : "mahasiswa.html");
  }

  async function initLogin() {
    const form = document.querySelector("#login-form");
    const error = document.querySelector("#login-error");
    const toggle = document.querySelector(".password-toggle");
    toggle?.addEventListener("click", () => {
      const input = document.querySelector("#password");
      input.type = input.type === "password" ? "text" : "password";
      toggle.textContent = input.type === "password" ? "Lihat" : "Sembunyikan";
      toggle.setAttribute("aria-label", input.type === "password" ? "Tampilkan password" : "Sembunyikan password");
    });
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      error.hidden = true;
      const submit = form.querySelector('button[type="submit"]');
      submit.disabled = true;
      try {
        const payload = Object.fromEntries(new FormData(form));
        const result = await api("login", { method: "POST", body: JSON.stringify(payload) });
        redirectForRole(result.user.role);
      } catch (exception) {
        showMessage(error, exception.message, true);
        submit.disabled = false;
      }
    });
  }

  function renderStudents() {
    const tbody = document.querySelector("#student-rows");
    document.querySelector("#student-count").textContent = `${students.length} mahasiswa`;
    if (!students.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="empty-state">Belum ada data mahasiswa.</td></tr>';
      return;
    }
    tbody.innerHTML = students.map((student) => `
      <tr>
        <td><div class="person-cell"><span class="avatar avatar-table">${escapeHtml(student.nama.charAt(0).toUpperCase())}</span><span><b>${escapeHtml(student.nama)}</b><small>${escapeHtml(student.username)}</small></span></div></td>
        <td>${escapeHtml(student.nim)}</td>
        <td>${escapeHtml(student.program_studi)}</td>
        <td><span class="semester-pill">${escapeHtml(student.semester)}</span></td>
        <td><div class="row-actions"><button class="text-button edit-student" type="button" data-id="${Number(student.id)}">Edit</button><button class="text-button delete-button delete-student" type="button" data-id="${Number(student.id)}">Hapus</button></div></td>
      </tr>`).join("");
  }

  function resetStudentForm() {
    const form = document.querySelector("#student-form");
    form.reset();
    form.elements.id.value = "";
    form.elements.password.required = true;
    document.querySelector("#student-submit").textContent = "Tambah mahasiswa";
    document.querySelector("#student-cancel").hidden = true;
    document.querySelector("#password-hint").textContent = "(min. 10 karakter)";
  }

  async function initAdmin() {
    const message = document.querySelector("#admin-message");
    try {
      const result = await api("dashboard");
      csrfToken = result.csrf_token;
      students = result.students;
      document.querySelector("#user-name").textContent = result.user.username;
      document.querySelector("#stat-students").textContent = result.stats.mahasiswa;
      document.querySelector("#stat-programs").textContent = result.stats.program_studi;
      document.querySelector("#stat-semester").textContent = result.stats.semester_aktif || "—";
      renderStudents();
    } catch (exception) {
      if (exception.message.startsWith("Sesi berakhir")) window.location.replace("login.html");
      else showMessage(document.querySelector("#page-error"), exception.message, true);
    }

    document.querySelector("#student-form").addEventListener("submit", async (event) => {
      event.preventDefault();
      message.hidden = true;
      const form = event.currentTarget;
      if (!form.reportValidity()) return;
      const data = Object.fromEntries(new FormData(form));
      if (data.id && !data.password) delete data.password;
      try {
        const result = await api("student_save", {
          method: "POST", body: JSON.stringify(data), csrf: true
        });
        showMessage(message, result.message);
        resetStudentForm();
        const refreshed = await api("dashboard");
        students = refreshed.students;
        document.querySelector("#stat-students").textContent = refreshed.stats.mahasiswa;
        document.querySelector("#stat-programs").textContent = refreshed.stats.program_studi;
        document.querySelector("#stat-semester").textContent = refreshed.stats.semester_aktif || "—";
        renderStudents();
      } catch (exception) {
        showMessage(message, exception.message, true);
      }
    });

    document.querySelector("#student-cancel").addEventListener("click", resetStudentForm);
    document.querySelector("#student-rows").addEventListener("click", async (event) => {
      const button = event.target.closest("button[data-id]");
      if (!button) return;
      const student = students.find((item) => Number(item.id) === Number(button.dataset.id));
      if (!student) return;
      if (button.classList.contains("edit-student")) {
        const form = document.querySelector("#student-form");
        for (const field of ["id", "username", "nim", "nama", "program_studi", "semester"]) {
          form.elements[field].value = student[field] ?? student.id;
        }
        form.elements.password.value = "";
        form.elements.password.required = false;
        document.querySelector("#student-submit").textContent = "Simpan perubahan";
        document.querySelector("#student-cancel").hidden = false;
        document.querySelector("#password-hint").textContent = "(kosongkan jika tidak diubah)";
        form.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      if (!window.confirm(`Hapus data ${student.nama}? Akun mahasiswa dan data akademik terkait juga akan dihapus.`)) return;
      try {
        const result = await api("student_delete", {
          method: "POST", body: JSON.stringify({ id: student.id }), csrf: true
        });
        showMessage(message, result.message);
        const refreshed = await api("dashboard");
        students = refreshed.students;
        document.querySelector("#stat-students").textContent = refreshed.stats.mahasiswa;
        document.querySelector("#stat-programs").textContent = refreshed.stats.program_studi;
        document.querySelector("#stat-semester").textContent = refreshed.stats.semester_aktif || "—";
        renderStudents();
      } catch (exception) {
        showMessage(message, exception.message, true);
      }
    });
  }

  function renderSchedule(schedule) {
    const tbody = document.querySelector("#schedule-rows");
    if (!schedule.length) {
      tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Belum ada jadwal kuliah.</td></tr>';
      return;
    }
    tbody.innerHTML = schedule.map((item) => `
      <tr><td><b>${escapeHtml(item.nama_mk)}</b><small class="table-sub">${escapeHtml(item.kode_mk)} · ${escapeHtml(item.sks)} SKS</small></td>
      <td>${escapeHtml(item.hari)}<small class="table-sub">${escapeHtml(item.jam_mulai.slice(0, 5))}–${escapeHtml(item.jam_selesai.slice(0, 5))}</small></td>
      <td>${escapeHtml(item.ruangan)}</td></tr>`).join("");
  }

  function renderGrades(grades) {
    const container = document.querySelector("#grade-list");
    if (!grades.length) {
      container.innerHTML = '<p class="empty-state">Belum ada nilai yang diterbitkan.</p>';
      return;
    }
    container.innerHTML = grades.map((grade) => {
      const score = Number(grade.nilai_angka);
      const label = score >= 85 ? "A" : score >= 75 ? "B" : score >= 65 ? "C" : "D";
      return `<article class="grade-item"><div class="grade-mark">${label}</div><div class="grade-copy"><b>${escapeHtml(grade.nama_mk)}</b><small>${escapeHtml(grade.kode_mk)} · Semester ${escapeHtml(grade.semester)}</small></div><strong>${escapeHtml(score.toFixed(1))}</strong></article>`;
    }).join("");
  }

  async function initStudent() {
    try {
      const result = await api("dashboard");
      csrfToken = result.csrf_token;
      const profile = result.profile;
      document.querySelector("#user-name").textContent = profile.nama;
      document.querySelector("#user-avatar").textContent = profile.nama.charAt(0).toUpperCase();
      document.querySelector("#greeting-name").textContent = profile.nama.split(" ")[0];
      document.querySelector("#profile-name").textContent = profile.nama;
      document.querySelector("#profile-program").textContent = profile.program_studi;
      document.querySelector("#profile-nim").textContent = `NIM ${profile.nim}`;
      document.querySelector("#profile-semester").textContent = `Semester ${profile.semester}`;
      renderSchedule(result.schedule);
      renderGrades(result.grades);
    } catch (exception) {
      if (exception.message.startsWith("Sesi berakhir")) window.location.replace("login.html");
      else showMessage(document.querySelector("#page-error"), exception.message, true);
    }
  }

  async function logout() {
    try {
      await api("logout", { method: "POST", body: JSON.stringify({}), csrf: true });
      window.location.replace("login.html");
    } catch (exception) {
      showMessage(document.querySelector("#page-error"), exception.message, true);
    }
  }

  document.querySelectorAll(".logout-button").forEach((button) => button.addEventListener("click", logout));
  document.querySelector(".mobile-menu")?.addEventListener("click", () => {
    document.querySelector(".sidebar").classList.toggle("sidebar-open");
  });
  setDate();
  if (page === "login") initLogin();
  if (page === "admin") initAdmin();
  if (page === "student") initStudent();
})();
