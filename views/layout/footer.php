<?php
// views/layout/footer.php
?>
  </main>

  <!-- Public Footer -->
  <footer class="hide-in-tv border-t border-white/[0.08] bg-[#060a10] text-slate-400 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        
        <!-- Brand Info -->
        <div class="space-y-4 md:col-span-1">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-black font-black text-base">⚡</span>
            <span class="font-bold text-white tracking-tight font-display text-lg">SMASHPOINT</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">
            Platform mandiri (self-hosted) untuk rotasi lapangan adil, jadwal main bareng terintegrasi, dan papan peringkat kompetitif komunitas olahraga.
          </p>
          <div class="flex items-center gap-2 text-xs text-emerald-400/90 font-mono">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Self-Hosted via Laragon & MySQL</span>
          </div>
        </div>

        <!-- Navigation Links -->
        <div>
          <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-3">Fitur Komunitas</h4>
          <ul class="space-y-2 text-xs">
            <li><a href="index.php?page=schedule" class="hover:text-emerald-400 transition-colors">Jadwal Mabar Mingguan</a></li>
            <li><a href="index.php?page=court" class="hover:text-emerald-400 transition-colors">Court Rotation Engine</a></li>
            <li><a href="index.php?page=leaderboard" class="hover:text-emerald-400 transition-colors">Papan Peringkat & MVP</a></li>
            <li><a href="index.php?page=articles" class="hover:text-emerald-400 transition-colors">Berita & Panduan Bermain</a></li>
          </ul>
        </div>

        <!-- Court Modes -->
        <div>
          <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-3">Mode Algoritma</h4>
          <ul class="space-y-2 text-xs text-slate-400">
            <li><span class="text-slate-200 font-medium">Fair Social Doubles:</span> Pasangan acak bergilir & minim repeat.</li>
            <li><span class="text-slate-200 font-medium">Mexicano / Ladder:</span> Matchmaking berdasarkan skor ronde berjalan.</li>
            <li><span class="text-slate-200 font-medium">King of Court:</span> Pemenang naik ke Court 1, kalah turun.</li>
          </ul>
        </div>

        <!-- System & Admin -->
        <div>
          <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-3">Manajemen</h4>
          <p class="text-xs text-slate-400 mb-3">
            Admin dapat mengatur kuota sesi, menerbitkan artikel, dan mengelola keanggotaan.
          </p>
          <a href="index.php?page=admin" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/[0.05] hover:bg-white/[0.1] text-xs font-medium text-slate-300 hover:text-white border border-white/[0.1] transition-all">
            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
            <span>Login CMS Admin</span>
          </a>
        </div>

      </div>

      <div class="mt-8 pt-6 border-t border-white/[0.05] flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-3">
        <p>&copy; <?= date('Y') ?> SMASHPOINT. Dibuat khusus untuk komunitas bulutangkis, tenis & padel.</p>
        <p class="font-mono text-[11px]">Enterprise Craftmanship • Anti-Slop System</p>
      </div>
    </div>
  </footer>

  <!-- Lucide Icon Auto-render -->
  <script>
    if (window.lucide) {
      lucide.createIcons();
    }
  </script>

  <!-- Client App JS -->
  <script src="public/assets/js/app.js"></script>

</body>
</html>
