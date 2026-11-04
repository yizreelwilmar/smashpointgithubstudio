// src/rotationEngine.js
// Powershoot Badminton Fair Court Rotation & Level-Balanced Matchmaking Engine

export class RotationEngine {
  static levelWeight(level) {
    const lvl = String(level || 'intermediate').toLowerCase().trim();
    if (lvl.includes('adv') || lvl.includes('mahir')) return 3;
    if (lvl.includes('beg') || lvl.includes('pemula')) return 1;
    return 2; // Intermediate
  }

  static generateRound(players, courtCount, pastMatches = [], algorithm = 'balanced_level') {
    const totalPlayers = players.length;
    if (totalPlayers < 4) {
      throw new Error("Minimal dibutuhkan 4 pemain hadir untuk rotasi ganda.");
    }

    const effectiveCourts = Math.min(courtCount, Math.floor(totalPlayers / 4));
    const effectiveSlots = effectiveCourts * 4;

    let currentRound = 1;
    if (pastMatches && pastMatches.length > 0) {
      let maxRound = 0;
      for (const m of pastMatches) {
        if (m.round_number > maxRound) {
          maxRound = Number(m.round_number);
        }
      }
      currentRound = maxRound + 1;
    }

    const workload = {};
    const partnerHistory = {};
    const opponentHistory = {};

    players.forEach(p => {
      workload[p.id] = {
        played: 0,
        rested: 0,
        session_points: 0,
        session_wins: 0
      };
      partnerHistory[p.id] = {};
      opponentHistory[p.id] = {};
    });

    const incPair = (table, a, b) => {
      if (!table[a]) table[a] = {};
      if (!table[b]) table[b] = {};
      table[a][b] = (table[a][b] || 0) + 1;
      table[b][a] = (table[b][a] || 0) + 1;
    };

    if (pastMatches && pastMatches.length > 0) {
      for (const m of pastMatches) {
        const tA = [m.team_a_p1, m.team_a_p2];
        const tB = [m.team_b_p1, m.team_b_p2];
        const allMatchPlayers = [...tA, ...tB];

        allMatchPlayers.forEach(pId => {
          if (workload[pId]) {
            workload[pId].played += 1;
          }
        });

        incPair(partnerHistory, tA[0], tA[1]);
        incPair(partnerHistory, tB[0], tB[1]);

        tA.forEach(pa => {
          tB.forEach(pb => {
            incPair(opponentHistory, pa, pb);
          });
        });

        if (m.status === 'completed') {
          const scoreA = Number(m.score_a) || 0;
          const scoreB = Number(m.score_b) || 0;
          if (workload[tA[0]]) workload[tA[0]].session_points += scoreA;
          if (workload[tA[1]]) workload[tA[1]].session_points += scoreA;
          if (workload[tB[0]]) workload[tB[0]].session_points += scoreB;
          if (workload[tB[1]]) workload[tB[1]].session_points += scoreB;

          if (m.winner_team === 'team_a') {
            if (workload[tA[0]]) workload[tA[0]].session_wins += 1;
            if (workload[tA[1]]) workload[tA[1]].session_wins += 1;
          } else if (m.winner_team === 'team_b') {
            if (workload[tB[0]]) workload[tB[0]].session_wins += 1;
            if (workload[tB[1]]) workload[tB[1]].session_wins += 1;
          }
        }
      }
    }

    // Prioritize players who have played the FEWEST matches (Fair social distribution)
    const playerPool = [...players].sort((a, b) => {
      const wA = workload[a.id]?.played || 0;
      const wB = workload[b.id]?.played || 0;
      if (wA !== wB) return wA - wB;
      // tiebreaker: random shuffle
      return Math.random() - 0.5;
    });

    const activePlayers = playerPool.slice(0, effectiveSlots);
    const restingPlayers = playerPool.slice(effectiveSlots);

    // Group active players into courts (4 per court)
    const matches = [];
    const usedActive = [...activePlayers];

    for (let c = 1; c <= effectiveCourts; c++) {
      const courtFour = usedActive.splice(0, 4);
      if (courtFour.length < 4) break;

      // Find best pairing among the 4 players to minimize prior partnerships and balance levels
      // Possible pairings of 4 players (0,1 vs 2,3), (0,2 vs 1,3), (0,3 vs 1,2)
      const pairCombos = [
        { teamA: [courtFour[0], courtFour[1]], teamB: [courtFour[2], courtFour[3]] },
        { teamA: [courtFour[0], courtFour[2]], teamB: [courtFour[1], courtFour[3]] },
        { teamA: [courtFour[0], courtFour[3]], teamB: [courtFour[1], courtFour[2]] }
      ];

      let bestCombo = pairCombos[0];
      let bestPenalty = Infinity;

      for (const combo of pairCombos) {
        const pHistoryA = partnerHistory[combo.teamA[0].id]?.[combo.teamA[1].id] || 0;
        const pHistoryB = partnerHistory[combo.teamB[0].id]?.[combo.teamB[1].id] || 0;
        const penaltyPartners = (pHistoryA + pHistoryB) * 100;

        const levelA = RotationEngine.levelWeight(combo.teamA[0].level) + RotationEngine.levelWeight(combo.teamA[1].level);
        const levelB = RotationEngine.levelWeight(combo.teamB[0].level) + RotationEngine.levelWeight(combo.teamB[1].level);
        const penaltyLevel = Math.abs(levelA - levelB) * 10;

        const totalPenalty = penaltyPartners + penaltyLevel;
        if (totalPenalty < bestPenalty) {
          bestPenalty = totalPenalty;
          bestCombo = combo;
        }
      }

      matches.push({
        court_number: c,
        round_number: currentRound,
        team_a_p1: bestCombo.teamA[0].id,
        team_a_p2: bestCombo.teamA[1].id,
        team_b_p1: bestCombo.teamB[0].id,
        team_b_p2: bestCombo.teamB[1].id,
        team_a_p1_name: bestCombo.teamA[0].name,
        team_a_p2_name: bestCombo.teamA[1].name,
        team_b_p1_name: bestCombo.teamB[0].name,
        team_b_p2_name: bestCombo.teamB[1].name,
        score_a: 0,
        score_b: 0,
        status: 'playing',
        winner_team: 'none'
      });
    }

    return {
      round_number: currentRound,
      court_count: effectiveCourts,
      matches,
      resting: restingPlayers.map(p => ({
        id: p.id,
        name: p.name,
        level: p.level,
        played: workload[p.id]?.played || 0
      }))
    };
  }
}
