// public/assets/js/app.js
// SMASHPOINT Interactive Client Engine

const SMASH = {
  // Toast Notification System
  toast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bg = type === 'error' ? 'bg-red-950/90 border-red-500/50 text-red-200' : 'bg-slate-900/95 border-emerald-500/50 text-emerald-200';
    toast.className = `p-4 rounded-xl border shadow-xl backdrop-blur-md transition-all duration-300 transform translate-y-4 opacity-0 pointer-events-auto flex items-center gap-3 ${bg}`;
    toast.innerHTML = `
      <div class="w-2 h-2 rounded-full ${type === 'error' ? 'bg-red-400' : 'bg-emerald-400'} animate-pulse"></div>
      <div class="text-sm font-medium">${message}</div>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => {
      toast.classList.remove('translate-y-4', 'opacity-0');
    });

    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-y-2');
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  // Scorekeeper + and - buttons
  adjustScore(inputSelector, delta) {
    const input = document.querySelector(inputSelector);
    if (!input) return;
    let val = parseInt(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val;
  },

  // Save Match Score
  async saveMatchScore(btn, matchId) {
    const card = btn.closest('.match-card');
    const inputA = card.querySelector(`input[name="score_a_${matchId}"]`);
    const inputB = card.querySelector(`input[name="score_b_${matchId}"]`);

    const scoreA = parseInt(inputA.value) || 0;
    const scoreB = parseInt(inputB.value) || 0;

    if (scoreA === 0 && scoreB === 0) {
      this.toast('Masukkan skor pertandingan sebelum menyimpan.', 'error');
      return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Menyimpan...`;

    try {
      const fd = new FormData();
      fd.append('match_id', matchId);
      fd.append('score_a', scoreA);
      fd.append('score_b', scoreB);

      const res = await fetch('index.php?page=api&action=save_score', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        this.toast(data.message, 'success');
        btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-400');
        btn.classList.add('bg-slate-700', 'text-slate-300');
        btn.innerHTML = `✓ Tersimpan`;
        setTimeout(() => location.reload(), 1200);
      } else {
        throw new Error(data.error?.message || 'Gagal menyimpan skor.');
      }
    } catch (e) {
      this.toast(e.message, 'error');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  },

  // Generate Next Round
  async generateRound(sessionId) {
    const courtCount = document.getElementById('court-count-select')?.value || 2;
    const algorithm = document.getElementById('algorithm-select')?.value || 'fair_social';
    const btn = document.getElementById('btn-generate-round');

    if (!confirm(`Generate ronde baru dengan algoritma '${algorithm}' di ${courtCount} lapangan?`)) {
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span class="inline-block animate-spin mr-1">⚡</span> Mengalkulasi rotasi...`;
    }

    try {
      const fd = new FormData();
      fd.append('session_id', sessionId);
      fd.append('court_count', courtCount);
      fd.append('algorithm', algorithm);

      const res = await fetch('index.php?page=api&action=generate_round', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        this.toast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        throw new Error(data.error?.message || 'Gagal generate ronde baru.');
      }
    } catch (e) {
      this.toast(e.message, 'error');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `⚡ Generate Ronde Baru`;
      }
    }
  },

  // Toggle Player Attendance
  async toggleAttendance(sessionId, playerId) {
    try {
      const fd = new FormData();
      fd.append('session_id', sessionId);
      fd.append('player_id', playerId);

      const res = await fetch('index.php?page=api&action=toggle_attendance', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();
      if (data.success) {
        this.toast(data.message);
        setTimeout(() => location.reload(), 600);
      } else {
        throw new Error(data.error?.message || 'Gagal mengubah status');
      }
    } catch (e) {
      this.toast(e.message, 'error');
    }
  },

  // RSVP Form Handler
  async submitRSVP(event, form) {
    event.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Mendaftarkan...`;

    try {
      const fd = new FormData(form);
      const res = await fetch('index.php?page=api&action=rsvp', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        this.toast(data.message, 'success');
        form.reset();
        setTimeout(() => {
          SMASH.closeModal('rsvp-modal');
          location.reload();
        }, 1200);
      } else {
        throw new Error(data.error?.message || 'Gagal mendaftar slot.');
      }
    } catch (e) {
      this.toast(e.message, 'error');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  },

  // Quick Add Player at Court
  async submitQuickAddPlayer(event, form) {
    event.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `Menambahkan...`;

    try {
      const fd = new FormData(form);
      const res = await fetch('index.php?page=api&action=quick_add_player', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        this.toast(data.message, 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        throw new Error(data.error?.message || 'Gagal menambah pemain.');
      }
    } catch (e) {
      this.toast(e.message, 'error');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  },

  // TV / Projector Display Mode Toggle
  toggleTVMode() {
    const isTV = document.body.classList.toggle('tv-mode');
    if (isTV) {
      if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
      }
      this.toast('Mode GOR TV / Projector Aktif. Tekan ESC untuk keluar.', 'success');
    } else {
      if (document.exitFullscreen && document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
      }
    }
  },

  // Modal Control
  openModal(modalId, sessionId = null) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (sessionId) {
      const sessInput = modal.querySelector('input[name="session_id"]');
      if (sessInput) sessInput.value = sessionId;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
};

// Global escape listener for TV mode & modals
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay').forEach(m => {
      m.classList.add('hidden');
      m.classList.remove('flex');
    });
    if (document.body.classList.contains('tv-mode')) {
      document.body.classList.remove('tv-mode');
    }
  }
});
