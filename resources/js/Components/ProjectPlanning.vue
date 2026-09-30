<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { t } from '@/i18n';

/**
 * De tijdslijn van een project: per onderdeel (gegund werk of eigen werk) een
 * balk per week, met de status en wat er automatisch naar de onderaannemer is
 * gegaan. Van hieruit: plannen, starten, klaar melden en om eerder vragen.
 */
const props = defineProps({
  project: Object,
  plan: Object, // { items, weeks, today_pct, from, to }
  subcontractors: Array,
  isOpen: Boolean,
});

const items = computed(() => props.plan.items || []);
const dated = computed(() => items.value.filter((i) => i.starts_on));
const undated = computed(() => items.value.filter((i) => !i.starts_on));
const weeks = computed(() => props.plan.weeks || []);
const statusLabel = { planned: 'Gepland', started: 'Bezig', done: 'Klaar' };
const statusPill = { planned: 'pill-sent', started: 'pill-partial', done: 'pill-paid' };

/* ---------- Onderdeel toevoegen / bewerken ---------- */
const editing = ref(null); // null | 'new' | item
const form = useForm({ title: '', starts_on: '', ends_on: '', subcontractor_id: null, notes: '' });
const openForm = (item = null) => {
  form.clearErrors();
  form.title = item?.title || '';
  form.starts_on = item?.starts_on || '';
  form.ends_on = item?.ends_on || '';
  form.subcontractor_id = item?.subcontractor_id || null;
  form.notes = item?.notes || '';
  editing.value = item || 'new';
};
const saveItem = () => {
  const opts = { preserveScroll: true, onSuccess: () => { editing.value = null; } };
  if (editing.value === 'new') form.post(route('projects.plan.store', props.project.id), opts);
  else form.patch(route('projects.plan.update', [props.project.id, editing.value.id]), opts);
};
const removeItem = (item) => {
  if (confirm(t('Onderdeel van de planning halen? De uitvraag zelf blijft bestaan.'))) {
    router.delete(route('projects.plan.destroy', [props.project.id, item.id]), { preserveScroll: true });
  }
};

/* ---------- Status ---------- */
const finishing = ref(null);
const doneForm = useForm({ status: 'done', done_on: '' });
const openDone = (item) => { doneForm.done_on = props.plan.today; finishing.value = item; };
const markDone = () => doneForm.patch(route('projects.plan.status', [props.project.id, finishing.value.id]), { preserveScroll: true, onSuccess: () => { finishing.value = null; } });
const setStatus = (item, status) => router.patch(route('projects.plan.status', [props.project.id, item.id]), { status }, { preserveScroll: true });
const earlyDone = computed(() => finishing.value && finishing.value.ends_on && doneForm.done_on && doneForm.done_on < finishing.value.ends_on);

/* ---------- Eerder vragen ---------- */
const asking = ref(null);
const earlierForm = useForm({ start: '', reason: '' });
const openEarlier = (item) => { earlierForm.clearErrors(); earlierForm.start = ''; earlierForm.reason = ''; asking.value = item; };
const askEarlier = () => earlierForm.post(route('projects.plan.earlier', [props.project.id, asking.value.id]), { preserveScroll: true, onSuccess: () => { asking.value = null; } });

/* ---------- Automatisch ---------- */
const toggleAuto = () => router.patch(route('projects.plan.auto', props.project.id), { auto_earlier: !props.project.auto_earlier }, { preserveScroll: true });

const mailState = (i) => {
  if (!i.mailable) return i.subcontractor ? t('geen e-mailadres') : null;
  if (i.problem) return '⚠️ ' + t('probleem gemeld :date', { date: i.problem_at });
  if (i.confirmed_at) return '✓ ' + t('bevestigd :date', { date: i.confirmed_at });
  if (i.reminder_at) return t('herinnering :date', { date: i.reminder_at });
  if (i.headsup_at) return t('vooraankondiging :date', { date: i.headsup_at });
  return t('mail gaat vanzelf een week vooraf');
};
const requestState = (i) => {
  if (!i.request) return null;
  if (i.request.pending) return '⏳ ' + t('gevraagd of :date kan (:sent)', { date: i.request.start_label, sent: i.request.sent_at });
  const map = { accepted: t('kan eerder: :date', { date: i.starts_label }), counter: t('kan op :date', { date: i.starts_label }), declined: t('kan niet eerder') };
  return (i.request.answer === 'declined' ? '✕ ' : '✓ ') + map[i.request.answer];
};
</script>

