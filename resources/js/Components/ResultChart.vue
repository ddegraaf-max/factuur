<script setup>
import { computed, ref } from 'vue';
import { eur } from '@/format.js';

/**
 * Resultaat per maand: omzet- en inkoopbalken naast elkaar, met daaronder de
 * winst (of het verlies) per maand als balken vanaf een nullijn. Vorig jaar
 * loopt als gestippelde referentielijn door beide panelen mee.
 *
 * Kleuren zijn gevalideerd op kleurenblind-onderscheid (omzet-rood vs
 * inkoop-blauw); winst/verlies is dubbel gecodeerd: richting (boven/onder de
 * nullijn) én kleur.
 */
const props = defineProps({
  chart: Object, // { year, prev_year, months[12], has_prev, has_costs, totals }
});

const C = {
  revenue: '#E8231F',
  costs: '#2563EB',
  profit: '#15803D',
  loss: '#B81814',
  prev: '#78716C',
};

const months = computed(() => props.chart.months || []);

const isEmpty = computed(() =>
  !months.value.some(m => m.revenue || m.costs || m.prev_revenue || m.prev_profit)
);

/* ---------- Schalen ---------- */
// Ronde bovengrens: 1 / 2 / 2,5 / 5 × 10^n
const niceCeil = (v) => {
  if (v <= 0) return 1;
  const exp = Math.pow(10, Math.floor(Math.log10(v)));
  for (const s of [1, 2, 2.5, 5, 10]) {
    if (v <= s * exp) return s * exp;
  }
  return 10 * exp;
};

const yMax = computed(() => niceCeil(Math.max(
  1,
  ...months.value.map(m => Math.max(m.revenue, m.costs, props.chart.has_prev ? m.prev_revenue : 0)),
)));

const profitMax = computed(() => niceCeil(Math.max(
  1,
  ...months.value.map(m => Math.max(m.profit, props.chart.has_prev ? m.prev_profit : 0, 0)),
)));

const profitMin = computed(() => {
  const worst = Math.min(...months.value.map(m => Math.min(m.profit, props.chart.has_prev ? m.prev_profit : 0, 0)));
  return worst < 0 ? -niceCeil(-worst) : 0;
});

const profitRange = computed(() => profitMax.value - profitMin.value);
const zeroPct = computed(() => (profitMax.value / profitRange.value) * 100);

const barPct = (v) => Math.max((v / yMax.value) * 100, v > 0 ? 1 : 0);

const profitBar = (v) => {
  const h = (Math.abs(v) / profitRange.value) * 100;
  return v >= 0
    ? { top: (zeroPct.value - h) + '%', height: Math.max(h, v > 0 ? 1.5 : 0) + '%' }
    : { top: zeroPct.value + '%', height: Math.max(h, 1.5) + '%' };
};

/* ---------- Vorig jaar (stippellijnen) ---------- */
const linePoints = (get) => months.value
  .map((m, i) => `${((i + 0.5) / 12 * 100).toFixed(2)},${get(m).toFixed(2)}`)
  .join(' ');

const prevRevenueLine = computed(() =>
  linePoints(m => 100 - (m.prev_revenue / yMax.value) * 100));

const prevProfitLine = computed(() =>
  linePoints(m => ((profitMax.value - m.prev_profit) / profitRange.value) * 100));

/* ---------- Assen ---------- */
const fmtCompact = (v) => {
  const abs = Math.abs(v);
  const sign = v < 0 ? '−' : '';
  if (abs >= 1000) {
    const k = abs / 1000;
    return sign + '€ ' + k.toLocaleString('nl-NL', { maximumFractionDigits: k < 10 ? 1 : 0 }) + 'k';
  }
  return sign + '€ ' + Math.round(abs).toLocaleString('nl-NL');
};

const revenueTicks = computed(() => [
  { pct: 0, label: fmtCompact(yMax.value) },
  { pct: 50, label: fmtCompact(yMax.value / 2) },
]);

const profitTicks = computed(() => {
  const ticks = [];
  if (profitMax.value > 0) ticks.push({ pct: 0, label: fmtCompact(profitMax.value) });
  if (profitMin.value < 0) ticks.push({ pct: 100, label: fmtCompact(profitMin.value) });
  return ticks;
});

/* ---------- Hover / tooltip ---------- */
const hoverIdx = ref(null);

