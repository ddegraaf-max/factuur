<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { eur } from '@/format.js';

const props = defineProps({
  quote: Object,
  company: Object,
});

const email = computed(() => usePage().props.portal_email || null);
const brand = computed(() => props.company.brand_color || '#E8231F');

const canDecide = computed(() => ['sent', 'expired'].includes(props.quote.status));

/* ---------- Tekenveld (handtekening) ---------- */
const canvasEl = ref(null);
const hasDrawn = ref(false);
let drawing = false;
let ctx = null;

onMounted(() => {
  if (!canvasEl.value) return;
  const canvas = canvasEl.value;
  // Scherp tekenen op high-DPI-schermen.
  const scale = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * scale;
  canvas.height = 160 * scale;
  ctx = canvas.getContext('2d');
  ctx.scale(scale, scale);
  ctx.lineWidth = 2.2;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.strokeStyle = '#1c1917';
});

const pointerPos = (e) => {
  const rect = canvasEl.value.getBoundingClientRect();
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
};

const startDraw = (e) => {
  if (!ctx) return;
  drawing = true;
  canvasEl.value.setPointerCapture(e.pointerId);
  const { x, y } = pointerPos(e);
  ctx.beginPath();
  ctx.moveTo(x, y);
};

const moveDraw = (e) => {
  if (!drawing || !ctx) return;
  const { x, y } = pointerPos(e);
  ctx.lineTo(x, y);
  ctx.stroke();
  hasDrawn.value = true;
};

const endDraw = () => { drawing = false; };

const clearSignature = () => {
  if (!ctx || !canvasEl.value) return;
  ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height);
  hasDrawn.value = false;
};

/* ---------- Ondertekenen ---------- */
const form = useForm({
  signed_name: props.quote.customer_name || '',
  signature: '',
  agree: false,
});

const sign = () => {
  if (!hasDrawn.value) {
    form.setError('signature', 'Zet eerst je handtekening in het tekenveld.');
    return;
  }
  form.signature = canvasEl.value.toDataURL('image/png');
  form.post(route('portal.quote.sign', props.quote.token), { preserveScroll: true });
};

/* ---------- Afwijzen ---------- */
const showDecline = ref(false);
const declineForm = useForm({ reason: '' });
const decline = () => {
  if (confirm('Weet je zeker dat je deze offerte wilt afwijzen?')) {
    declineForm.post(route('portal.quote.decline', props.quote.token), { preserveScroll: true });
  }
};
</script>

