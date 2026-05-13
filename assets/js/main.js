document.addEventListener("DOMContentLoaded", function () {
  // auto dismiss alerts
  document.querySelectorAll(".alert").forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";
      setTimeout(function () {
        alert.remove();
      }, 500);
    }, 4000);
  });

  // toggle switch
  document.querySelectorAll(".toggle-sw").forEach(function (toggle) {
    toggle.addEventListener("click", function () {
      this.classList.toggle("on");
    });
  });

  // faq accordion
  document.querySelectorAll(".faq-item").forEach(function (item) {
    var question = item.querySelector(".faq-question");
    if (question) {
      question.addEventListener("click", function () {
        // close semua dulu
        document.querySelectorAll(".faq-item").forEach(function (other) {
          if (other !== item) other.classList.remove("open");
        });
        item.classList.toggle("open");
      });
    }
  });

  // auto scroll chat
  var chatArea = document.getElementById("chatArea");
  if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;

  // chat send on enter
  var chatInput = document.getElementById("chatInput");
  var chatForm = document.getElementById("chatForm");
  if (chatInput && chatForm) {
    chatInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        chatForm.submit();
      }
    });
  }

  // message send on enter
  var msgInput = document.getElementById("msgInput");
  var msgForm = document.getElementById("msgForm");
  if (msgInput && msgForm) {
    msgInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        msgForm.submit();
      }
    });
  }

  // file upload preview
  var fileInput = document.getElementById("fileInput");
  var fileNameShow = document.getElementById("fileName");
  if (fileInput && fileNameShow) {
    fileInput.addEventListener("change", function () {
      if (this.files[0]) {
        fileNameShow.textContent = "✓ " + this.files[0].name;
        fileNameShow.style.color = "var(--teal)";
      } else {
        fileNameShow.textContent = "";
      }
    });
  }

  // confirm delete
  document.querySelectorAll("[data-confirm]").forEach(function (el) {
    el.addEventListener("click", function (e) {
      if (!confirm(this.dataset.confirm || "Confirm?")) {
        e.preventDefault();
      }
    });
  });

  // active nav highlight
  var currentFile = window.location.pathname.split("/").pop();
  document.querySelectorAll(".nav-link").forEach(function (link) {
    var href = link.getAttribute("href") || "";
    if (href.split("/").pop() === currentFile && currentFile !== "") {
      link.classList.add("active");
    }
  });
});

// toggle form / div visibility
function toggleForm(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.style.display =
    el.style.display === "none" || el.style.display === "" ? "block" : "none";
}

function toggleDiv(id) {
  toggleForm(id);
}

// toast notification
function showToast(message, type) {
  type = type || "success";
  var colors = {
    success: "var(--success)",
    danger: "var(--danger)",
    info: "var(--accent-l)",
  };
  var toast = document.createElement("div");
  toast.style.cssText = [
    "position:fixed",
    "bottom:24px",
    "right:24px",
    "z-index:9999",
    "background:var(--bg-card)",
    "border:1px solid " + (colors[type] || colors.info),
    "color:" + (colors[type] || colors.info),
    "padding:12px 20px",
    "border-radius:10px",
    "font-size:13px",
    "font-family:inherit",
    "box-shadow:0 4px 20px rgba(0,0,0,0.4)",
  ].join(";");
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(function () {
    toast.style.transition = "opacity 0.4s";
    toast.style.opacity = "0";
    setTimeout(function () {
      toast.remove();
    }, 400);
  }, 3000);
}

// ai summarizer
async function runSummarizer() {
  var notesEl = document.getElementById("notesInput");
  var outputEl = document.getElementById("summaryOutput");
  var loadEl = document.getElementById("summaryLoading");
  var phEl = document.getElementById("summaryPlaceholder");
  var btn = document.getElementById("summarizeBtn");

  if (!notesEl) return;
  var notes = notesEl.value.trim();
  if (!notes) {
    showToast("Sila masukkan nota dulu!", "danger");
    return;
  }

  if (loadEl) loadEl.style.display = "block";
  if (outputEl) outputEl.style.display = "none";
  if (phEl) phEl.style.display = "none";
  if (btn) btn.disabled = true;

  try {
    var res = await fetch("api/summarize.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ notes: notes }),
    });
    var data = await res.json();
    if (data.error) throw new Error(data.error);

    renderSummary(data);
    if (outputEl) outputEl.style.display = "block";

    // save
    await fetch("api/save_summary.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ notes: notes, summary: data, title: data.title }),
    });
  } catch (err) {
    showToast("Gagal: " + err.message, "danger");
    if (phEl) phEl.style.display = "block";
  }

  if (loadEl) loadEl.style.display = "none";
  if (btn) btn.disabled = false;
}

function renderSummary(data) {
  var titleEl = document.getElementById("sumTitle");
  var diffEl = document.getElementById("sumDiff");
  var ptsEl = document.getElementById("sumPoints");
  var termsEl = document.getElementById("sumTerms");
  var kwEl = document.getElementById("keyTermsWrap");

  if (titleEl) titleEl.textContent = "✦ " + (data.title || "Summary");

  if (diffEl) {
    var diffMap = {
      Easy: "badge-success",
      Medium: "badge-amber",
      Hard: "badge-danger",
    };
    diffEl.className = "badge " + (diffMap[data.difficulty] || "badge-muted");
    diffEl.textContent = "Difficulty: " + (data.difficulty || "—");
  }

  if (ptsEl && data.points) {
    ptsEl.innerHTML = data.points
      .map(function (pt, i) {
        return (
          '<div class="flex-center gap-10" style="margin-bottom:10px;align-items:flex-start;">' +
          '<div style="width:20px;height:20px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--accent-l);flex-shrink:0;">' +
          (i + 1) +
          "</div>" +
          '<span style="font-size:13px;line-height:1.5;color:var(--text);">' +
          pt +
          "</span>" +
          "</div>"
        );
      })
      .join("");
  }

  if (termsEl && data.keyTerms) {
    termsEl.innerHTML = data.keyTerms
      .map(function (t) {
        return '<span class="badge badge-teal">' + t + "</span>";
      })
      .join("");
    if (kwEl) kwEl.style.display = data.keyTerms.length ? "" : "none";
  }
}