const tooltipStyle = computed(() => {
  if (hoverIdx.value === null) return {};
  const i = hoverIdx.value;
  const center = (i + 0.5) / 12 * 100;
  if (i <= 1) return { left: `${(i / 12) * 100}%` };
  if (i >= 10) return { right: `${((11 - i) / 12) * 100}%` };
  return { left: `${center}%`, transform: 'translateX(-50%)' };
});

/* ---------- Totalen ---------- */
const totals = computed(() => props.chart.totals || {});

const growthLabel = (g) => (g === null || g === undefined)
  ? null
  : `${g >= 0 ? '↑' : '↓'} ${Math.abs(g).toLocaleString('nl-NL')}%`;
</script>

<template>
  <div class="card rc-card">
    <div class="card-header rc-head">
      <div>
        <div class="card-title">Resultaat per maand</div>
        <div class="card-subtitle">Omzet, inkoop en winst · {{ chart.year }}<template v-if="chart.has_prev"> vergeleken met {{ chart.prev_year }}</template> · excl. BTW</div>
      </div>
      <div class="rc-legend">
        <span class="rc-key"><i class="rc-swatch" :style="{ background: C.revenue }"></i>Omzet</span>
        <span class="rc-key"><i class="rc-swatch" :style="{ background: C.costs }"></i>Inkoop</span>
        <span v-if="chart.has_prev" class="rc-key">
          <svg class="rc-dash" viewBox="0 0 26 6"><line x1="1" y1="3" x2="25" y2="3" :stroke="C.prev" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round"/></svg>
          {{ chart.prev_year }}
        </span>
      </div>
    </div>

    <div v-if="isEmpty" class="rc-empty">
      Nog geen cijfers voor {{ chart.year }}. Zodra je factureert (en inkoop inboekt) zie je hier je omzet en winst per maand.
    </div>

    <template v-else>
      <!-- Kerncijfers: dit jaar t.o.v. dezelfde periode vorig jaar -->
      <div class="rc-stats">
        <div class="rc-stat">
          <div class="rc-stat-label"><i class="rc-swatch" :style="{ background: C.revenue }"></i>Omzet · {{ totals.period_label }}</div>
          <div class="rc-stat-value">{{ eur(totals.revenue) }}</div>
          <div class="rc-stat-delta">
            <span v-if="growthLabel(totals.revenue_growth)" :class="totals.revenue_growth >= 0 ? 'up' : 'down'">{{ growthLabel(totals.revenue_growth) }}</span>
            <span v-if="growthLabel(totals.revenue_growth)"> vs {{ chart.prev_year }}</span>
            <span v-else>&nbsp;</span>
          </div>
        </div>
        <div class="rc-stat">
          <div class="rc-stat-label"><i class="rc-swatch" :style="{ background: C.costs }"></i>Inkoop · {{ totals.period_label }}</div>
          <div class="rc-stat-value">{{ eur(totals.costs) }}</div>
          <div class="rc-stat-delta"><span>&nbsp;</span></div>
        </div>
        <div class="rc-stat">
          <div class="rc-stat-label"><i class="rc-swatch" :style="{ background: totals.profit >= 0 ? C.profit : C.loss }"></i>{{ totals.profit >= 0 ? 'Winst' : 'Verlies' }} · {{ totals.period_label }}</div>
          <div class="rc-stat-value">{{ eur(totals.profit) }}</div>
          <div class="rc-stat-delta">
            <span v-if="growthLabel(totals.profit_growth)" :class="totals.profit_growth >= 0 ? 'up' : 'down'">{{ growthLabel(totals.profit_growth) }}</span>
            <span v-if="growthLabel(totals.profit_growth)"> vs {{ chart.prev_year }}</span>
            <span v-else>&nbsp;</span>
          </div>
        </div>
      </div>

      <div class="rc-body" @mouseleave="hoverIdx = null">
        <!-- Tooltip -->
        <div v-if="hoverIdx !== null" class="rc-tooltip" :style="tooltipStyle">
          <div class="rc-tt-title">{{ months[hoverIdx].label }} {{ chart.year }}</div>
          <div class="rc-tt-row"><i class="rc-swatch" :style="{ background: C.revenue }"></i>Omzet<span class="num">{{ eur(months[hoverIdx].revenue) }}</span></div>
          <div class="rc-tt-row"><i class="rc-swatch" :style="{ background: C.costs }"></i>Inkoop<span class="num">{{ eur(months[hoverIdx].costs) }}</span></div>
          <div class="rc-tt-row rc-tt-strong"><i class="rc-swatch" :style="{ background: months[hoverIdx].profit >= 0 ? C.profit : C.loss }"></i>{{ months[hoverIdx].profit >= 0 ? 'Winst' : 'Verlies' }}<span class="num">{{ eur(months[hoverIdx].profit) }}</span></div>
          <template v-if="chart.has_prev">
            <div class="rc-tt-sep"></div>
            <div class="rc-tt-row muted">Omzet {{ chart.prev_year }}<span class="num">{{ eur(months[hoverIdx].prev_revenue) }}</span></div>
            <div class="rc-tt-row muted">Winst {{ chart.prev_year }}<span class="num">{{ eur(months[hoverIdx].prev_profit) }}</span></div>
          </template>
        </div>

        <!-- Paneel 1: omzet & inkoop -->
        <div class="rc-panel rc-panel-main">
          <div v-for="t in revenueTicks" :key="t.pct" class="rc-grid" :style="{ top: t.pct + '%' }">
            <span class="rc-tick">{{ t.label }}</span>
          </div>
          <div class="rc-cols">
            <div
              v-for="(m, i) in months"
              :key="i"
              class="rc-col"
              :class="{ hot: hoverIdx === i }"
              @mouseenter="hoverIdx = i"
            >
              <div class="rc-pair">
                <div class="rc-bar" :style="{ height: barPct(m.revenue) + '%', background: C.revenue }"></div>
                <div class="rc-bar" :style="{ height: barPct(m.costs) + '%', background: C.costs }"></div>
              </div>
            </div>
          </div>
          <svg v-if="chart.has_prev" class="rc-line" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <polyline :points="prevRevenueLine" fill="none" :stroke="C.prev" stroke-width="2" vector-effect="non-scaling-stroke" stroke-dasharray="0.9 0.9" stroke-linejoin="round" stroke-linecap="round" opacity="0.85"/>
          </svg>
        </div>

        <!-- Maandlabels -->
        <div class="rc-labels">
          <span v-for="(m, i) in months" :key="i" :class="{ hot: hoverIdx === i }" @mouseenter="hoverIdx = i">{{ m.label }}</span>
        </div>

        <!-- Paneel 2: winst / verlies -->
        <div class="rc-panel-title">
          Winst per maand
          <span class="rc-mini-key"><i class="rc-swatch" :style="{ background: C.profit }"></i>winst</span>
          <span class="rc-mini-key"><i class="rc-swatch" :style="{ background: C.loss }"></i>verlies</span>
        </div>
        <div class="rc-panel rc-panel-profit">
          <div v-for="t in profitTicks" :key="t.pct" class="rc-grid" :style="{ top: t.pct + '%' }">
            <span class="rc-tick">{{ t.label }}</span>
          </div>
          <div class="rc-zero" :style="{ top: zeroPct + '%' }"><span class="rc-tick">€ 0</span></div>
          <div class="rc-cols">
            <div
              v-for="(m, i) in months"
              :key="i"
              class="rc-col"
              :class="{ hot: hoverIdx === i }"
              @mouseenter="hoverIdx = i"
            >
              <div
                class="rc-pbar"
                :class="{ neg: m.profit < 0 }"
                :style="{
                  top: profitBar(m.profit).top,
                  height: profitBar(m.profit).height,
                  background: m.profit >= 0 ? C.profit : C.loss,
                }"
              ></div>
            </div>
          </div>
          <svg v-if="chart.has_prev" class="rc-line" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <polyline :points="prevProfitLine" fill="none" :stroke="C.prev" stroke-width="2" vector-effect="non-scaling-stroke" stroke-dasharray="0.9 0.9" stroke-linejoin="round" stroke-linecap="round" opacity="0.85"/>
          </svg>
        </div>

        <div v-if="!chart.has_costs" class="rc-note">
          Nog geen inkoop ingeboekt — de winst is nu gelijk aan je omzet.
          Boek je <a :href="route('purchases.index')">inkoopfacturen</a> in voor een echt winstbeeld.
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.rc-card { margin-top: 20px; }
.rc-head { flex-wrap: wrap; gap: 10px; }

