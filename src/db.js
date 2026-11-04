// src/db.js
// In-memory data store for Powershoot Badminton Management System

export const db = {
  settings: {
    id: 1,
    community_name: 'Powershoot Badminton Club',
    tagline: 'Komunitas Badminton Kompetitif & Mabar Terorganisir Tanpa Catat Manual',
    description: 'Powershoot Badminton adalah komunitas bulutangkis aktif di Jakarta Selatan yang mengadakan sesi main bareng 3-4 kali seminggu di berbagai GOR terbaik. Dilengkapi sistem rotasi lapangan otomatis, akumulasi poin leaderboard, dan manajemen iuran transparan.',
    instagram: '@powershoot.badminton',
    whatsapp_default: '081299887766',
    base_city: 'Jakarta Selatan',
    established_year: 2023,
    announcement_text: 'Komunitas Badminton Resmi Jakarta Selatan (Est. 2023)',
    announcement_subtext: 'Slot Dibuka Setiap Pekan',
    hero_title: 'Sistem Operasional Mabar & Rotasi Lapangan Badminton Terorganisir',
    hero_subtitle: 'Tanpa kertas, tanpa rebutan giliran main. Sistem mengacak pasangan ganda seimbang, mencatat total poin leaderboard, dan merekap iuran lapangan secara real-time.'
  },

  schedules: [
    {
      id: 1,
      title: 'Branch 1: Mabar Rutin Rabu Malam (Pancoran)',
      day_name: 'Rabu Malam',
      branch_area: 'Pancoran, Jakarta Selatan',
      schedule_date: '2026-11-04',
      time_start: '19:00',
      time_end: '22:00',
      venue_name: 'GOR Bintang Badminton Arena (Court 1 & 2)',
      venue_maps_url: 'https://maps.google.com/?q=GOR+Bintang+Badminton+Pancoran',
      court_count: 2,
      fee_per_person: 35000,
      max_slots: 16,
      pic_name: 'Kapten Dimas (PIC Pancoran)',
      pic_whatsapp: '081299887701',
      status: 'open',
      facilities: 'Shuttlecock Bulu Angsa, Karpet Standar BWF, Parkir Luas',
      venue_address: 'Jl. Pancoran Barat No. 12, Jakarta Selatan',
      bank_info: 'BCA 1234567890 a.n Dimas Powershoot',
      notes: 'Shuttlecock Bulu Angsa Berkualitas disediakan. Pembagian lapangan otomatis & skor masuk leaderboard.'
    },
    {
      id: 2,
      title: 'Branch 2: Mabar Intensif Jumat Malam (Fatmawati)',
      day_name: 'Jumat Malam',
      branch_area: 'Fatmawati, Jakarta Selatan',
      schedule_date: '2026-11-06',
      time_start: '20:00',
      time_end: '23:00',
      venue_name: 'GOR Master Sport Arena (Court 3, 4 & 5)',
      venue_maps_url: 'https://maps.google.com/?q=GOR+Master+Sport+Fatmawati',
      court_count: 3,
      fee_per_person: 40000,
      max_slots: 24,
      pic_name: 'Coach Ryan (PIC Fatmawati)',
      pic_whatsapp: '081299887702',
      status: 'open',
      facilities: '3 Lapangan Karpet Tebal, Shower Air Panas, Kantin',
      venue_address: 'Jl. RS Fatmawati No. 45, Cilandak, Jakarta Selatan',
      bank_info: 'BCA 2345678901 a.n Ryan Powershoot',
      notes: '3 Lapangan karpet tebal. Format rotasi bertingkat Mexicano Ladder.'
    },
    {
      id: 3,
      title: 'Branch 3: Weekend Morning Smash (Tebet)',
      day_name: 'Sabtu Pagi',
      branch_area: 'Tebet, Jakarta Selatan',
      schedule_date: '2026-11-07',
      time_start: '07:00',
      time_end: '10:00',
      venue_name: 'GOR Cempaka Court (Court A & B)',
      venue_maps_url: 'https://maps.google.com/?q=GOR+Cempaka+Tebet',
      court_count: 2,
      fee_per_person: 35000,
      max_slots: 16,
      pic_name: 'Bagas (PIC Tebet)',
      pic_whatsapp: '081299887703',
      status: 'open',
      facilities: 'Minuman Isotonik Dingin, Shuttlecock Speed 77',
      venue_address: 'Jl. Tebet Barat Dalam Raya No. 8, Jakarta Selatan',
      bank_info: 'Mandiri 137001234567 a.n Bagas Powershoot',
      notes: 'Sesi pagi bugar. Minuman isotonik gratis untuk peserta yang hadir on-time.'
    },
    {
      id: 9,
      title: 'Main Court Badminton (Senen Night Session)',
      day_name: 'Sabtu Malam',
      branch_area: 'Senen, Jakarta Pusat',
      schedule_date: '2026-11-07',
      time_start: '20:00',
      time_end: '22:00',
      venue_name: 'Main Court Badminton Plaza Kenari Mas',
      venue_maps_url: 'https://www.google.com/maps/search/?api=1&query=Plaza%20Kenari%20Mas%20Jalan%20Kramat%20Raya%20No.%20101%2C%20RW%2007%2C%20Senen%2C%20Jakarta%2C%20Java',
      court_count: 3,
      fee_per_person: 40000,
      max_slots: 24,
      pic_name: 'Yizreel',
      pic_whatsapp: '082125051665',
      status: 'in_progress',
      facilities: 'JP Gold, Lapangan 1 - 3, AC Courtside',
      venue_address: 'Jalan Kramat Raya No. 101, RW 07, Senen, Jakarta Pusat',
      bank_info: 'BCA 3350280374 a.n Dewi Putri',
      notes: 'Rotasi dinamis Fair Social. Konfirmasi pembatalan slot maksimal H-1.'
    }
  ],

  players: [
    { id: 1, name: 'Capt. Hendra', phone: '0812990011', level: 'Advance', total_matches: 48, wins: 38, losses: 10, total_points: 986, points_conceded: 710, streak: 5, is_active: 1 },
    { id: 2, name: 'Kevin Sanjaya', phone: '0812990012', level: 'Advance', total_matches: 42, wins: 34, losses: 8, total_points: 874, points_conceded: 640, streak: 4, is_active: 1 },
    { id: 3, name: 'Fajar Alfian', phone: '0812990013', level: 'Advance', total_matches: 39, wins: 30, losses: 9, total_points: 812, points_conceded: 635, streak: 2, is_active: 1 },
    { id: 4, name: 'Coach Ryan', phone: '0812990014', level: 'Advance', total_matches: 36, wins: 28, losses: 8, total_points: 750, points_conceded: 580, streak: 3, is_active: 1 },
    { id: 37, name: 'Dori', phone: '0813000001', level: 'Intermediate', total_matches: 24, wins: 18, losses: 6, total_points: 498, points_conceded: 410, streak: 3, is_active: 1 },
    { id: 38, name: 'Alv', phone: '0813000002', level: 'Intermediate', total_matches: 22, wins: 15, losses: 7, total_points: 452, points_conceded: 395, streak: 1, is_active: 1 },
    { id: 39, name: 'Wikan', phone: '0813000003', level: 'Intermediate', total_matches: 20, wins: 13, losses: 7, total_points: 410, points_conceded: 370, streak: -1, is_active: 1 },
    { id: 40, name: 'Hilmy', phone: '0813000004', level: 'Intermediate', total_matches: 19, wins: 14, losses: 5, total_points: 395, points_conceded: 330, streak: 2, is_active: 1 },
    { id: 41, name: 'Fadil', phone: '0813000005', level: 'Intermediate', total_matches: 18, wins: 11, losses: 7, total_points: 372, points_conceded: 340, streak: 1, is_active: 1 },
    { id: 42, name: 'Ali', phone: '0813000006', level: 'Intermediate', total_matches: 17, wins: 10, losses: 7, total_points: 350, points_conceded: 335, streak: -1, is_active: 1 },
    { id: 43, name: 'Setiawan', phone: '0813000007', level: 'Intermediate', total_matches: 16, wins: 9, losses: 7, total_points: 326, points_conceded: 310, streak: 1, is_active: 1 },
    { id: 44, name: 'Tristan (Tr)', phone: '0813000008', level: 'Intermediate', total_matches: 15, wins: 8, losses: 7, total_points: 305, points_conceded: 298, streak: -1, is_active: 1 },
    { id: 45, name: 'Bagas', phone: '0813000009', level: 'Intermediate', total_matches: 14, wins: 7, losses: 7, total_points: 288, points_conceded: 280, streak: 1, is_active: 1 },
    { id: 46, name: 'Rian Ardi', phone: '0813000010', level: 'Beginner', total_matches: 12, wins: 5, losses: 7, total_points: 236, points_conceded: 245, streak: -2, is_active: 1 },
    { id: 47, name: 'Dewi Putri', phone: '0813000011', level: 'Beginner', total_matches: 10, wins: 4, losses: 6, total_points: 198, points_conceded: 210, streak: 1, is_active: 1 },
    { id: 48, name: 'Yizreel', phone: '082125051665', level: 'Intermediate', total_matches: 15, wins: 9, losses: 6, total_points: 310, points_conceded: 285, streak: 2, is_active: 1 }
  ],

  // session_participants linking session_id to player_id with payment_status
  session_participants: [
    { id: 86, session_id: 9, player_id: 37, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 12:00:00', is_present: 1, bench_count: 0 },
    { id: 87, session_id: 9, player_id: 38, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 12:15:00', is_present: 1, bench_count: 0 },
    { id: 88, session_id: 9, player_id: 39, payment_status: 'unpaid', payment_amount: 40000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 89, session_id: 9, player_id: 40, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 13:00:00', is_present: 1, bench_count: 0 },
    { id: 90, session_id: 9, player_id: 41, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 13:10:00', is_present: 1, bench_count: 0 },
    { id: 91, session_id: 9, player_id: 42, payment_status: 'unpaid', payment_amount: 40000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 92, session_id: 9, player_id: 43, payment_status: 'unpaid', payment_amount: 40000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 93, session_id: 9, player_id: 44, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 14:00:00', is_present: 1, bench_count: 0 },
    { id: 94, session_id: 9, player_id: 45, payment_status: 'unpaid', payment_amount: 40000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 95, session_id: 9, player_id: 46, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 14:30:00', is_present: 1, bench_count: 0 },
    { id: 96, session_id: 9, player_id: 47, payment_status: 'unpaid', payment_amount: 40000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 97, session_id: 9, player_id: 48, payment_status: 'paid', payment_amount: 40000, paid_at: '2026-11-03 15:00:00', is_present: 1, bench_count: 0 },

    // Attach participants for session 1 as well
    { id: 101, session_id: 1, player_id: 1, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 10:00:00', is_present: 1, bench_count: 0 },
    { id: 102, session_id: 1, player_id: 2, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 10:10:00', is_present: 1, bench_count: 0 },
    { id: 103, session_id: 1, player_id: 3, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 10:20:00', is_present: 1, bench_count: 0 },
    { id: 104, session_id: 1, player_id: 4, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 10:30:00', is_present: 1, bench_count: 0 },
    { id: 105, session_id: 1, player_id: 37, payment_status: 'unpaid', payment_amount: 35000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 106, session_id: 1, player_id: 38, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 11:00:00', is_present: 1, bench_count: 0 },
    { id: 107, session_id: 1, player_id: 39, payment_status: 'unpaid', payment_amount: 35000, paid_at: null, is_present: 1, bench_count: 0 },
    { id: 108, session_id: 1, player_id: 40, payment_status: 'paid', payment_amount: 35000, paid_at: '2026-11-03 11:15:00', is_present: 1, bench_count: 0 }
  ],

  matches: [
    {
      id: 1,
      session_id: 9,
      round_number: 1,
      court_number: 1,
      team_a_p1: 37,
      team_a_p2: 38,
      team_b_p1: 39,
      team_b_p2: 40,
      score_a: 21,
      score_b: 19,
      winner_team: 'team_a',
      status: 'completed',
      completed_at: '2026-11-03 20:25:00'
    },
    {
      id: 2,
      session_id: 9,
      round_number: 1,
      court_number: 2,
      team_a_p1: 41,
      team_a_p2: 42,
      team_b_p1: 43,
      team_b_p2: 44,
      score_a: 18,
      score_b: 21,
      winner_team: 'team_b',
      status: 'completed',
      completed_at: '2026-11-03 20:28:00'
    },
    {
      id: 3,
      session_id: 9,
      round_number: 2,
      court_number: 1,
      team_a_p1: 37,
      team_a_p2: 40,
      team_b_p1: 41,
      team_b_p2: 44,
      score_a: 15,
      score_b: 12,
      winner_team: 'none',
      status: 'playing',
      completed_at: null
    },
    {
      id: 4,
      session_id: 9,
      round_number: 2,
      court_number: 2,
      team_a_p1: 38,
      team_a_p2: 43,
      team_b_p1: 39,
      team_b_p2: 42,
      score_a: 11,
      score_b: 14,
      winner_team: 'none',
      status: 'playing',
      completed_at: null
    }
  ],

  gallery: [
    {
      id: 1,
      title: 'Dokumentasi Mabar Rabu Malam Pancoran',
      image_url: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',
      caption: 'Mabar seru 16 pemain di GOR Bintang Arena',
      display_order: 1,
      created_at: '2026-11-02'
    },
    {
      id: 2,
      title: 'Sesi Pagi Bugar Tebet',
      image_url: 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80',
      caption: 'Pemanasan dan rotasi ganda di GOR Cempaka',
      display_order: 2,
      created_at: '2026-11-01'
    },
    {
      id: 3,
      title: 'Internal Fun Tournament Musim 1',
      image_url: 'https://images.unsplash.com/photo-1599586120429-48281b6f0ece?auto=format&fit=crop&w=1200&q=80',
      caption: 'Penyerahan medali dan raket untuk MVP',
      display_order: 3,
      created_at: '2026-10-28'
    },
    {
      id: 4,
      title: 'Foto Bersama Komunitas Powershoot',
      image_url: 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=80',
      caption: 'Kompak dan menjunjung tinggi sportivitas di lapangan',
      display_order: 4,
      created_at: '2026-10-25'
    }
  ],

  articles: [
    {
      id: 1,
      title: 'Tata Tertib Mabar & Standar Shuttlecock Powershoot 2026',
      slug: 'tata-tertib-mabar-standar-shuttlecock-2026',
      category: 'Aturan Lapangan',
      excerpt: 'Wajib sepatu sol karet non-marking, shuttlecock Speed 77 bulu angsa, dan konfirmasi kehadiran H-4 jam.',
      content: `Demi kenyamanan bersama seluruh anggota komunitas dalam setiap sesi mabar mingguan:\n\n1. **Sepatu Lapangan:** Wajib menggunakan sepatu khusus badminton dengan outsole karet non-marking demi menjaga karpet lapangan dan mencegah cedera lutut.\n2. **Shuttlecock Resmi:** Menggunakan shuttlecock bulu angsa resmi Speed 77 yang telah teruji kestabilan putarannya.\n3. **Pembayaran Iuran:** Harap melunasi iuran kok via QRIS sebelum sesi dimulai atau tunai kepada PIC di lokasi GOR.`,
      cover_image: 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80',
      created_at: '2026-11-02'
    },
    {
      id: 2,
      title: 'Sistem Akumulasi Poin Leaderboard: Mengapa Setiap Poin Lapangan Berharga',
      slug: 'sistem-akumulasi-poin-leaderboard',
      category: 'Sistem Poin',
      excerpt: 'Di Powershoot, peringkat tidak hanya dihitung dari menang/kalah, melainkan akumulasi poin nyata yang dicetak di setiap game!',
      content: `Dalam sistem Powershoot Badminton Management, setiap poin yang kamu cetak dalam pertandingan langsung diakumulasikan ke profilmu.\n\nMeskipun kalah dengan skor tipis 19-21, kamu tetap mendapatkan 19 poin untuk mendongkrak posisimu di Leaderboard Hall of Fame Komunitas. Jadi, jangan pernah menyerah di setiap reli shuttlecock!`,
      cover_image: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',
      created_at: '2026-11-02'
    }
  ],

  admins: [
    { id: 1, username: 'admin', password: 'admin123', name: 'Captain Powershoot', role: 'admin' },
    { id: 2, username: 'admin', password: 'smashpoint123', name: 'Captain Powershoot', role: 'admin' },
    { id: 3, username: 'pic_yiz', password: 'password123', name: 'Yizreel', role: 'pic' }
  ]
};

// Database Query Helpers
export function getSettings() {
  return db.settings;
}

export function updateSettings(updates) {
  Object.assign(db.settings, updates);
  return db.settings;
}

export function getSchedules() {
  return [...db.schedules].sort((a, b) => new Date(a.schedule_date) - new Date(b.schedule_date));
}

export function getSchedule(id) {
  const sid = Number(id);
  return db.schedules.find(s => s.id === sid) || null;
}

export function addSchedule(data) {
  const newId = (db.schedules.length ? Math.max(...db.schedules.map(s => s.id)) : 0) + 1;
  const newSched = {
    id: newId,
    title: data.title || 'Mabar Powershoot',
    day_name: data.day_name || 'Sabtu Malam',
    branch_area: data.branch_area || 'Jakarta Selatan',
    schedule_date: data.schedule_date || new Date().toISOString().slice(0, 10),
    time_start: data.time_start || '19:00',
    time_end: data.time_end || '22:00',
    venue_name: data.venue_name || 'GOR Badminton Arena',
    venue_maps_url: data.venue_maps_url || 'https://maps.google.com',
    court_count: Number(data.court_count) || 2,
    fee_per_person: Number(data.fee_per_person) || 35000,
    max_slots: Number(data.max_slots) || 16,
    pic_name: data.pic_name || 'Kapten Powershoot',
    pic_whatsapp: data.pic_whatsapp || '081299887766',
    status: data.status || 'open',
    facilities: data.facilities || 'Shuttlecock Bulu Angsa',
    venue_address: data.venue_address || 'Jakarta',
    bank_info: data.bank_info || 'BCA 1234567890',
    notes: data.notes || ''
  };
  db.schedules.push(newSched);
  return newSched;
}

export function getPlayers() {
  return [...db.players].filter(p => p.is_active);
}

export function getPlayer(id) {
  const pid = Number(id);
  return db.players.find(p => p.id === pid) || null;
}

export function addPlayer(data) {
  const newId = (db.players.length ? Math.max(...db.players.map(p => p.id)) : 0) + 1;
  const newPlayer = {
    id: newId,
    name: data.name.trim(),
    phone: data.phone || null,
    level: data.level || 'Intermediate',
    total_matches: 0,
    wins: 0,
    losses: 0,
    total_points: 0,
    points_conceded: 0,
    streak: 0,
    is_active: 1
  };
  db.players.push(newPlayer);
  return newPlayer;
}

export function getParticipants(sessionId) {
  const sid = Number(sessionId);
  const parts = db.session_participants.filter(p => p.session_id === sid);
  return parts.map(pt => {
    const player = db.players.find(pl => pl.id === pt.player_id) || { name: 'Player ' + pt.player_id, level: 'Intermediate' };
    return {
      ...pt,
      name: player.name,
      level: player.level,
      phone: player.phone,
      total_points: player.total_points
    };
  });
}

export function togglePayment(sessionId, playerId) {
  const sid = Number(sessionId);
  const pid = Number(playerId);
  let pt = db.session_participants.find(p => p.session_id === sid && p.player_id === pid);
  if (!pt) {
    const sched = getSchedule(sid);
    const fee = sched ? sched.fee_per_person : 35000;
    pt = {
      id: Date.now(),
      session_id: sid,
      player_id: pid,
      payment_status: 'paid',
      payment_amount: fee,
      paid_at: new Date().toISOString(),
      is_present: 1,
      bench_count: 0
    };
    db.session_participants.push(pt);
    return pt;
  }

  pt.payment_status = pt.payment_status === 'paid' ? 'unpaid' : 'paid';
  pt.paid_at = pt.payment_status === 'paid' ? new Date().toISOString() : null;
  return pt;
}

export function togglePresent(sessionId, playerId) {
  const sid = Number(sessionId);
  const pid = Number(playerId);
  const pt = db.session_participants.find(p => p.session_id === sid && p.player_id === pid);
  if (pt) {
    pt.is_present = pt.is_present === 1 ? 0 : 1;
    return pt;
  }
  return null;
}

export function addParticipantToSession(sessionId, playerId, paymentStatus = 'unpaid') {
  const sid = Number(sessionId);
  const pid = Number(playerId);
  const existing = db.session_participants.find(p => p.session_id === sid && p.player_id === pid);
  if (existing) return existing;

  const sched = getSchedule(sid);
  const fee = sched ? sched.fee_per_person : 35000;
  const newPart = {
    id: (db.session_participants.length ? Math.max(...db.session_participants.map(p => p.id)) : 0) + 1,
    session_id: sid,
    player_id: pid,
    payment_status: paymentStatus,
    payment_amount: fee,
    paid_at: paymentStatus === 'paid' ? new Date().toISOString() : null,
    is_present: 1,
    bench_count: 0
  };
  db.session_participants.push(newPart);
  return newPart;
}

export function getMatches(sessionId) {
  const sid = Number(sessionId);
  const mList = db.matches.filter(m => m.session_id === sid);
  return mList.map(m => {
    const p1A = db.players.find(p => p.id === m.team_a_p1)?.name || 'P1';
    const p2A = db.players.find(p => p.id === m.team_a_p2)?.name || 'P2';
    const p1B = db.players.find(p => p.id === m.team_b_p1)?.name || 'P3';
    const p2B = db.players.find(p => p.id === m.team_b_p2)?.name || 'P4';
    return {
      ...m,
      team_a_p1_name: p1A,
      team_a_p2_name: p2A,
      team_b_p1_name: p1B,
      team_b_p2_name: p2B
    };
  });
}

export function saveScore(matchId, scoreA, scoreB) {
  const mid = Number(matchId);
  const match = db.matches.find(m => m.id === mid);
  if (!match) return null;

  const sA = Number(scoreA);
  const sB = Number(scoreB);
  match.score_a = sA;
  match.score_b = sB;
  match.status = 'completed';
  match.completed_at = new Date().toISOString();

  let winner = 'none';
  if (sA > sB) winner = 'team_a';
  else if (sB > sA) winner = 'team_b';
  else winner = 'draw';
  match.winner_team = winner;

  // Update players points & record
  const updatePlayerStats = (playerId, myScore, oppScore, isWin) => {
    const p = db.players.find(pl => pl.id === playerId);
    if (p) {
      p.total_matches += 1;
      p.total_points += myScore;
      p.points_conceded += oppScore;
      if (isWin) {
        p.wins += 1;
        p.streak = p.streak >= 0 ? p.streak + 1 : 1;
      } else {
        p.losses += 1;
        p.streak = p.streak <= 0 ? p.streak - 1 : -1;
      }
    }
  };

  const isWinA = winner === 'team_a';
  const isWinB = winner === 'team_b';

  updatePlayerStats(match.team_a_p1, sA, sB, isWinA);
  updatePlayerStats(match.team_a_p2, sA, sB, isWinA);
  updatePlayerStats(match.team_b_p1, sB, sA, isWinB);
  updatePlayerStats(match.team_b_p2, sB, sA, isWinB);

  return match;
}

export function addBatchMatches(sessionId, roundNumber, matches) {
  const sid = Number(sessionId);
  let nextId = (db.matches.length ? Math.max(...db.matches.map(m => m.id)) : 0) + 1;
  const created = [];
  matches.forEach(m => {
    const newM = {
      id: nextId++,
      session_id: sid,
      round_number: roundNumber,
      court_number: m.court_number,
      team_a_p1: m.team_a_p1,
      team_a_p2: m.team_a_p2,
      team_b_p1: m.team_b_p1,
      team_b_p2: m.team_b_p2,
      score_a: 0,
      score_b: 0,
      winner_team: 'none',
      status: 'playing',
      completed_at: null
    };
    db.matches.push(newM);
    created.push(newM);
  });
  return created;
}

export function getLeaderboard(limit = 25, sessionId = null) {
  let list = db.players.map(p => {
    const total = p.total_matches || 0;
    const winRate = total > 0 ? Math.round((p.wins / total) * 100) : 0;
    const pointDiff = (p.total_points || 0) - (p.points_conceded || 0);
    return {
      ...p,
      win_rate: winRate,
      point_diff: pointDiff
    };
  });

  list.sort((a, b) => {
    if (b.total_points !== a.total_points) return b.total_points - a.total_points;
    if (b.wins !== a.wins) return b.wins - a.wins;
    return b.win_rate - a.win_rate;
  });

  if (limit) {
    list = list.slice(0, limit);
  }
  return list;
}

export function getGallery() {
  return [...db.gallery].sort((a, b) => a.display_order - b.display_order);
}

export function getArticles() {
  return [...db.articles];
}

export function getArticle(slug) {
  return db.articles.find(a => a.slug === slug) || null;
}