<template>
  <Head :title="`Offerte ${quote.number} · Portaal`" />
  <PortalLayout :email="email">
    <div class="portal-card portal-invoice">
      <!-- Kop met huisstijl van de afzender -->
      <div class="pq-head" :style="{ borderColor: brand }">
        <div>
          <img v-if="company.logo_data" :src="company.logo_data" :alt="company.name" class="pq-logo">
          <div v-else class="pq-company" :style="{ color: brand }">{{ company.name }}</div>
          <div class="pq-doc">Offerte <strong>{{ quote.number }}</strong></div>
        </div>
        <div class="pq-head-right">
          <div class="pq-total">{{ eur(quote.total) }}</div>
          <div class="pq-total-sub">incl. btw</div>
        </div>
      </div>

      <!-- Status na beslissing -->
      <div v-if="quote.status === 'accepted'" class="pq-banner ok">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span v-if="quote.signed_name">
          Digitaal ondertekend door <strong>{{ quote.signed_name }}</strong> op {{ quote.signed_at_label }}.
          Download hieronder de ondertekende PDF voor je eigen administratie.
        </span>
        <span v-else>Deze offerte is geaccepteerd{{ quote.accepted_at_label ? ` op ${quote.accepted_at_label}` : '' }}.</span>
      </div>
      <div v-else-if="quote.status === 'rejected'" class="pq-banner warn">
        Deze offerte is afgewezen{{ quote.rejected_at_label ? ` op ${quote.rejected_at_label}` : '' }}.
        Van gedachten veranderd? Neem contact op met {{ company.name }}.
      </div>
      <div v-else-if="quote.is_expired || quote.status === 'expired'" class="pq-banner warn">
        De geldigheid van deze offerte is verlopen ({{ quote.valid_until_label }}). Ondertekenen kan nog —
        of neem even contact op met {{ company.name }}.
      </div>

      <!-- Inhoud -->
      <div class="pq-body">
        <div v-if="quote.intro" class="pq-intro">{{ quote.intro }}</div>

        <table class="pq-lines">
          <thead>
            <tr><th>Omschrijving</th><th class="right">Aantal</th><th class="right">Prijs</th><th class="right">Totaal</th></tr>
          </thead>
          <tbody>
            <tr v-for="l in quote.lines" :key="l.id">
              <td>
                <div class="pq-line-desc">{{ l.description }}</div>
                <div v-if="l.details" class="pq-line-details">{{ l.details }}</div>
              </td>
              <td class="right num">{{ l.quantity }} {{ l.unit }}</td>
              <td class="right num">{{ eur(l.unit_price) }}</td>
              <td class="right num">{{ eur(l.line_subtotal) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="pq-totals">
          <div><span>Subtotaal</span><span class="num">{{ eur(quote.subtotal) }}</span></div>
          <div><span>BTW</span><span class="num">{{ eur(quote.vat_total) }}</span></div>
          <div class="grand"><span>Totaal</span><span class="num">{{ eur(quote.total) }}</span></div>
        </div>

        <div class="pq-meta">
          <span>Offertedatum: <strong>{{ quote.quote_date_label }}</strong></span>
          <span>Geldig tot: <strong>{{ quote.valid_until_label }}</strong></span>
          <span v-if="quote.reference">Referentie: <strong>{{ quote.reference }}</strong></span>
        </div>

        <div v-if="quote.notes" class="pq-notes">{{ quote.notes }}</div>

        <!-- Ondertekenen -->
        <div v-if="canDecide" class="pq-sign" :style="{ borderColor: brand }">
          <div class="pq-sign-title">Akkoord? Onderteken digitaal</div>
          <p class="pq-sign-sub">
            Zet hieronder je handtekening — dat is rechtsgeldig en scheelt printen en scannen.
            Je geverifieerde e-mailadres ({{ email }}), het tijdstip en je handtekening worden bij de offerte vastgelegd.
          </p>

          <div class="form-group">
            <label>Je volledige naam *</label>
            <input type="text" v-model="form.signed_name" maxlength="120" placeholder="Voor- en achternaam">
            <div v-if="form.errors.signed_name" class="field-error">{{ form.errors.signed_name }}</div>
          </div>

          <div class="form-group">
            <label>Handtekening *<span class="pq-hint-inline">(teken met muis of vinger)</span></label>
            <div class="pq-canvas-wrap">
              <canvas
                ref="canvasEl"
                class="pq-canvas"
                style="touch-action: none;"
                @pointerdown="startDraw"
                @pointermove="moveDraw"
                @pointerup="endDraw"
                @pointercancel="endDraw"
              ></canvas>
              <button type="button" class="pq-clear" @click="clearSignature">Wissen</button>
            </div>
            <div v-if="form.errors.signature" class="field-error">{{ form.errors.signature }}</div>
          </div>

          <label class="pq-agree">
            <input type="checkbox" v-model="form.agree">
            <span>Ik ga akkoord met deze offerte van {{ company.name }} en onderteken deze digitaal.</span>
          </label>
          <div v-if="form.errors.agree" class="field-error">{{ form.errors.agree }}</div>

          <button type="button" class="pq-sign-btn" :style="{ background: brand }" :disabled="form.processing" @click="sign">
            {{ form.processing ? 'Bezig…' : 'Onderteken en ga akkoord' }}
          </button>

          <div class="pq-decline">
            <button v-if="!showDecline" type="button" class="pq-decline-link" @click="showDecline = true">
              Liever niet akkoord? Offerte afwijzen
            </button>
            <div v-else>
              <div class="form-group">
                <label>Toelichting<span class="pq-hint-inline">(optioneel, gaat naar {{ company.name }})</span></label>
                <textarea v-model="declineForm.reason" rows="2" maxlength="500" placeholder="Bijv. we kiezen voor een andere aanpak"></textarea>
              </div>
              <button type="button" class="pq-decline-btn" :disabled="declineForm.processing" @click="decline">
                {{ declineForm.processing ? 'Bezig…' : 'Offerte definitief afwijzen' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Acties -->
        <div class="pq-actions">
          <a :href="route('portal.quote.pdf', quote.token)" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
          </a>
          <a v-if="company.email" :href="`mailto:${company.email}?subject=Vraag over offerte ${quote.number}`" class="btn btn-secondary">
            Vraag stellen
          </a>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<style scoped>
.pq-head {
  display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
  padding: 26px 32px 20px; border-bottom: 3px solid;
}
.pq-logo { max-height: 48px; max-width: 200px; }
.pq-company { font-family: var(--font-display); font-weight: 700; font-size: 20px; }
.pq-doc { margin-top: 8px; font-size: 13.5px; color: var(--text-3); }
.pq-head-right { text-align: right; flex: none; }
.pq-total { font-family: var(--font-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; }
.pq-total-sub { font-size: 11.5px; color: var(--text-4); }

.pq-banner { display: flex; align-items: flex-start; gap: 10px; margin: 18px 32px 0; padding: 13px 16px; border-radius: 10px; font-size: 13.5px; line-height: 1.55; }
.pq-banner.ok { background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC; }
.pq-banner.warn { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
.pq-banner svg { flex: none; margin-top: 2px; }

.pq-body { padding: 22px 32px 28px; }
.pq-intro { font-size: 14px; line-height: 1.7; color: var(--text-2); margin-bottom: 18px; white-space: pre-line; }

.pq-lines { width: 100%; border-collapse: collapse; }
.pq-lines th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-4); padding: 0 8px 8px; border-bottom: 2px solid var(--border); }
.pq-lines th.right, .pq-lines td.right { text-align: right; }
.pq-lines td { padding: 10px 8px; border-bottom: 1px solid var(--border); font-size: 13.5px; vertical-align: top; }
.pq-line-desc { font-weight: 500; }
.pq-line-details { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.num { font-family: var(--font-mono); }

.pq-totals { max-width: 300px; margin-left: auto; margin-top: 12px; }
.pq-totals > div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13.5px; color: var(--text-2); }
.pq-totals .grand { font-weight: 700; font-size: 15.5px; color: var(--text); border-top: 2px solid var(--text); margin-top: 6px; padding-top: 9px; }

.pq-meta { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 18px; font-size: 12.5px; color: var(--text-3); }
.pq-notes { margin-top: 14px; font-size: 13px; color: var(--text-2); background: var(--surface-2); border-radius: 8px; padding: 12px 14px; white-space: pre-line; }

.pq-sign { margin-top: 24px; border: 1.5px solid; border-radius: 12px; padding: 20px 22px; }
.pq-sign-title { font-family: var(--font-display); font-weight: 700; font-size: 16.5px; margin-bottom: 6px; }
.pq-sign-sub { font-size: 12.5px; color: var(--text-3); line-height: 1.6; margin-bottom: 16px; }
.pq-hint-inline { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 6px; }

.pq-canvas-wrap { position: relative; }
.pq-canvas { width: 100%; height: 160px; border: 1.5px dashed var(--border-strong, #D6D3D1); border-radius: 10px; background: #fff; cursor: crosshair; display: block; }
.pq-clear {
  position: absolute; top: 8px; right: 8px; font-size: 11.5px; font-weight: 600;
  color: var(--text-3); background: var(--surface-2); border-radius: 6px; padding: 4px 10px; cursor: pointer;
}
.pq-clear:hover { color: var(--text); }

.pq-agree { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; line-height: 1.55; margin: 14px 0 4px; cursor: pointer; }
.pq-agree input { margin-top: 2px; }

.pq-sign-btn {
  display: inline-flex; align-items: center; gap: 8px; margin-top: 14px;
  color: #fff; font-weight: 700; font-size: 15px; padding: 13px 26px; border-radius: 10px;
  cursor: pointer; border: none; box-shadow: 0 1px 3px rgba(28,25,23,0.18);
}
.pq-sign-btn:hover:not(:disabled) { filter: brightness(0.92); }
.pq-sign-btn:disabled { opacity: 0.6; cursor: wait; }

.pq-decline { margin-top: 16px; padding-top: 14px; border-top: 1px dashed var(--border); }
.pq-decline-link { font-size: 12.5px; color: var(--text-3); text-decoration: underline; cursor: pointer; }
.pq-decline-link:hover { color: var(--text); }
.pq-decline-btn { font-size: 13px; font-weight: 600; color: #B91C1C; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 9px 16px; cursor: pointer; }

.pq-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }

.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 6px; }
.form-group input[type="text"], .form-group textarea {
  width: 100%; max-width: 420px; border: 1px solid var(--border); border-radius: 8px;
  padding: 9px 12px; font-size: 14px; font-family: inherit;
}
.field-error { color: #B91C1C; font-size: 12px; margin-top: 5px; }

@media (max-width: 560px) {
  .pq-head { padding: 20px 18px 16px; }
  .pq-body { padding: 18px 18px 22px; }
  .pq-banner { margin: 14px 18px 0; }
}
</style>