.rc-legend { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.rc-key { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-2); font-weight: 500; }
.rc-swatch { width: 10px; height: 10px; border-radius: 3px; display: inline-block; flex: none; }
.rc-dash { width: 26px; height: 6px; display: block; }

.rc-empty { padding: 40px 24px; text-align: center; color: var(--text-3); font-size: 13.5px; line-height: 1.6; }

.rc-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  padding: 16px 24px 4px;
}
.rc-stat-label { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-3); margin-bottom: 4px; }
.rc-stat-value { font-family: var(--font-display); font-weight: 600; font-size: 20px; letter-spacing: -0.015em; }
.rc-stat-delta { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.rc-stat-delta .up { color: var(--success); font-weight: 600; }
.rc-stat-delta .down { color: var(--brand); font-weight: 600; }

.rc-body { position: relative; padding: 16px 24px 20px; }

.rc-panel { position: relative; }
.rc-panel-main { height: 200px; border-bottom: 1px solid var(--border-strong); }
.rc-panel-profit { height: 120px; }

.rc-grid { position: absolute; left: 0; right: 0; height: 1px; background: var(--border); }
.rc-tick {
  position: absolute; left: 2px; top: -1.55em;
  font-size: 10.5px; color: var(--text-4); font-family: var(--font-mono);
  white-space: nowrap;
  background: color-mix(in srgb, var(--surface) 80%, transparent);
  padding: 0 4px; border-radius: 4px;
  z-index: 4;
}
.rc-zero { position: absolute; left: 0; right: 0; height: 1px; background: var(--border-strong); }

.rc-cols { position: absolute; inset: 0; display: flex; z-index: 2; }
.rc-col { flex: 1; position: relative; border-radius: 6px; }
.rc-col.hot { background: var(--surface-2); }

/* Paneel 1: paar balken naast elkaar, onderaan verankerd, 2px tussenruimte */
.rc-pair {
  position: absolute; inset: 0;
  display: flex; align-items: flex-end; justify-content: center; gap: 2px;
}
.rc-bar {
  width: 30%; max-width: 22px;
  border-radius: 4px 4px 0 0;
  transition: height 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Paneel 2: balken vanaf de nullijn (positief omhoog afgerond, negatief omlaag) */
.rc-pbar {
  position: absolute; left: 50%; transform: translateX(-50%);
  width: 42%; max-width: 24px;
  border-radius: 4px 4px 0 0;
}
.rc-pbar.neg { border-radius: 0 0 4px 4px; }

.rc-line { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 3; pointer-events: none; }

.rc-labels { display: flex; margin: 6px 0 14px; }
.rc-labels span {
  flex: 1; text-align: center;
  font-size: 11px; color: var(--text-3);
}
.rc-labels span.hot { color: var(--text); font-weight: 600; }

.rc-panel-title {
  display: flex; align-items: center; gap: 12px;
  font-size: 12.5px; font-weight: 600; color: var(--text-2);
  margin: 4px 0 10px;
}
.rc-mini-key { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-3); font-weight: 500; }
.rc-mini-key .rc-swatch { width: 8px; height: 8px; }

.rc-tooltip {
  position: absolute; top: -8px; z-index: 10;
  background: var(--text); color: #fff;
  border-radius: 10px; padding: 10px 14px;
  box-shadow: var(--shadow-lg);
  font-size: 12px; line-height: 1.5;
  min-width: 190px;
  pointer-events: none;
}
.rc-tt-title { font-weight: 700; margin-bottom: 6px; }
.rc-tt-row { display: flex; align-items: center; gap: 6px; padding: 1.5px 0; color: rgba(255,255,255,0.9); }
.rc-tt-row .num { margin-left: auto; font-family: var(--font-mono); color: #fff; padding-left: 14px; }
.rc-tt-strong { font-weight: 600; }
.rc-tt-row.muted { color: rgba(255,255,255,0.65); }
.rc-tt-row.muted .num { color: rgba(255,255,255,0.8); }
.rc-tt-sep { height: 1px; background: rgba(255,255,255,0.2); margin: 6px 0; }

.rc-note {
  margin-top: 14px;
  font-size: 12.5px; color: var(--text-3);
  background: var(--surface-2); border: 1px dashed var(--border-strong);
  border-radius: 9px; padding: 10px 14px;
}
.rc-note a { color: var(--brand); font-weight: 500; }

@media (max-width: 760px) {
  .rc-stats { grid-template-columns: minmax(0, 1fr); gap: 8px; padding: 14px 16px 0; }
  .rc-stat { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
  .rc-stat-label { margin-bottom: 0; }
  .rc-stat-value { font-size: 16px; }
  .rc-body { padding: 14px 10px 16px; }
  .rc-panel-main { height: 150px; }
  .rc-panel-profit { height: 100px; }
  .rc-labels span { font-size: 9.5px; }
  .rc-tooltip { min-width: 165px; font-size: 11px; }
}
</style>