<template>
  <div class="card plan-card" id="planning">
    <div class="card-header">
      <div>
        <div class="card-title">{{ $t('Planning') }} <span class="count">{{ items.length }}</span></div>
        <div class="card-subtitle">{{ $t('Wie wanneer aan de slag gaat. Gegund werk uit een uitvraag komt er vanzelf bij; de onderaannemer krijgt een week vooraf en bij de start automatisch een mail.') }}</div>
      </div>
      <div class="plan-actions" v-if="isOpen">
        <label class="plan-auto" :title="$t('Is een onderdeel eerder klaar dan gepland, dan vragen we de volgende partij(en) automatisch of ze eerder kunnen beginnen.')">
          <input type="checkbox" :checked="project.auto_earlier" @change="toggleAuto"> {{ $t('Automatisch om eerder vragen') }}
        </label>
        <button class="btn btn-secondary btn-sm" @click="openForm()">{{ $t('Onderdeel toevoegen') }}</button>
      </div>
    </div>

    <!-- Tijdslijn -->
    <div v-if="dated.length" class="plan-scroll">
      <div class="plan-grid" :style="{ minWidth: (220 + weeks.length * 46) + 'px' }">
        <div class="plan-head">
          <div class="plan-label plan-label-head">{{ $t('Onderdeel') }}</div>
          <div class="plan-weeks">
            <div v-for="w in weeks" :key="w.key" class="plan-week" :class="{ current: w.current }">
              <div class="wk">{{ w.number }}</div>
              <div class="dt">{{ w.label }}</div>
            </div>
          </div>
        </div>
        <div v-for="i in dated" :key="i.id" class="plan-row">
          <div class="plan-label">
            <div class="plan-title">{{ i.title }}</div>
            <div class="plan-who">{{ i.subcontractor || $t('eigen werk') }}</div>
          </div>
          <div class="plan-track">
            <div v-for="w in weeks" :key="w.key" class="plan-cell" :class="{ current: w.current }"></div>
            <div class="plan-bar" :class="[i.status, { late: i.late, pending: i.request?.pending }]" :style="{ left: i.left + '%', width: i.width + '%' }" :title="i.starts_label + ' – ' + (i.ends_label || '')">
              <span>{{ i.starts_label }}<template v-if="i.status === 'done' && i.done_label"> · {{ $t('klaar :date', { date: i.done_label }) }}</template></span>
            </div>
          </div>
        </div>
        <div v-if="plan.today_pct !== null" class="plan-today" :style="{ left: 'calc(220px + (100% - 220px) * ' + (plan.today_pct / 100) + ')' }"><span>{{ $t('vandaag') }}</span></div>
      </div>
    </div>
    <div v-else-if="!items.length" class="card-body sub" style="line-height:1.6;">
      {{ $t('Nog niets gepland. Gun een uitvraag van dit project, of voeg zelf een onderdeel toe met een startdatum.') }}
    </div>

    <!-- De lijst met status en acties -->
    <table v-if="items.length" class="data-table plan-table">
      <thead>
        <tr>
          <th>{{ $t('Onderdeel') }}</th>
          <th>{{ $t('Wanneer') }}</th>
          <th>{{ $t('Status') }}</th>
          <th>{{ $t('Onderaannemer') }}</th>
          <th v-if="isOpen" class="actions"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="i in items" :key="i.id" :class="{ 'is-done': i.status === 'done' }">
          <td>
            <div class="plan-title">{{ i.title }}<span v-if="i.price" class="sub"> · € {{ i.price.toLocaleString('nl-NL', { minimumFractionDigits: 2 }) }}</span></div>
            <div v-if="i.notes" class="sub">{{ i.notes }}</div>
          </td>
          <td>
            <template v-if="i.starts_on">{{ i.starts_label }} – {{ i.ends_label }} <span class="sub">· {{ $t('week :week', { week: i.week }) }}</span></template>
            <span v-else class="sub">{{ $t('nog in te plannen') }}</span>
            <div v-if="i.late" class="sub warn">{{ $t('over de einddatum heen') }}</div>
            <div v-if="requestState(i)" class="sub">{{ requestState(i) }}<template v-if="i.request?.message && i.request?.answer"> · „{{ i.request.message }}”</template></div>
          </td>
          <td><span :class="['pill', statusPill[i.status]]">{{ $t(statusLabel[i.status]) }}</span></td>
          <td>
            <div>{{ i.subcontractor || '—' }}</div>
            <div class="sub" :class="{ warn: i.problem }">{{ mailState(i) }}</div>
            <div v-if="i.problem" class="sub warn">„{{ i.problem }}”</div>
          </td>
          <td v-if="isOpen" class="actions">
            <button v-if="i.status === 'planned' && i.starts_on" class="btn btn-ghost btn-sm" @click="setStatus(i, 'started')">{{ $t('Start') }}</button>
            <button v-if="i.status !== 'done'" class="btn btn-ghost btn-sm" @click="openDone(i)">{{ $t('Klaar') }}</button>
            <button v-if="i.status === 'done'" class="btn btn-ghost btn-sm" @click="setStatus(i, 'planned')">{{ $t('Heropenen') }}</button>
            <button v-if="i.status === 'planned' && i.starts_on && i.mailable && !i.request?.pending" class="btn btn-ghost btn-sm" @click="openEarlier(i)">{{ $t('Eerder vragen') }}</button>
            <button class="btn btn-ghost btn-sm" @click="openForm(i)">{{ $t('Bewerken') }}</button>
            <button class="btn btn-ghost btn-sm" :title="$t('Verwijderen')" @click="removeItem(i)">✕</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Onderdeel -->
    <div v-if="editing" class="modal-overlay" @click.self="editing = null">
      <div class="modal" style="max-width:560px;">
        <div class="modal-header">
          <div class="modal-title">{{ editing === 'new' ? $t('Onderdeel toevoegen') : $t('Onderdeel bewerken') }}</div>
          <button class="btn btn-ghost btn-sm" @click="editing = null">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ $t('Onderdeel') }} *</label>
            <input type="text" v-model="form.title" maxlength="160" :placeholder="$t('Bijv. Metselwerk, Dakbedekking, Eigen ploeg: ruwbouw')">
            <div v-if="form.errors.title" class="field-error">{{ form.errors.title }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Start') }}</label>
              <input type="date" v-model="form.starts_on">
            </div>
            <div class="form-group">
              <label>{{ $t('Tot en met') }} <span class="sub">{{ $t('(leeg = één werkweek)') }}</span></label>
              <input type="date" v-model="form.ends_on" :min="form.starts_on || undefined">
            </div>
          </div>
          <div v-if="editing === 'new' || !editing.round_id" class="form-group">
            <label>{{ $t('Onderaannemer') }} <span class="sub">{{ $t('(optioneel; met e-mailadres gaan de mails vanzelf)') }}</span></label>
            <select v-model="form.subcontractor_id">
              <option :value="null">{{ $t('— Eigen werk —') }}</option>
              <option v-for="s in subcontractors" :key="s.id" :value="s.id">{{ s.name }}<template v-if="!s.mailable"> ({{ $t('geen e-mail') }})</template></option>
            </select>
          </div>
          <div v-else class="sub" style="margin-bottom:12px;">{{ $t('Gegund aan :name via de uitvraag.', { name: editing.subcontractor }) }}</div>
          <div class="form-group">
            <label>{{ $t('Bijzonderheden') }} <span class="sub">{{ $t('(gaan mee in de mail aan de onderaannemer)') }}</span></label>
            <textarea v-model="form.notes" rows="3" maxlength="2000" :placeholder="$t('Bijv. melden bij de uitvoerder, steiger staat er al')"></textarea>
          </div>
          <div v-if="form.errors.plan" class="field-error">{{ form.errors.plan }}</div>
          <p v-if="editing !== 'new' && editing.starts_on" class="sub" style="line-height:1.5;">{{ $t('Verandert de startdatum, dan gaan de vooraankondiging en herinnering opnieuw op tijd uit.') }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="editing = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="saveItem">{{ $t('Opslaan') }}</button>
        </div>
      </div>
    </div>

    <!-- Klaar melden -->
    <div v-if="finishing" class="modal-overlay" @click.self="finishing = null">
      <div class="modal" style="max-width:480px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t(':title klaar melden', { title: finishing.title }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="finishing = null">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ $t('Klaar op') }}</label>
            <input type="date" v-model="doneForm.done_on">
          </div>
          <p v-if="earlyDone && project.auto_earlier" class="sub" style="line-height:1.6;">{{ $t('Dat is eerder dan gepland (:end). De volgende partijen met een e-mailadres krijgen automatisch de vraag of zij evenveel eerder kunnen beginnen; hun antwoord schuift de planning vanzelf op.', { end: finishing.ends_label }) }}</p>
          <p v-else-if="earlyDone" class="sub" style="line-height:1.6;">{{ $t('Dat is eerder dan gepland. Automatisch om eerder vragen staat uit; je kunt het per onderdeel doen met „Eerder vragen”.') }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="finishing = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="doneForm.processing" @click="markDone">{{ $t('Klaar melden') }}</button>
        </div>
      </div>
    </div>

    <!-- Eerder vragen -->
    <div v-if="asking" class="modal-overlay" @click.self="asking = null">
      <div class="modal" style="max-width:480px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Eerder vragen aan :name', { name: asking.subcontractor }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="asking = null">✕</button>
        </div>
        <div class="modal-body">
          <p class="sub" style="line-height:1.6;margin:0 0 12px;">{{ $t(':title staat gepland op :date. De onderaannemer krijgt een mail met de vraag of hij eerder kan, en antwoordt met één klik: ja, een andere dag, of nee.', { title: asking.title, date: asking.starts_label }) }}</p>
          <div class="form-group">
            <label>{{ $t('Voorgestelde start') }}</label>
            <input type="date" v-model="earlierForm.start" :max="asking.starts_on">
            <div v-if="earlierForm.errors.start" class="field-error">{{ earlierForm.errors.start }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Toelichting') }} <span class="sub">{{ $t('(optioneel)') }}</span></label>
            <textarea v-model="earlierForm.reason" rows="2" maxlength="500" :placeholder="$t('Bijv. de ruwbouw is een week eerder klaar')"></textarea>
          </div>
          <div v-if="earlierForm.errors.plan" class="field-error">{{ earlierForm.errors.plan }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="asking = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="earlierForm.processing || !earlierForm.start" @click="askEarlier">{{ $t('Vraag versturen') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plan-card { margin-bottom: 16px; }
.plan-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.plan-auto { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-2); cursor: pointer; white-space: nowrap; }
.plan-auto input { width: 15px; height: 15px; padding: 0; }
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.count { font-size: 12px; color: var(--text-3); font-weight: 500; margin-left: 4px; }
.warn { color: var(--brand-dark); }
.actions { white-space: nowrap; text-align: right; }
.actions .btn { padding: 2px 7px; }
.plan-scroll { overflow-x: auto; border-top: 1px solid var(--border); }
.plan-grid { position: relative; padding-bottom: 10px; }
.plan-head, .plan-row { display: grid; grid-template-columns: 220px minmax(0, 1fr); }
.plan-label { padding: 8px 14px; border-right: 1px solid var(--border); min-width: 0; }
.plan-label-head { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; display: flex; align-items: flex-end; }
.plan-title { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.plan-who { font-size: 12px; color: var(--text-3); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.plan-weeks { display: flex; }
.plan-week { flex: 1 1 0; min-width: 46px; text-align: center; padding: 6px 0 4px; border-left: 1px solid var(--border); }
.plan-week .wk { font-size: 12px; font-weight: 700; }
.plan-week .dt { font-size: 10.5px; color: var(--text-3); }
.plan-week.current { background: var(--brand-tint); }
.plan-week.current .wk { color: var(--brand); }
.plan-track { position: relative; display: flex; height: 40px; border-top: 1px solid var(--border); }
.plan-cell { flex: 1 1 0; min-width: 46px; border-left: 1px solid var(--border); }
.plan-cell.current { background: var(--brand-tint); }
.plan-bar { position: absolute; top: 8px; height: 24px; border-radius: 6px; background: #DBEAFE; border: 1px solid #93C5FD; color: #1E3A8A; font-size: 11px; line-height: 22px; padding: 0 8px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; box-sizing: border-box; }
.plan-bar.started { background: #FEF3C7; border-color: #FCD34D; color: #78350F; }
.plan-bar.done { background: #DCFCE7; border-color: #86EFAC; color: #14532D; }
.plan-bar.late { border-color: var(--brand); border-width: 2px; }
.plan-bar.pending { border-style: dashed; }
.plan-today { position: absolute; top: 0; bottom: 0; width: 0; border-left: 2px dashed var(--brand); pointer-events: none; }
.plan-today span { position: absolute; top: -2px; left: 4px; font-size: 10px; color: var(--brand); font-weight: 600; background: var(--surface); padding: 0 3px; }
.plan-table { border-top: 1px solid var(--border); }
.plan-table tr.is-done td { color: var(--text-3); }
</style>
